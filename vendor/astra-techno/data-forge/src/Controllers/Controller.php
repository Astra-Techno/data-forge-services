<?php

namespace AstraTech\DataForge\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller as BaseController;
use App\Helpers\QueryHelper;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $_errors = [];

    public function setError($msg,  $code = 400)
    {
        $this->_errors[] = ['message' => $msg, 'code' => $code];
        return false;
    }

    public function getError()
    {
        if (empty($this->_errors))
            return [];

        return array_pop($this->_errors);
    }

    public function newQuery($name)
    {
        return new QueryHelper($name);
    }

    public function response($out = null)
    {
        $error = $this->getError();
        if (!$out && $error)
            return response()->json(['error' => $error['message']], $error['code']);
        return response()->json($out, 200);
    }

    final function dbResult($query, $column = '')
    {
		$row = $this->dbRow($query);
        if ($row === false)
            return false;

		if (count($row) == 0 || ($column && !isset($row[$column])))
			return '';

		return current($row);
    }

    final function dbResults($query, $column = '')
    {
		$rows = $this->dbObjects($query);
        if ($rows === false)
            return false;

		if (count($rows) == 0 || ($column && !isset($rows[0]->$column)))
			return [];
		$column = empty($column) ? current(array_keys((array)$rows[0])) : $column;
		return array_column($rows, $column);
    }

    final function dbRow($query)
    {
        $out = $this->dbObject($query);
        if ($out === false)
            return false;

		return json_decode(json_encode($out), true);
    }

    final function dbRows($query)
    {
        $rows = $this->dbObjects($query);
        if ($rows === false)
            return false;

		return json_decode(json_encode($rows), true);
    }

    final function dbObject($query)
    {
        try {
		    $out = DB::selectOne($query);
        } catch(\Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    
        return $out;
    }

    final function dbObjects($query, $group_column = null)
    {
        try {
            $errorSet = false;

		    $out = DB::select($query);
            if ($group_column && count($out) > 0)
            {
                $groupped_out = [];
                foreach ($out as $out_item) {
                    if (!property_exists($out_item, $group_column)) {
                        $errorSet = true;
                        $this->setError('group column missing on select: ' . $group_column);
                        break;
                    }

                    $group_value = $out_item->$group_column;
                    $groupped_out[$group_value] = $out_item;
                }

                if (!$errorSet)
                    $out = $groupped_out;
            }
        } catch(\Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }

        return $out;
    }

    final function execute($query)
    {
        try {
		    DB::statement($query);
        } catch(\Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }

        return true;
    }
}