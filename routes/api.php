<?php

use Dingo\Api\Routing\Router as Router;

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\Auth\VerificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

$api = app(Router::class);

$api->version('v1', function ($api) {
    $api->group(['middleware' => 'auth:api'], function ($api) {
        $api->post('logout', [LoginController::class, 'logout']);
    
        $api->get('user', [UserController::class, 'current']);
    });

    $api->group(['middleware' => 'guest:api'], function ($api) {
        $api->post('login', [LoginController::class, 'login'])->name('login');
        $api->post('register', [RegisterController::class, 'register']);
    
        $api->post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail']);
        $api->post('password/reset', [ResetPasswordController::class, 'reset']);
    
        $api->post('email/verify/{user}', [VerificationController::class, 'verify'])->name('verification.verify');
        $api->post('email/resend', [VerificationController::class, 'resend']);
    });
});
