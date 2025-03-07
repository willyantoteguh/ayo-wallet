<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TopUpController;
use App\Http\Controllers\Api\TransferController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\DataPlanController;
use App\Http\Controllers\Api\OperatorCardController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\TransferHistoryController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WalletController;

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

Route::post('register', [AuthController::class, 'register']);

Route::post('login', [AuthController::class, 'login']);

Route::post('is-email-exist', [UserController::class, 'isEmailExist']);

Route::post('webhook', [WebhookController::class, 'update']);

Route::group(['middleware' => 'jwt.verify'], function ($router) {
 
    Route::post('top-up', [TopUpController::class, 'onStore']);
 
    Route::post('transfer', [TransferController::class, 'onStore']);
 
    Route::post('data_plan', [DataPlanController::class, 'onStore']);
 
    Route::get('operator_card', [OperatorCardController::class, 'index']);

    Route::get('payment_method', [PaymentMethodController::class, 'index']);

    Route::get('transfer_history', [TransferHistoryController::class, 'index']);

    Route::get('transaction', [TransactionController::class, 'index']);

    Route::get('user', [UserController::class, 'show']);
    Route::get('user/{username}', [UserController::class, 'getUserByUsername']);
    Route::put('user', [UserController::class, 'update']);

    Route::get('wallets', [WalletController::class, 'show']);
});
 