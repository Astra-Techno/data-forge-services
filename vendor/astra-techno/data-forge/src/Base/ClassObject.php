<?php

namespace AstraTech\DataForge\Base;

use \AllowDynamicProperties;

#[AllowDynamicProperties]
class ClassObject
{
    protected $_errors = [];

    protected $_caller;

    final function getCaller($skipClass = [], $skipFunction = [])
	{
		$class = '';
		$method = '';
		$line = '';

		foreach (debug_backtrace() as $key => $m)
		{
			$class	= empty($m['class']) ? '' : $m['class'];
			$method	= empty($m['function']) ? '' : $m['function'];

            if ($skipClass && !$class)
                continue;

            if ($skipFunction && $method && in_array($method, $skipFunction))
                continue;

			if (in_array(strtolower($method), array('outmsg', 'addlog', 'addlogs', 'seterror', 'getsqlresult', 'getdata', 'execute', 'query'))) {
				$line = empty($m['line']) ? '' : $m['line'];
				continue;
			}

            $tmp = explode("\\", $class);
            $tmp = end($tmp);

            if ($skipClass && $tmp && in_array($tmp, $skipClass))
                continue;

            $line	= empty($m['line']) ? '' : $m['line'];
            break;
		}

		return ['class' => $class, 'method' => $method, 'line' => $line];
	}

    public function set($property, $value)
    {
        if (!is_object($value) && !is_array($value))
            $value = trim($value);

        $this->$property = $value;
    }

    public function raiseError($str, $code = 400)
    {
        throw new \Exception($str, $code);
    }

    public function setError($msg)
    {
        $this->_errors[] = ['message' => $msg, 'trace' => $this->getCaller()];
        return false;
    }

    public function getError($full = false)
    {
        $error = array_pop($this->_errors);
        if (!$error)
            return $full ? ['msg' => '', 'trace' => ''] : '';

        return $full ? $error : $error['error'];
    }
}
