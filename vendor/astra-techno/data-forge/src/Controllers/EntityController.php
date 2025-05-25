<?php

namespace AstraTech\DataForge\Controllers;

use Illuminate\Http\Request;
use AstraTech\DataForge\Base\DataForge;

class EntityController extends Controller
{
	public function fetch(Request $request, $name, $key = '')
	{
        $entity = call_user_func_array(['DataForge', 'get'.$name], ($key ? ['id' => $key] : []));
        if (!$entity) {
        	return response([
				'message' => DataForge::getError()
			], 403);
        }

        $base = (int) request('baseAttrib', 1);
        $attribGroup = request('group');
        $attribs = request('attrib');

        if ($attribGroup)
            return response($entity->toGroupArray($attribGroup, $attribs, $base));

		return response($entity->toArray($attribs, $base));
	}
}