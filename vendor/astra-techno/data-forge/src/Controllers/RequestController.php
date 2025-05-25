<?php
namespace AstraTech\DataForge\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class RequestController extends Controller
{
    /**
     * Method used to dynamically load controller inside App\Http\Controllers folder.
     *
     * This method is used to return a controller object for a specific
     * controller name.
     *
     * @param string $controller The name of the controller.
     * @return controller Object
     */
    public function getController($controller)
    {
        // Dynamically determine the full controller class name
        $controllerClass = "App\\Http\\Controllers\\" . ucfirst($controller) . "Controller";
        if (class_exists($controllerClass))
            return App::make($controllerClass);

        $this->setError('Controller not found!');
        return false;
    }

    public function call($controller, $method, $argument)
    {
        if (!method_exists($controller, $method)) {
            $this->setError('Method not found!');
            return false;
        }

        $out = $controller->$method($argument);
        $error = $controller->getError();
        if ($error)
            $this->setError($error['message'], $error['code']);

        return $out;
    }

    public function callAfter($controller, $method, $isList, $rows, $total = 0)
    {
        $method = 'format'.ucfirst($method);
        if (!method_exists($controller, $method))
            return $total > 0 ? [$rows, $total] : $rows;

        $select = request('select', 'list');
        if (!$isList)
            return $controller->$method($rows, $select);

        $out = [];
        foreach ($rows as $row) {
            if ($row = $controller->$method($row, $select))
                $out[] = $row;
        }

        return $total > 0 ? [$out, count($out)] : $out;
    }

    /**
     * Handle a request to fetch a list of records with total count for pagination.
     *
     * This method is used to return a collection of records for a specific
     * controller and method as per the dynamic query generation.
     *
     * @param string $controller The name of the controller.
     * @param string $method The method of the controller.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function list($controller, $method, Request $request)
    {
        $controller = $this->getController($controller);
        if (!$controller)
            return $this->response();

        $query = $this->call($controller, $method, $request);
        if (!$query)
            return $this->response();

        $total = $this->dbResult($query->get('total'));
        if ($total === false)
            return $this->response();

        $query->set('page', $request->get('page', 1));
        $query->set('limit', $request->get('limit', 10));
        
        $rows = $this->dbObjects($query->get($request->get('select', 'list')));

        if ($rows === false)
            return $this->response();

        $tmp = $this->callAfter($controller, $method, true, $rows, $total);
        if ($tmp && is_array($tmp)) {
            $rows = $tmp[0];
            $total = $tmp[1];
        } else {
            $rows = $tmp;
            $total = 0;
        }

        return $this->response(['total' => $total, 'items' => $rows]);
    }

    /**
     * Handle a request to fetch a list of all records.
     *
     * This method is used to return a collection of records for a specific
     * controller and method as per the dynamic query generation.
     *
     * @param string $controller The name of the controller.
     * @param string $method The method of the controller.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function all($controller, $method, Request $request)
	{
        $controller = $this->getController($controller);
        if (!$controller)
            return $this->response();

        $query = $this->call($controller, $method, $request);
        if (!$query)
            return $this->response();

        $rows = $this->dbObjects($query->get($request->get('select', 'list')));
        if ($rows === false)
            return $this->response();

        $rows = $this->callAfter($controller, $method, true, $rows);

        return $this->response($rows);
	}

    /**
     * Handle a request to fetch a record.
     *
     * This method is used to return a collection of records for a specific
     * controller and method as per the dynamic query generation.
     *
     * @param string $controller The name of the controller.
     * @param string $method The method of the controller.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function item($controller, $method, Request $request)
	{
        $controller = $this->getController($controller);
        if (!$controller)
            return $this->response();

        $query = $this->call($controller, $method, $request);
        if (!$query)
            return $this->response();

        $row = $this->dbObject($query->get($request->get('select', 'list')));
        $row = $this->callAfter($controller, $method, false, $row);

        return $this->response($row);
	}

    /**
     * Handle a request to do custom data fetching or action.
     *
     * This method is used to return a collection of records for a specific
     * controller and method as per the dynamic query generation.
     *
     * @param string $controller The name of the controller.
     * @param string $method The method of the controller.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function task($controller, $method, Request $request)
	{
        $controller = $this->getController($controller);
        if (!$controller)
            return $this->response();

        $out = $this->call($controller, $method, $request);
        if (!$out)
            return $this->response();

        return $this->response($out);
	}
}
