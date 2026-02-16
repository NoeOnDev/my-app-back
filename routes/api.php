<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:forgot-password');
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
	Route::get('/me', [AuthController::class, 'me']);
	Route::post('/change-password', [AuthController::class, 'changePassword']);
	Route::post('/logout', [AuthController::class, 'logout']);
	Route::post('/refresh-token', [AuthController::class, 'refreshToken']);

	Route::get('/admin/ping', [AdminController::class, 'ping'])->middleware('role:admin');
	Route::get('/admin/users', [AdminController::class, 'users'])->middleware('permission:users.read');
	Route::get('/admin/roles', [AdminController::class, 'roles'])->middleware('permission:users.read');
	Route::get('/admin/permissions', [AdminController::class, 'permissions'])->middleware('permission:users.read');
	Route::post('/admin/roles', [AdminController::class, 'createRole'])->middleware('permission:users.manage');
	Route::patch('/admin/roles/{role}', [AdminController::class, 'updateRole'])->middleware('permission:users.manage');
	Route::delete('/admin/roles/{role}', [AdminController::class, 'deleteRole'])->middleware('permission:users.manage');
	Route::post('/admin/roles/{role}/sync-permissions', [AdminController::class, 'syncRolePermissions'])->middleware('permission:users.manage');
	Route::post('/admin/permissions', [AdminController::class, 'createPermission'])->middleware('permission:users.manage');
	Route::patch('/admin/permissions/{permission}', [AdminController::class, 'updatePermission'])->middleware('permission:users.manage');
	Route::delete('/admin/permissions/{permission}', [AdminController::class, 'deletePermission'])->middleware('permission:users.manage');

	Route::post('/admin/users/{user}/assign-role', [AdminController::class, 'assignRole'])->middleware('permission:users.manage');
	Route::post('/admin/users/{user}/remove-role', [AdminController::class, 'removeRole'])->middleware('permission:users.manage');
	Route::post('/admin/users/{user}/sync-roles', [AdminController::class, 'syncRoles'])->middleware('permission:users.manage');
	Route::post('/admin/users/{user}/give-permission', [AdminController::class, 'givePermission'])->middleware('permission:users.manage');
	Route::post('/admin/users/{user}/revoke-permission', [AdminController::class, 'revokePermission'])->middleware('permission:users.manage');
	Route::post('/admin/users/{user}/sync-permissions', [AdminController::class, 'syncPermissions'])->middleware('permission:users.manage');
});
