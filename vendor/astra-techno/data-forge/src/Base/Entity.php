<?php

namespace AstraTech\DataForge\Base;

abstract class Entity extends ClassObject
{
	private $_data = [];

    // Magic method to set dynamic properties
    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
    }

    final function getClassName()
    {
        return str_replace('App\DataForge\\', '', get_class($this));
    }

    // Magic method to get dynamic properties
    public function __get($name)
    {
        if (isset($this->_data[$name]))
            return $this->_data[$name];
        else if (isset($this->_data['_method'][$name]))
            return $this->_data['_method'][$name];

        $method = 'get'.$name;
        if (!method_exists($this, $method))
            $this->raiseError($name." - property not found in class ".$this->className);

        $this->_data['_method'][$name] = $this->$method();
        return $this->_data['_method'][$name];
    }

    abstract function init($args);

	final function bind($array)
	{
		// Assigning dynamic properties to the object
		foreach ($array as $key => $value) {
			$this->$key = $value;
		}
	}

    final function unset($args)
    {
        $args = (array) $args;
        foreach ($args as $arg) {
            if (isset($this->_data[$arg]))
                unset($this->_data[$arg]);
            else if (isset($this->_data['_method'][$arg]))
                unset($this->_data['_method'][$arg]);
        }
    }

    final function reset()
    {
        $this->_data['_method'] = [];
    }

	function _getPropertyMethods()
	{
		$methods1 = get_class_methods($this);
		$methods2 = DataForge::classMethods('AstraTech\DataForge\Base\ClassObject');
		$methods3 = DataForge::classMethods('AstraTech\DataForge\Base\Entity');

		$methods1 = array_diff($methods1, $methods2);
		$methods1 = array_diff($methods1, $methods3);

		$methods = array();
		foreach ($methods1 as $method)
		{
			if (stripos($method, 'get') !== 0)
				continue;

			if ($method = preg_replace('/get/', '', $method, 1))
				$methods[] = lcfirst($method);
		}

		return $methods;
	}

    final function getBaseAttribs()
    {
        $base = $this->_data;
 		if (isset($base['_method']))
 			unset($base['_method']);

 		return $base;
    }

 	function toArray($attrbs = [], $withBase = true)
 	{
 		$data = $withBase ? $this->getBaseAttribs() : [];
 		if (!$attrbs)
 			return $data;

 		if ($attrbs == 'all')
 			$attrbs = $this->_getPropertyMethods();
 		else if (!is_array($attrbs))
 			$attrbs = DataForge::split($attrbs);

        foreach ($attrbs as $attrb)
        {
			$tmp = $this->$attrb;
            if (is_object($tmp) && strpos(get_class($tmp), 'App\DataForge\Entity\\') !== false)
                $tmp = $tmp->toArray();
            else if (is_array($tmp) && !empty($tmp[0]))
            {
            	$sub = [];
				foreach ($tmp as $key => $val) {
					if (is_object($val) && strpos(get_class($val), 'App\DataForge\Entity\\') !== false)
					    $val = $val->toArray();

					$sub[$key] = $val;
				}

				$tmp = $sub;
            }

            $data[$attrb] = $tmp;
        }

		return $data;
 	}

    function toGroupArray($group, $attribs, $withBase = false)
 	{
        if (is_array($attribs))
            $attribs = implode(',', $attribs);

 		if (method_exists($this, 'attribGroups')) {
            $groups = $this->attribGroups();
            if (isset($groups[$group]))
                $attribs .= ','.$groups[$group];
        }

        $attribs = DataForge::split($attribs);
		return $this->toArray($attribs, $withBase);
 	}
}
