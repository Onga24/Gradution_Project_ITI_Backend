<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminProjectController;
use App\Http\Controllers\Api\ExecutionController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\FriendController;
use App\Http\Controllers\Api\ConversationMessageController;
use App\Http\Controllers\Api\AIController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\ChatAssistantController;







Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::post('register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);

Route::post('login', [AuthController::class, 'login']);

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    

Route::middleware('auth:sanctum')->group(function () {
   
Route::post('logout', [AuthController::class, 'logout']);
Route::post('/update-profile', [AuthController::class, 'updateProfile']);
Route::get('/my-profile',[AuthController::class,'getMyProfile']);
Route::get('/me', [AuthController::class, 'getMyProfile']);

Route::post('projects', [ProjectController::class, 'store']);
Route::post('projects/join', [ProjectController::class, 'joinByInvite']);
Route::get('projects', [ProjectController::class, 'myprojects']);
    Route::put('/projects/{project}', [ProjectController::class, 'update']);   // edit
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']); // soft delete


Route::post('/projects/{project}/save-code', [ProjectController::class, 'saveCode']);
Route::get('/projects/{project}', [ProjectController::class, 'show']);

Route::post('/ai-autocomplete', [AIController::class, 'autocomplete']);


Route::post('/execute', [ExecutionController::class, 'execute'])
     ->middleware(['throttle:6,1']); 


    Route::get('/projects/{project}/messages', [MessageController::class, 'index']);
    Route::post('/projects/{project}/messages', [MessageController::class, 'store']);



    Route::get('/users/search', [UserController::class,'search']);
    Route::post('/friends', [FriendController::class,'store']);
    Route::get('/conversations/{conversation}', [ConversationController::class,'show']);
    Route::post('/conversations/{conversation}/messages', [ConversationMessageController::class,'store']);

    Route::get('/projects/{projectId}/files', [FileController::class, 'getProjectFiles']);
    Route::post('/projects/{projectId}/files', [FileController::class, 'saveProjectFiles']);
    Route::post('/projects/{project}/files/upload', [FileController::class, 'store']);
    Route::delete('/projects/{project}/files/{file}', [FileController::class, 'destroy']);
    Route::delete('/projects/{project}/files', [FileController::class, 'destroyMultiple']);
    Route::get('/projects/{project}', [FileController::class, 'show']);
    Route::post('/ai/chat', [ChatAssistantController::class, 'handleChat']);


});


Route::prefix('admin')->middleware(['auth:sanctum','is_admin'])->group(function () {
    // Users
    Route::get('users', [AdminUserController::class, 'index']);
    Route::get('users/{user}', [AdminUserController::class, 'show']);
    Route::put('users/{user}', [AdminUserController::class, 'update']); 
    Route::delete('users/{user}', [AdminUserController::class, 'destroy']); 
    Route::post('users/{id}/restore', [AdminUserController::class, 'restore']);
    Route::delete('users/{id}/force', [AdminUserController::class, 'forceDelete']);
    Route::get('dashboard', [AdminUserController::class, 'stats']);



    // Projects
    Route::get('projects', [AdminProjectController::class, 'index']);
    Route::get('projects/{project}', [AdminProjectController::class, 'show']);
    Route::put('projects/{project}', [AdminProjectController::class, 'update']);
    Route::delete('projects/{project}', [AdminProjectController::class, 'destroy']); 
    Route::post('projects/{id}/restore', [AdminProjectController::class, 'restore']);
    Route::delete('projects/{id}/force', [AdminProjectController::class, 'forceDelete']);
});



