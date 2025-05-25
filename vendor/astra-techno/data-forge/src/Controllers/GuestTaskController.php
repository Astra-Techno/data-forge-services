<?php

namespace AstraTech\DataForge\Controllers;

use Illuminate\Http\Request;

class GuestTaskController extends Controller
{
	public function action(Request $request, $name, $method = 'default')
	{
		$data = \Task($name.':Guest'.$method, $request);
		return response($data);
	}
}
