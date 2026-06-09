<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\HomepageBentoController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1'); // 5 attempts per minute per IP
    Route::post('/google', [AuthController::class, 'googleAuth']);
    
    // Email verification routes
    Route::get('/verify/{id}/{hash}', [AuthController::class, 'verify'])
        ->middleware(['signed'])
        ->name('verification.verify');
    
    // Password reset routes
    Route::post('/password/email', [AuthController::class, 'sendPasswordResetLink']);
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    
    // Cart routes
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::put('/cart/items/{id}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{id}', [CartController::class, 'removeItem']);
    
    // User profile routes
    Route::get('/user/profile', [AuthController::class, 'getProfile']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    
    // User address routes
    Route::prefix('user/addresses')->group(function () {
        Route::get('/', [AddressController::class, 'index']);
        Route::post('/', [AddressController::class, 'store']);
        Route::put('/{id}', [AddressController::class, 'update']);
        Route::delete('/{id}', [AddressController::class, 'destroy']);
        Route::put('/{id}/default', [AddressController::class, 'setDefault']);
    });
    
    // Order routes
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/orders/{id}/cancel', [OrderController::class, 'cancel']);
    
    // Admin order routes
    Route::put('/admin/orders/{id}/status', [OrderController::class, 'updateStatus']);
    
    // Payment routes
    Route::post('/payments/razorpay/create', [OrderController::class, 'createRazorpayOrder']);
});

// Legacy route (can be removed later)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public catalog routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);
Route::get('/homepage-bento', [HomepageBentoController::class, 'index']);

// Newsletter routes (public, no authentication required)
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe']);

// Contact form route
Route::post('/contact', [ContactController::class, 'submit']);

// Webhook routes (no authentication required - webhooks come from Razorpay servers)
Route::post('/webhooks/razorpay', [WebhookController::class, 'razorpay']);
