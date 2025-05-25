<?php

use AstraTech\DataForge\Controllers\RequestController;
use AstraTech\DataForge\Controllers\EntityController;
use AstraTech\DataForge\Controllers\SqlController;
use AstraTech\DataForge\Controllers\TaskController;
use AstraTech\DataForge\Controllers\GuestTaskController;

use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
	Route::middleware(['auth:sanctum'])->group(function () {
		$urlMatch = '[a-zA-Z0-9]+(/[a-zA-Z0-9]+)?(:[a-zA-Z0-9]+)?';
		Route::get('list/{path}', [SqlController::class, 'list'])->where('path', $urlMatch);        // http://localhost:8000/api/list/Users/all
		Route::get('group-list/{path}', [SqlController::class, 'groupedList'])->where('path', $urlMatch);        // http://localhost:8000/api/group-list/Users/all
		Route::get('item/{path}', [SqlController::class, 'item'])->where('path', $urlMatch);        // http://localhost:8000/api/item/Users/all
		Route::get('field/{path}', [SqlController::class, 'field'])->where('path', $urlMatch);        // http://localhost:8000/api/count/Users/all
		Route::get('all/{path}', [SqlController::class, 'all'])->where('path', $urlMatch);        // http://localhost:8000/api/all/Users/all
		Route::get('options/{path}', [SqlController::class, 'options'])->where('path', $urlMatch);    // http://localhost:8000/api/options/Users/all

		Route::post('list/{path}', [SqlController::class, 'list'])->where('path', $urlMatch);        // http://localhost:8000/api/list/Users/all
		Route::post('group-list/{path}', [SqlController::class, 'groupedList'])->where('path', $urlMatch);        // http://localhost:8000/api/group-list/Users/all
		Route::post('item/{path}', [SqlController::class, 'item'])->where('path', $urlMatch);        // http://localhost:8000/api/item/Users/all
		Route::post('field/{path}', [SqlController::class, 'field'])->where('path', $urlMatch);        // http://localhost:8000/api/count/Users/all
		Route::post('all/{path}', [SqlController::class, 'all'])->where('path', $urlMatch);        // http://localhost:8000/api/all/Users/all
		Route::post('options/{path}', [SqlController::class, 'options'])->where('path', $urlMatch);        // http://localhost:8000/api/options/Users/all

		Route::get('task/{name}/{method}', [TaskController::class, 'action']);        // http://localhost:8000/api/all/Users/all
		Route::post('task/{name}/{method}', [TaskController::class, 'action']);        // http://localhost:8000/api/all/Users/all
		Route::get('entity/{name}/{key?}', [EntityController::class, 'fetch']);    // http://localhost:8000/api/entity/User/{id}
		Route::post('entity/{name}/{key?}', [EntityController::class, 'fetch']);   // http://localhost:8000/api/entity/User/{email}
	});


	Route::get('guest-task/{name}/{method}', [GuestTaskController::class, 'action']);        // http://localhost:8000/api/guest-task/Users/all
	Route::post('guest-task/{name}/{method}', [GuestTaskController::class, 'action']);		// http://localhost:8000/api/guest-task/Users/all
});
