<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SubcategoryController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\RequestAccessController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/login', [AuthController::class, 'login']);
Route::post('/request-access', [RequestAccessController::class, 'store']);

// Authenticated
Route::middleware(['auth:sanctum', 'organization.active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::middleware('password.changed')->group(function () {

        // ---- Organizations (Super Admin only) ----
        Route::middleware('role:super-admin')->group(function () {
            Route::apiResource('organizations', OrganizationController::class);
            Route::post('/group-admins', [UserController::class, 'storeGroupAdmin']);
            Route::post('/users', [UserController::class, 'storeUser']);
        });

        // ---- Group Members (Group Admin only) ----
        Route::middleware('role:group-admin')->group(function () {
            Route::get('/group-members', [UserController::class, 'groupMembers']);
            Route::post('/group-members', [UserController::class, 'storeGroupMember']);

            Route::patch('/organizations/name', [OrganizationController::class, 'updateOwnName']);
        });

        // ---- Users ----
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);

        // ---- Products, Categories, Subcategories, Brands ----
        Route::middleware('role:super-admin|group-admin|group-member')->group(function () {
            Route::apiResource('products', ProductController::class);
            Route::apiResource('categories', CategoryController::class);
            Route::apiResource('subcategories', SubcategoryController::class);
            Route::apiResource('brands', BrandController::class);
        });
    });
});