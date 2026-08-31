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
    // Sin permiso: cualquier usuario autenticado.
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Cada recurso exige el permiso Spatie correspondiente (mismo mapa que el
    // menu del frontend). `can:` responde 403 si el usuario no lo tiene.
    Route::get('/dashboard', DashboardController::class)->middleware('can:dashboard.view');
    Route::get('/reports', ReportController::class)->middleware('can:reports.view');

    Route::get('/company', [CompanyController::class, 'show'])->middleware('can:settings.manage');
    Route::put('/company', [CompanyController::class, 'update'])->middleware('can:settings.manage');

    Route::apiResource('employees', EmployeeController::class)->middleware('can:employees.manage');
    Route::apiResource('departments', DepartmentController::class)->middleware('can:settings.manage');
    Route::apiResource('positions', PositionController::class)->middleware('can:settings.manage');
    Route::apiResource('attendances', AttendanceController::class)->middleware('can:attendance.manage');
    Route::apiResource('vacation-requests', VacationRequestController::class)->middleware('can:requests.approve');
    Route::apiResource('permission-requests', PermissionRequestController::class)->middleware('can:requests.approve');
    Route::apiResource('sick-leaves', SickLeaveController::class)->middleware('can:requests.approve');
    Route::apiResource('employee-documents', EmployeeDocumentController::class)->middleware('can:documents.manage');
    Route::apiResource('shifts', ShiftController::class)->middleware('can:attendance.manage');
    Route::apiResource('audit-logs', AuditLogController::class)->only(['index', 'show'])->middleware('can:audit.view');
    Route::apiResource('roles', RoleController::class)->only(['index', 'store', 'update'])->middleware('can:roles.manage');
    Route::apiResource('users', UserController::class)->only(['index', 'store', 'update'])->middleware('can:users.manage');

    // El permiso por recurso se valida dentro del controlador.
    Route::get('/exports/{resource}.{format}', ExportController::class)
        ->whereIn('resource', ['employees', 'attendances', 'vacation-requests', 'permission-requests', 'sick-leaves', 'employee-documents', 'audit-logs'])
        ->whereIn('format', ['csv', 'pdf']);
});
