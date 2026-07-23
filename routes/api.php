<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\InquiryController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\SupplierDashboardController;
use App\Http\Controllers\Api\GeneralPagesController;
use App\Http\Controllers\Api\ContactRevealController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==========================================
// 1. Authentication Routes
// ==========================================
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
Route::post('/register/user', [AuthController::class, 'registerUser'])->middleware('throttle:auth');
Route::post('/register/buyer', [AuthController::class, 'registerBuyer'])->middleware('throttle:auth');

// ==========================================
// 2. Catalog & Search Routes
// ==========================================
Route::middleware('throttle:api')->group(function () {
    Route::get('/popular-products', [CatalogController::class, 'getPopularProducts']);
    Route::get('/categories', [CatalogController::class, 'getCategories']);
    Route::get('/products', [CatalogController::class, 'getProducts']);
    Route::get('/products/autocomplete', [CatalogController::class, 'autocomplete']);
    Route::get('/products/{slug}', [CatalogController::class, 'getProductDetail']);
    Route::get('/suppliers', [CatalogController::class, 'getSuppliers']);
    Route::get('/supplier-countries', [CatalogController::class, 'getSupplierCountries']);
    Route::get('/suppliers/{id}', [CatalogController::class, 'getSupplierDetail']);
    Route::get('/rfqs/leads', [InquiryController::class, 'getRfqs']);
    Route::get('/leads', [InquiryController::class, 'getRfqs']);
});

// ==========================================
// 3. Inquiries & RFQs (Buying Requests)
// ==========================================
Route::middleware('throttle:inquiries')->group(function () {
    Route::post('/inquiries', [InquiryController::class, 'submitInquiry']);
    Route::post('/inquiries/general', [InquiryController::class, 'submitGeneralInquiry']);
    Route::post('/rfq', [InquiryController::class, 'submitRfq']);
});

// ==========================================
// 4. Blog & Marketing Pages Routes
// ==========================================
Route::middleware('throttle:api')->group(function () {
    Route::get('/blogs', [GeneralPagesController::class, 'getBlogs']);
    Route::get('/blogs/{slug}', [GeneralPagesController::class, 'getBlogDetail']);
});

Route::middleware('throttle:inquiries')->group(function () {
    Route::post('/complaints', [GeneralPagesController::class, 'submitComplaint']);
    Route::post('/reviews', [GeneralPagesController::class, 'submitReview']);
    Route::post('/wta-applications', [GeneralPagesController::class, 'submitWtaApplication']);
});

// ==========================================
// 5. Protected Routes (Requires Sanctum Auth)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth & Profile
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [SupplierDashboardController::class, 'getProfile']);
    Route::put('/profile', [SupplierDashboardController::class, 'updateProfile']);
    Route::get('/supplier/dashboard', [SupplierDashboardController::class, 'getMetrics']);
    
    // Supplier Product Management
    Route::get('/my-products', [SupplierDashboardController::class, 'getProducts']);
    Route::post('/my-products', [SupplierDashboardController::class, 'storeProduct']);
    Route::put('/my-products/{id}', [SupplierDashboardController::class, 'updateProduct']);
    Route::delete('/my-products/{id}', [SupplierDashboardController::class, 'deleteProduct']);
    
    // Chat & Direct Messaging
    Route::get('/inbox', [ChatController::class, 'getConversations']);
    Route::get('/inbox/{partnerId}/{partnerType}', [ChatController::class, 'getMessages']);
    Route::post('/inbox/send', [ChatController::class, 'sendMessage']);
    
    // RFQs (Leads) for suppliers
    Route::get('/my-inquiries', [InquiryController::class, 'getBuyerInquiries']);
    Route::post('/reveal-contact', [ContactRevealController::class, 'reveal']);
});
