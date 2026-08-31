<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\EmployeeDocumentController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\PermissionRequestController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\SickLeaveController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VacationRequestController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard', DashboardController::class);
    Route::get('/reports', ReportController::class);
    Route::get('/company', [CompanyController::class, 'show']);
    Route::put('/company', [CompanyController::class, 'update']);
    Route::apiResource('employees', EmployeeController::class);
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('positions', PositionController::class);
    Route::apiResource('attendances', AttendanceController::class);
    Route::apiResource('vacation-requests', VacationRequestController::class);
    Route::apiResource('permission-requests', PermissionRequestController::class);
    Route::apiResource('sick-leaves', SickLeaveController::class);
    Route::apiResource('employee-documents', EmployeeDocumentController::class);
    Route::apiResource('shifts', ShiftController::class);
    Route::apiResource('audit-logs', AuditLogController::class)->only(['index', 'show']);
    Route::apiResource('roles', RoleController::class)->only(['index', 'store', 'update']);
    Route::apiResource('users', UserController::class)->only(['index', 'store', 'update']);
    Route::get('/exports/{resource}.{format}', ExportController::class)
        ->whereIn('resource', ['employees', 'attendances', 'vacation-requests', 'permission-requests', 'sick-leaves', 'employee-documents', 'audit-logs'])
        ->whereIn('format', ['csv', 'pdf']);
});
