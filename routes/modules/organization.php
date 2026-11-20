<?php

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\BranchController;
use App\Controllers\DepartmentController;
use App\Controllers\EmployeeController;
use App\Controllers\UserController;
use App\Controllers\RoleController;

return static function (Router $r): void {
    // Branches
    $r->get('/organization', [BranchController::class, 'index']);
    $r->post('/organization', [BranchController::class, 'store']);
    $r->post('/organization/{id}/update', [BranchController::class, 'update']);

    // Departments
    $r->get('/departments', [DepartmentController::class, 'index']);
    $r->post('/departments', [DepartmentController::class, 'store']);
    $r->post('/departments/{id}/update', [DepartmentController::class, 'update']);

    // Employees
    $r->get('/employees', [EmployeeController::class, 'index']);
    $r->post('/employees', [EmployeeController::class, 'store']);
    $r->post('/employees/{id}/update', [EmployeeController::class, 'update']);

    // Users & Branch Scopes
    $r->get('/users', [UserController::class, 'index']);
    $r->post('/users', [UserController::class, 'store']);
    $r->post('/users/{id}/update', [UserController::class, 'update']);

    // Roles & Permission Matrix
    $r->get('/roles', [RoleController::class, 'index']);
    $r->post('/roles', [RoleController::class, 'store']);
    $r->post('/roles/{id}/permissions', [RoleController::class, 'updatePermissions']);
};
