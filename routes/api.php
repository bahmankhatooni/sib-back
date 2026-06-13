<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TargetController;
use App\Http\Controllers\Api\ProgramController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ============================================================
// مسیرهای عمومی (نیاز به احراز هویت ندارند)
// ============================================================
Route::post('login', [AuthController::class, 'login']);

// ============================================================
// مسیرهای محافظت شده (نیاز به توکن دارند)
// ============================================================
Route::middleware('auth:sanctum')->group(function () {

    // --------------------------------------------------------
    // احراز هویت (همه کاربران لاگین شده)
    // --------------------------------------------------------
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    // --------------------------------------------------------
    // مسیرهای مختص مدیر سیستم (ADMIN)
    // این مسیرها فقط توسط کاربران با نقش ADMIN قابل دسترسی است
    // --------------------------------------------------------
    Route::middleware('check.role:ADMIN')->group(function () {

        // واحدها (Units)
        Route::apiResource('units', UnitController::class);

        // نقش‌ها (Roles)
        Route::apiResource('roles', RoleController::class);

        // کاربران (Users)
        Route::apiResource('users', UserController::class);
        Route::patch('users/{id}/toggle-status', [UserController::class, 'toggleStatus']);

        // اهداف (Targets)
        Route::apiResource('targets', TargetController::class);
    });

    // --------------------------------------------------------
    // مسیرهای قابل دسترسی برای همه کاربران لاگین شده
    // (ادمین و ثبات واحد)
    // --------------------------------------------------------

    // برنامه‌ها (Programs)
    Route::apiResource('programs', ProgramController::class);

    // اقدامات (Tasks)
    Route::apiResource('tasks', TaskController::class);

    // فعالیت‌ها (Activities)
    Route::apiResource('activities', ActivityController::class);

    // کاربرگ‌ها (Forms)
    Route::apiResource('forms', FormController::class);
    Route::get('forms/{id}/fields', [FormController::class, 'getFields']);
    Route::post('forms/{id}/fields', [FormController::class, 'saveFields']);
    Route::get('forms/{id}/form', [FormController::class, 'getForm']);
    Route::post('forms/{id}/form', [FormController::class, 'saveForm']);
    // در بخش مسیرهای محافظت شده
    Route::get('forms/{id}/export', [FormController::class, 'export']);
    Route::post('forms/import', [FormController::class, 'import']);

    // گزارشات (Reports)
    Route::prefix('reports')->group(function () {
        Route::get('statistics', [ReportController::class, 'statistics']);
        Route::get('list', [ReportController::class, 'list']);
        Route::get('details/{id}', [ReportController::class, 'details']);
        Route::get('export', [ReportController::class, 'export']);
    });
    //پروفایل (Profile)
    Route::post('/change-password', [UserController::class, 'changePassword']);
});
