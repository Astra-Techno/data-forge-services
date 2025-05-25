<?php

namespace AstraTech\DataForge\Base;

class Task extends ClassObject
{
   final function load($name, $input)
   {
		if (empty($name))
			return $this;

		@list($className, $method) = DataForge::findClass('App\DataForge\Task', $name);

		$classMethod = $method ?? 'default';
		$classMethod = trim($classMethod);

		$class = new $className();

		if (!method_exists($class, $classMethod)) {
			$className = ltrim($className, 'App\DataForge\\');
            $classMethod = ltrim($classMethod, 'Guest');

			$msg = ltrim($className, '\Task').'/'.$classMethod;
            if (config('app.debug'))
            	$msg = $className.':'.$classMethod;

			$this->raiseError("$msg - method not found!");
		}

		return call_user_func_array([$class, $classMethod], [&$input]);
   }
}