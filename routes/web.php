<?php

use App\Http\Controllers\ShopController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\InquiryController;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\AdminOnly;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/lt');
Route::get('/admin/login',[AdminController::class,'loginForm'])->middleware(\App\Http\Middleware\AdminLocale::class)->name('login');
Route::post('/admin/login',[AdminController::class,'login'])->middleware([\App\Http\Middleware\AdminLocale::class,'throttle:6,1']);
Route::post('/admin/language', function (\Illuminate\Http\Request $request) {
    $data = $request->validate(['language'=>'required|in:lt,en,ru']);
    session(['admin_locale'=>$data['language']]);
    return back();
})->name('admin.language');
Route::prefix('admin')->middleware([\App\Http\Middleware\AdminLocale::class,AdminOnly::class])->group(function () {
    Route::get('/',[AdminController::class,'index'])->name('admin');
    Route::post('/logout',[AdminController::class,'logout'])->name('logout');
    Route::get('/listings/create',[AdminController::class,'edit'])->name('admin.create');
    Route::get('/listings/{listing}/edit',[AdminController::class,'edit'])->name('admin.edit');
    Route::post('/listings',[AdminController::class,'save'])->name('admin.store');
    Route::put('/listings/{listing}',[AdminController::class,'save'])->name('admin.update');
    Route::delete('/listings/{listing}',[AdminController::class,'archive'])->name('admin.archive');
    Route::post('/listings/{id}/restore',[AdminController::class,'restore'])->whereNumber('id')->name('admin.restore');
    Route::get('/inquiries/{inquiry}',[AdminController::class,'inquiry'])->name('admin.inquiry');
    Route::put('/inquiries/{inquiry}',[AdminController::class,'updateInquiry'])->name('admin.inquiry.update');
    Route::get('/inquiries/{inquiry}/photos/{index}',[AdminController::class,'photo'])->whereNumber('index')->name('admin.inquiry.photo');
    Route::get('/orders/{order}',[AdminController::class,'order'])->name('admin.order');
    Route::put('/orders/{order}',[AdminController::class,'updateOrder'])->name('admin.order.update');
});
Route::prefix('{locale}')->where(['locale'=>'lt|en|ru'])->middleware(SetLocale::class)->group(function () {
    Route::get('/',[ShopController::class,'home'])->name('home');
    Route::view('/contact','contact')->name('contact');
    Route::view('/privacy','privacy')->name('privacy');
    Route::view('/inquiry-received','inquiry-thanks')->name('inquiry.thanks');
    Route::get('/{kind}',[InquiryController::class,'form'])->where('kind','request-part|sell-car')->name('inquiry.form');
    Route::post('/{kind}',[InquiryController::class,'store'])->where('kind','request-part|sell-car')->middleware('throttle:5,1')->name('inquiry.store');
    Route::get('/basket',[ShopController::class,'basket'])->name('basket');
    Route::put('/basket',[ShopController::class,'updateBasket'])->name('basket.update');
    Route::post('/basket/{listing}',[ShopController::class,'add'])->name('basket.add');
    Route::post('/order-request',[ShopController::class,'submit'])->middleware('throttle:5,1')->name('order.submit');
    Route::view('/thank-you','thanks')->name('thanks');
    Route::get('/listing/{listing}',[ShopController::class,'show'])->name('listing');
    Route::get('/{section}',[ShopController::class,'catalog'])->where('section','parts|rentals|services|dismantling|cars-for-sale')->name('catalog');
});
