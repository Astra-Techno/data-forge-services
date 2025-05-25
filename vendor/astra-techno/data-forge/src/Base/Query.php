<?php

namespace AstraTech\DataForge\Base;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class Query extends ClassObject
{
    protected $_name;
    protected $from;
    protected $join = [];
    protected $where = [];
    protected $whereGroup = [];
    protected $select = [];
    protected $group;
    protected $orderBy = '';
    protected $order = '';
    protected $query;
    protected $select_type = 'list';
    protected $limit;
    protected $page;
    protected $having = [];
    protected $union = [];
    protected $subqueries = [];
    protected $pdo;

    public function __construct($name)
    {
        $this->_name = 'SQL:' . $name;
        $this->pdo = DB::connection()->getPdo();
    }

    public function getName()
    {
        return $this->_name;
    }

    public function assignKeys($input)
    {
        $keys = ['select_type', 'limit', 'page'];
        foreach ($keys as $key) {
            if (Arr::has($input, $key))
                $this->{$key} = $input[$key];
        }
    }

    public function assign($query)
    {
        $this->query = $query;
        return $this;
    }

    public function from($str)
    {
        $this->set('from', $str);
        return $this;
    }

    public function select($name, $value)
    {
        $this->select[$name] = $value;
        return $this;
    }

    public function join($str)
    {
        if ($str != '')
            $this->join[] = 'JOIN ' . $str;
        return $this;
    }

    public function inner($str)
    {
        if ($str != '')
            $this->join[] = 'INNER JOIN ' . $str;
        return $this;
    }

    public function left($str)
    {
        if ($str != '')
            $this->join[] = 'LEFT JOIN ' . $str;
        return $this;
    }

    public function filter($str, $required = true)
    {
        if ($str != '')
            $this->where[] = ['condition' => $str, 'required' => $required];

        return $this;
    }

    public function filterOptional($str)
    {
        return $this->filter($str, false);
    }

    public function filterAnyOneRequired($type, $array)
    {
        $this->whereGroup[$type] = $array;
        return $this;
    }

    public function group($str)
    {
        $this->set('group', $str);
        return $this;
    }

    public function having($str)
    {
        $this->having[] = $str;
        return $this;
    }

    public function union($query)
    {
        $this->union[] = $query;
        return $this;
    }

    public function subquery($alias, $query)
    {
        $this->subqueries[$alias] = $query;
        return $this;
    }

    public function order($field, $direction = 'asc')
    {
        $this->set('orderBy', $field);
        $this->set('order', $direction);
        return $this;
    }

    public function getSelect()
    {
        $type = trim($this->select_type);
        if ($type && !in_array($type, array_keys($this->select)))
            $this->raiseError($this->_name . ' - Invalid select type (' . $type . ')!');

        return $this->select[$type];
    }

    public function getGroup()
    {
        if ($this->select_type == 'total')
            return '';

        return $this->group;
    }

    public function getOrder()
    {
        if ($this->select_type == 'total')
            return '';

        return trim($this->orderBy);
    }

    public function getLimit()
    {
        $limit = intval($this->limit ?? 0);
        if ($this->select_type == 'total' || !$limit)
            return '';

        $page = intval($this->page ?? 0);
        $from = $page > 0 ? ($page - 1) * $limit : 0;
        return $from . ',' . $limit;
    }

    public function __toString()
    {
        if ($this->query)
            return $this->query;

        if (!empty($this->union)) {
            $query[] = implode(" \nUNION \n", $this->union);
            return implode(" \n", $query);
        }

        // Build the SQL query string.
        $query = ["SELECT"];
        $query[] = "\t" . $this->getSelect();

        if (strpos(strtoupper($this->from), 'FROM ') === false)
            $query[] = "FROM " . $this->from;
        else
            $query[] = $this->from;

        foreach ($this->join as $join)
            $query[] = $join;

        $this->where = array_values($this->where);
        foreach ($this->where as $key => $where) {
            if (!$key)
                $query[] = 'WHERE';

            $query[] = $key ? "\tAND (" . $where['condition'] . ")" : "\t(" . $where['condition'] . ")";
        }

        $group = $this->getGroup();
        if ($group)
            $query[] = 'GROUP BY ' . $group;

        if (!empty($this->having)) {
            $query[] = 'HAVING ' . implode(' AND ', $this->having);
        }

        $order = $this->getOrder();
        if ($order)
            $query[] = 'ORDER BY ' . $order;

        $limit = $this->getLimit();
        if ($limit)
            $query[] = 'LIMIT ' . $limit;

        return implode(" \n", $query);
    }

    public function bind($data)
    {
        $failed = [];
        if ($this->query) {
            $this->query = $this->replaceConstant($this->query, $data, $failed, false);
            if (!$this->query && $failed) {
                Log::error($this->_name . ' - required inputs (' . implode(", ", $failed) . ') missing!');
                $this->raiseError($this->_name . ' - required inputs (' . implode(", ", $failed) . ') missing!');
            }
            return;
        }

        foreach ($this->where as $key => $where) {
            $failed = [];
            $str = $this->replaceConstant($where['condition'], $data, $failed, true);
            if ($str && !$failed) {
                $this->where[$key]['condition'] = $str;
                continue;
            }

            if ($where['required'] && $failed) {
                Log::error($this->_name . ' - required inputs (' . implode(", ", $failed) . ') missing!');
                $this->raiseError($this->_name . ' - required inputs (' . implode(", ", $failed) . ') missing!');
            }

            unset($this->where[$key]);
        }

        foreach ($this->whereGroup as $key => $conditions) {
            $added = false;
            foreach ($conditions as $i => $condition) {
                $failed = [];
                $str = $this->replaceConstant($condition, $data, $failed, true);
                if (!$str || $failed)
                    continue;

                $added = true;
                $this->where[]['condition'] = $str;
            }

            if (!$added) {
                Log::error($this->_name . ' - group (' . $key . ') no more conditions matched!');
                $this->raiseError($this->_name . ' - group (' . $key . ') no more conditions matched!');
            }
        }

        $failed = [];
        $this->orderBy = $this->replaceConstant($this->orderBy, $data, $failed, false);
        $this->order = $this->replaceConstant($this->order, $data, $failed, false);

        $this->order = strtoupper(trim($this->order));
        if (!in_array($this->order, ['ASC', 'DESC']))
            $this->order = '';

        $this->orderBy .= ' ' . $this->order;
        if ($failed)
            $this->orderBy = '';
    }

    public function is_int($var)
    {
        return preg_match('/^\d+(,\d+)*$/', trim(str_replace(' ', '', $var)));
    }

    public function getRequestField($field)
    {
        $allowed_fields = ['sort_by', 'sort_order', 'select_type'];
        if (strpos($field, 'request.') === 0)
            return str_replace('request.', '', $field);
        else if (strpos($field, 'filter_') === 0 || strpos($field, 'filter.') === 0)
            return $field;
        else if (in_array($field, $allowed_fields))
            return $field;

        return '';
    }

    public function replaceConstant($str, $input, &$failed = [], $isSql = false)
    {
        preg_match_all('/{(.*?)}/', $str, $matches);

        if (empty($matches[1]) || strpos($str, '{"') !== false)
            return $str;

        $request = request()->all();
        $find = $replace = [];

        $fields = array_unique(array_map('trim', $matches[1]));
        foreach (array_unique($matches[1]) as $i => $match) {
            $request_field = $this->getRequestField($match);

            if ($request_field && Arr::has($request, $request_field))
                $value = Arr::get($request, $request_field);
            else if (Arr::has($input, $match))
                $value = Arr::get($input, $match);
            else {
                $failed[] = $match;
                continue;
            }

            if ($isSql && !$this->is_int($value)) {
                // Use PDO to safely bind the value
                $stmt = $this->pdo->prepare("SELECT :value AS value");
                $stmt->bindValue(':value', $value);
                $stmt->execute();
                $value = $stmt->fetchColumn();
                $value = dbQuote($value);
            }

            $find[] = '{' . $match . '}';
            $replace[] = $value;
        }

        return str_replace($find, $replace, $str);
    }

    public function toEloquent()
    {
        $query = new QueryBuilder(app('db')->connection());
        $query->select($this->getSelect())
            ->from($this->from);

        foreach ($this->join as $join) {
            $query->join($join);
        }

        foreach ($this->where as $where) {
            $query->whereRaw($where['condition']);
        }

        if ($this->group) {
            $query->groupBy($this->group);
        }

        if (!empty($this->having)) {
            $query->havingRaw(implode(' AND ', $this->having));
        }

        if ($this->orderBy) {
            $query->orderBy($this->orderBy, $this->order);
        }

        if ($this->limit) {
            $query->limit($this->limit);
        }

        return $query;
    }
}
