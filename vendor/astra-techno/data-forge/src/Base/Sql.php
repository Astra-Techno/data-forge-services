<?php

namespace AstraTech\DataForge\Base;

use Illuminate\Support\Facades\DB;

class Sql extends ClassObject
{
    protected $_query;

	final function setQuery($query)
	{
		$this->_query = $query;
	}

	final function getQuery()
	{
		return (string) $this->_query;
	}

    final function getQueryName()
    {
        return $this->_query->getName();
    }

    final function load($name, $input)
    {
        if (empty($name))
            return $this;

        @list($className, $method) = DataForge::findClass('App\DataForge\Sql', $name);

        $classMethod = $method ?? 'default';
        $classMethod = trim($classMethod);

        $class = new $className();

        if (!method_exists($class, $classMethod)) {
        	$className = ltrim($className, 'App\DataForge\\');
            $this->raiseError("$className:$classMethod - method not found!");
        }

        $query = call_user_func_array([$class, $classMethod], [&$input]);
        if (!$query) {
            $this->raiseError("$className:$classMethod - empty query returned!");
        }

        if ($query instanceof Query) {
            $query->assignKeys($input);
            $query->bind($input);
        } else
            DataForge::replaceConstant($query, $input);

		$class->setQuery($query);

		return $class;
    }

	function __toString()
	{
		return $this->getQuery();
	}

    final function execute()
    {

    }

    final function result($column = '')
    {
		$row = $this->assoc();
		if (!$row || count($row) == 0 || ($column && !isset($row[$column])))
			return '';

		return current($row);
    }

    final function fetchColumn($column = '')
    {
		$row = $this->assoc();
		if (!$row || count($row) == 0 || ($column && !isset($row[$column])))
			return '';

		return current($row);
    }

    final function resultList($column = '')
    {
		$rows = $this->objectList();
		if (count($rows) == 0 || ($column && !isset($rows[0]->$column)))
			return [];

		$column = empty($column) ? current(array_keys((array)$rows[0])) : $column;
		return array_column($rows, $column);
    }

    final function assoc()
    {
		return json_decode(json_encode($this->object()), true);
    }

    final function fetchRow()
    {
		return json_decode(json_encode($this->object()), true);
    }

    final function assocList()
    {
		return json_decode(json_encode($this->objectList()), true);
    }

    final function fetchRowList()
    {
		return json_decode(json_encode($this->objectList()), true);
    }

    final function object()
    {
        try {
		    $out = DB::selectOne($this->getQuery());
        } catch(\Exception $e) {
            self::raiseError(self::getQueryName().' - '. $e->getMessage());
        }

        return $out;
    }

    final function objectList($group_column = null)
    {
        try {
		    $out = DB::select($this->getQuery());
            if ($group_column && count($out) > 0)
            {
                $groupped_out = [];
                foreach ($out as $out_item) {
                    if (!property_exists($out_item, $group_column)) {
                        self::raiseError(self::getQueryName() . ' - group column missing on select: ' . $group_column);
                    }
                    $group_value = $out_item->$group_column;
                    $groupped_out[$group_value] = $out_item;
                }
                $out = $groupped_out;
            }
        } catch(\Exception $e) {
            self::raiseError(self::getQueryName().' - '. $e->getMessage());
        }

        return $out;
    }

    final function count()
    {
        try {
		    $out = DB::select($this->getQuery());
        } catch(\Exception $e) {
            self::raiseError(self::getQueryName().' - '. $e->getMessage());
        }

        return $out;
    }
}
