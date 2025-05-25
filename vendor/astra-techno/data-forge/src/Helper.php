<?php

use Illuminate\Support\Facades\DB;
use AstraTech\DataForge\Base\Sql;
use AstraTech\DataForge\Base\Task;
use AstraTech\DataForge\Base\Query;

if  (!function_exists('Sql')) {
    /**
     * Sql function is used to instantiate sql class methods & return query object. The first argument is the name & method and second one - array of inputs (filter params).
     * @uses Sql('sqlClassName') - Returns an query object instance of the specified SQL class default method.
     * @uses Sql('sqlClassName:methodName') - Returns a query object instance of the specified SQL class & method.
     * @uses Sql('sqlClassName:methodName', ['xxx' => 100, 'yyyy' => 'abc@alpha.com']) - Returns an instance of the specified SQL class & method.
     *
     * @param  string  $name
     * @param  array  $input
     * @return object Query object instance.
     */
    function Sql($name, $input = [])
    {
        $sql = new Sql();
        return $sql->load($name, $input);
    }
}

if  (!function_exists('Schema')) {
    /**
     * Schema function is used to instantiate sql class methods & return query object. The first argument is the name & method and second one - array of inputs (filter params).
     * @uses Sql('sqlClassName') - Returns an query object instance of the specified SQL class default method.
     * @uses Sql('sqlClassName:methodName') - Returns a query object instance of the specified SQL class & method.
     * @uses Sql('sqlClassName:methodName', ['xxx' => 100, 'yyyy' => 'abc@alpha.com']) - Returns an instance of the specified SQL class & method.
     *
     * @param  string  $name
     * @param  array  $input
     * @return object Query object instance.
     */
    function Schema($name, array $input = [])
    {
        $sql = new Sql();
        return $sql->load($name, $input);
    }
}


if  (!function_exists('Task')) {
    /**
     *
     * @param  string  $name
     * @param  array  $input
     * @return object Query object instance.
     */
    function Task($name, $input = [])
    {
        $Task = new Task();
        return $Task->load($name, $input);
    }
}

if  (!function_exists('Factory')) {
    function Factory()
    {
        /*static $factory = null;
        if (!$factory)
            $factory = new Factory();
        return $factory;*/
    }
}

if  (!function_exists('Query')) {
    /**
     * Query function is used to instantiate query object using this to can create custom sql query with multiple options. The first argument is just used to naming the query.
     * @uses Query('MyPropertyList') - Returns a query object instance.
     *
     * @param  string  $name
     * @return object Returns query base instance of query object, user should extend it to create own queries.
     */
    function Query($name)
    {
        $factory = new Query($name);
        return $factory;
    }
}

if  (!function_exists('dbQuote')) {
	/**
	 * Quotes a string for use in a query
	 * PDO::quote() places quotes around the input string (if required) and escapes special characters within the input string, using a quoting style appropriate to the underlying driver.
	 *
	 * @param string $string The string to be quoted.
	 * @return bool|string Returns a quoted string that is theoretically safe to pass into an SQL statement. Returns `false` if the driver does not support quoting in this way.
	 */
    function dbQuote($str)
    {
        return DB::connection()->getPdo()->quote($str);
    }
}

if  (!function_exists('debugMode')) {
	/**
	 * DebugMode function returns boolean value based on debug flag.
	 *
	 * @return bool Return true if debug flag enabled else return false.
	 */
    function debugMode()
    {
    	if (request('debug') == 1)
            return true;

        return false;
    }
}
