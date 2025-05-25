<?php

namespace AstraTech\DataForge\Controllers;

use Illuminate\Http\Request;

class TaskController extends Controller
{
	public function action(Request $request, $name, $method = 'default')
	{
		$data = \Task($name.':'.$method, $request);
		return response($data);
	}
}
