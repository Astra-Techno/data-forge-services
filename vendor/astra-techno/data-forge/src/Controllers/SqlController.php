<?php

namespace AstraTech\DataForge\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SqlController extends Controller
{
    function getSqlName($path)
    {
        if (strpos($path, ':') === false)
            $path .= ':default';

        return str_replace("/", '\\', $path);
    }

    public function getData(Request $request, $path)
    {
        $classMethod = $this->getSqlName($path);

        $data = \Sql($classMethod)->assocList();
        return response($data);
    }

    public function list(Request $request, $path)
    {
        $classMethod = $this->getSqlName($path);
        $select = request('select', 'list');
        $page = (int) request('page');
        $limit = (int) request('limit');
        $limit = $limit ? $limit : 20;

        $obj1 = \Sql($classMethod, ['select_type' => 'total']);
        $obj2 = \Sql($classMethod, ['select_type' => $select, 'page' => $page, 'limit' => $limit]);

        if (debugMode())
            $out = "Total:\n" . $obj1 . " \n\n\nList:\n" . $obj2;
        else
            $out = ['total' => $obj1->result(), 'items' => $obj2->assocList()];

        return response($out);
    }

    public function item(Request $request, $path)
    {
        $classMethod = $this->getSqlName($path);
        $select = request('select', 'list');
        $obj = \Sql($classMethod, ['select_type' => $select]);

        if (debugMode())
            $out = (string) $obj;
        else
            $out = $obj->assoc();

        return response($out);
    }

    public function all(Request $request, $path)
    {
        $classMethod = $this->getSqlName($path);
        $select = request('select', 'list');
	$limit = (int) request('limit');
	
	$args = ['select_type' => $select];
	if ($limit > 0)
		$args['limit'] = $limit;
		
        $obj = \Sql($classMethod, $args);

        if (debugMode())
            $out = (string) $obj;
        else
            $out = $obj->assocList();

        return response($out);
    }

    public function groupedList(Request $request, $path)
    {
        $classMethod = $this->getSqlName($path);
        $select = request('select', 'list');
        $group_label = request('group_label', 'group_label');

        $page = (int) request('page');
        $limit = (int) request('limit');
        $limit = $limit ? $limit : 20;

        $obj1 = \Sql($classMethod, ['select_type' => 'total']);
        $obj2 = \Sql($classMethod, ['select_type' => 'group_total']);
        $obj3 = \Sql($classMethod, ['select_type' => $select, 'page' => $page, 'limit' => $limit]);

        if (debugMode())
            $out = "Total:\n" . $obj1 . " \n\n\nGroup Total:\n" . $obj2 . " \n\n\nList:\n" . $obj3;
        else {
            $items = $this->buildGroupedList($obj3->assocList(), $group_label);
            $out = ['total' => $obj1->result(), 'group_total' => $obj2->objectList('group_label'), 'items' => $items];
        }

        return response($out);
    }

    public function buildGroupedList($items, $group_label)
    {
        $out = [];
        if (!$items)
            return $out;

        foreach ($items as $item)
            $out[$item[$group_label]][] = $item;

        return $out;
    }

    public function options(Request $request, $path)
    {
        $classMethod = $this->getSqlName($path);
        $select = request('select', 'list');
        $obj = \Sql($classMethod, ['select_type' => $select]);

        if (debugMode())
            $out = (string) $obj;
        else
            $out = $obj->assocList();

        $out = $this->buildHierarchy($out);
        return response($this->buildOptions($out));
    }

    function buildHierarchy(array $elements, $parentId = 0)
    {
        $branch = array();

        foreach ($elements as $element) {
            if ($element['parent_id'] > 0)
                $element['value'] = $element['parent_id'] . ':' . $element['value'];

            if ($element['parent_id'] == $parentId) {
                $children = $this->buildHierarchy($elements, $element['id']);
                if ($children)
                    $element['children'] = $children;
                $branch[] = $element;
            }
        }

        // Sort the branch: move elements with 'subordinates' to the bottom
        usort($branch, function ($a, $b) {
            $aHasSubordinates = isset($a['children']) && count($a['children']) > 0;
            $bHasSubordinates = isset($b['children']) && count($b['children']) > 0;

            return $aHasSubordinates <=> $bHasSubordinates;
        });

        return $branch;
    }

    function buildOptions(array $elements, $level = 0, $spaceMultiplier = 4)
    {
        $branch = [];

        foreach ($elements as $element) {
            // Calculate the number of spaces based on the level
            $spaces = str_repeat('  ', $level * $spaceMultiplier);
            //$spaces = '';

            // Modify the name to include the spaces
            $label = $spaces . htmlspecialchars($element['label']);

            // Add level to indicate depth if needed elsewhere
            $element['level'] = $level;

            $option = ['label' => $label, 'level' => $level, 'value' => $element['value']];

            // Recursively build the child elements
            $children = [];
            if (!empty($element['children'])) {
                $option = ['group' => $label, 'level' => $level, 'value' => $element['value']];
                $children = $this->buildOptions($element['children'], $level + 1, $spaceMultiplier);
            }

            // Add the current element to the branch
            $branch[] = $option;

            // Merge the children into the branch
            if ($children)
                $branch = array_merge($branch, $children);
        }

        return $branch;
    }


    public function field(Request $request, $path)
    {
        $classMethod = $this->getSqlName($path);
        $select = request('select', 'list');
        $obj = \Sql($classMethod, ['select_type' => $select]);

        if (debugMode())
            $out = (string) $obj;
        else
            $out = $obj->result();

        return response($out);
    }

    public function logout()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->currentAccessToken()->delete();

        return response('', 204);
    }
}