<?php

namespace AstraTech\DataForge\Base;

use Illuminate\Support\Arr;

/**
 * @ignore
 */

class DataForge extends ClassStatic
{
    public static function findClass($prefix, $name, $delimitter = ':', $skipError = false)
    {
        if (empty($name))
            self::raiseError("Empty value passed to load class!");


        @list($name, $classMethod) = self::split($name, $delimitter);
        $className = $prefix ? $prefix.'\\'.$name : $name;

        if (!class_exists($className)) {
        	if ($skipError) {
				self::setError($className.' - Bad Request!');
				return false;
        	}

			self::raiseError($className.' - Bad Request!');
        }

        $classMethod = $classMethod ?? null;

	    return [$className, $classMethod];
    }

    /**
     * Split the string  by a delimiter passed.  Default delimitter is ",".
     *
     * @param  string  $name
     * @param  string  $delimitter
     * @return array
     */
    public static function split($name, $delimitter = ',', $noDuplicates = true)
    {
        $delimitter = empty($delimitter) ? ':' : $delimitter;
        $split = explode($delimitter, $name);

		if ($noDuplicates)
			$split = array_unique(array_filter($split));

		return array_map('trim', $split);
    }

    /**
     * Validate passed input is integer or not.
     *
     * @param  string  $var
     * @return boolean
     */
    static function is_int($var)
    {
        return preg_match('/^\d+(,\d+)*$/', trim(str_replace(' ', '', $var)));
    }

    /**
     * @ignore
     */
    static function getRequestField($field)
    {
        $allowed_fields = ['sort_by', 'sort_order', 'select_type'];
        if (strpos($field, 'request.') === 0 || strpos($field, 'filter.') === 0) 
           return str_replace('request.', '', $field);
        else if (in_array($field, $allowed_fields))
            return $field;

        return '';
    }

    static function replaceConstant($str, $input, &$failed = [], $isSql = false)
    {
        preg_match_all('/{(.*?)}/', $str, $matches);

		if (empty($matches[1]) || strpos($str, '{"') !== false)
			return $str;

        $request = request()->all();
        $find = $replace = [];

        $fields = array_unique(array_map('trim', $matches[1]));
        foreach (array_unique($matches[1]) as $i => $match)
        {
            $request_field = self::getRequestField($match);

            if ($request_field && Arr::has($request, $request_field))
                $value = Arr::get($request, $request_field);
            else if (Arr::has($input, $match))
                $value = Arr::get($input, $match);
            else {
                $failed[] = $match;
                continue;
            }

            if ($isSql && !self::is_int($value))
                $value = dbQuote($value);

            $find[] = '{'.$match.'}';
            $replace[] = $value;
        }

        return str_replace($find, $replace, $str);
    }

    /*public function callFunction($prefix, $name, $method = '', $data = [])
    {
        if (empty($name)) {
            self::raiseError("Empty class & method to call!");
        }

        @list($className, $classMethod) = $this->split($name, ':');
        $class = $this->loadClass($prefix, $className);

        $classMethod = $classMethod ?? $method;
        $classMethod = trim($classMethod);
        if (!$classMethod)
            return [$class];

        if (!method_exists($class, $classMethod)) {
            self::raiseError("$className:$classMethod - method not found!");
        }

        $result = call_user_func_array([$class, $classMethod], [$data]);

        return [$class, $result];
    }*/

	static function parseMethodName($name, $type = 'get')
	{
		if (!$type)
			return false;

		$method = explode($type, $name, 2);
		if (!empty($method[0]))
			return false;

		array_shift($method);

		$split = preg_split('/(?=[A-Z])/', $method[0], 2, PREG_SPLIT_NO_EMPTY);
		if ($split == $method)
			return $method;

		return [$method, $split];
	}

    // Define a method that will be called when a non-existing method is invoked
    public static function __callStatic($name, $arguments)
    {
    	$methods = self::parseMethodName($name, 'get');
    	if (!$methods)
    		return self::raiseError("Entity not found - '$name' with inputs: " . implode(', ', $arguments));

		$className = '';
 		foreach ($methods as $method) {
 			$method = is_array($method) ? implode("\\", $method) : $method;
 			$classMethod = self::findClass('App\DataForge\Entity', $method, ':', true);
			if ($classMethod) {
				$className = $classMethod[0];
				break;
			}
 		}

        if (!$className)
            self::raiseError(self::getError());

        $class = new $className();
        if ($item = call_user_func_array([$class, 'init'], $arguments))
            $class->bind($item);
        else {
			self::setError($method. " entity data not loaded! - for inputs: " . implode(', ', self::convertArgs($arguments)));
			return false;
        }

        // Check entity access.
        if (method_exists($class, 'access') && !$class->access()) {
            self::setError("Access invalid for (".$method. ") entity! - for inputs: " . implode(', ', self::convertArgs($arguments)));
			return false;
        }

        return $class;
    }

	public function convertArgs($input)
	{
		foreach ($input as $key => $val) {
			if (is_array($val))
				$val = json_encode($val);
			$input[$key] = $val;
		}

		return $input;
	}

    public static function classMethods($name)
    {
    	static $_methods = [];
    	if (isset($_methods[$name]))
    		return $_methods[$name];

		$methods = [];
		$reflector = new \ReflectionClass($name);
		foreach ($reflector->getMethods() as $method)
			$methods[] = $method->name;

		$_methods[$name] = $methods;

        return $_methods[$name];
    }

    public function getCURLResponse($url, $params, $headers = null, $method = '')
	{
		$curl_session = curl_init($url);

		// do we have headers? set them
		if ( isset($headers)) {
			curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
		}

		if ($params) {
			// Tell curl to use HTTP POST
			if (!$method)
				curl_setopt ($curl_session, CURLOPT_POST, true);

			// Tell curl that this is the body of the POST
			curl_setopt ($curl_session, CURLOPT_POSTFIELDS, $params);
		}

		if ($method)
			curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, $method);

		// setup the authentication
		// Tell curl not to return headers, but do return the response
		curl_setopt($curl_session, CURLOPT_HEADER, false);
		curl_setopt($curl_session, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_session, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl_session, CURLOPT_SSL_VERIFYPEER, 0);

		// curl_setopt($curl_session, CURLOPT_PROXYPORT, '3128');
		// curl_setopt($curl_session, CURLOPT_PROXYTYPE, 'HTTPS');
		// curl_setopt($curl_session, CURLOPT_PROXY, '192.168.3.1');
		// curl_setopt($curl_session, CURLOPT_PROXYUSERPWD, ':');

		$data = trim(curl_exec($curl_session));
		$error = trim(curl_error($curl_session));
		$http_code = curl_getinfo($curl_session, CURLINFO_HTTP_CODE);

		$decode = json_decode($data, true);
		if ($decode)
			$data = $decode;

		if (is_array($data) && !empty($data['error']))
			$error = $data['error'];

		if (is_array($data) && !empty($data['data']))
			$data = $data['data'];

		$response = array();
		$response['data'] = $data;
		$response['error'] = $error;

	  	return $response;
	}

    public function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function getSiteResponse($site_alias, $task_path, $params)
    {
        $site = \Sql('Sites', ['alias' => $site_alias, 'select_type' => 'item'])->assoc();
        if (empty($site['site_url']))
            self::raiseError('Unable to load site by alias');
        $client_url = rtrim($site['site_url'], '/') . '/' . ltrim($task_path, '/');
        $client_path = parse_url($client_url, PHP_URL_PATH);
        $password = self::generateRandomString(15);
        $username = md5(md5($password) . $client_path);
        $authcode = base64_encode("$username:$password");
        $client_header = ["Authorization: Basic $authcode"];
        return self::getCURLResponse($client_url, $params, $client_header);
    }

	public function Date($format = 'Y-m-d H:i:s', $tz = 'UTC')
	{
		return now()->setTimezone($tz)->format($format);
	}

    public function siteAccess($group)
    {
        $allowedGroups = [];
        $allowedGroups['propertymanager'] = [8, 25, 28, 26, 37, 39, 40];
        $allowedGroups['landlord'] = [31];
        $allowedGroups['tenant'] = [19];
        $allowedGroups['tradie'] = [21,22,23,29,30,35];

        if (empty($allowedGroups[$group]))
            return [];

        return $allowedGroups[$group];
    }
}