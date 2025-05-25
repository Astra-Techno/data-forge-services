<?php

namespace AstraTech\DataForge\Base;

class ClassStatic
{
    protected static $_errors = [];

    final static function getCaller()
	{
		$class = '';
		$method = '';
		$line = '';

		foreach (debug_backtrace() as $key => $m)
		{
			$class	= empty($m['class']) ? '' : $m['class'];
			$method	= empty($m['function']) ? '' : $m['function'];

			if (in_array(strtolower($method), array('outmsg', 'addlog', 'addlogs', 'seterror', 'getsqlresult', 'getdata', 'execute', 'query'))) {
				$line = empty($m['line']) ? '' : $m['line'];
				continue;
			}

			$line	= empty($m['line']) ? '' : $m['line'];
		}

		return trim($class.'::'.$method.' - (line:'.$line.')');
	}

    public static function raiseError($str, $code = 400)
    {
        throw new \Exception($str, $code);
    }

    public static function setError($msg)
    {
        self::$_errors[] = ['error' => $msg, 'trace' => self::getCaller()];
        return false;
    }

    public static function getError($full = false)
    {
        $error = array_pop(self::$_errors);
        if (!$error)
            return $full ? ['msg' => '', 'trace' => ''] : '';

        return $full ? $error : $error['error'];
    }
}
