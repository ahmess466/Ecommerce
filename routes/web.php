<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WishListController;
use App\Http\Middleware\AuthAdmin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });


Auth::routes();

Route::get('/', [HomeController::class, 'index'])->name('home.index');
Route::get('/shop',[ShopController::class,'index'])->name('shop.index');
// Route::get('/shop/{product_slug}',[ShopController::class,'productDetails'])->name('shop.product.details');
Route::get('/shop/{id}', [ShopController::class, 'productDetails'])->name('shop.product.details');
Route::get('/cart',[CartController::class,'index'])->name('cart.index');
Route::post('/cart/add',[CartController::class,'add_to_cart'])->name('cart.add');
Route::put('/cart/increase-quantity/(rowId)',[CartController::class,'increase_cart_quantity'])->name('cart.qty.increase');
Route::put('/cart/decrease-quantity/(rowId)',[CartController::class,'decrease_cart_quantity'])->name('cart.qty.decrease');
Route::delete('/cart/remove/{rowId}',[CartController::class ,'remove_item'])->name('cart.item.remove');
Route::delete('/cart/clear',[CartController::class ,'empty_cart'])->name('cart.empty');

Route::post('/cart/apply-coupon',[CartController::class,'apply_coupon_code'])->name('cart.coupon.apply');
Route::delete('/cart/coupon-remove',[CartController::class,'remove_coupon_code'])->name('cart.coupon.remove');

Route::get('/checkout',[CartController::class ,'checkout'])->name('cart.checkout');
Route::post('/place-an-order',[CartController::class,'place_an_order'])->name('cart.place.an.order');
Route::get('/order_confirmation',[CartController::class,'order_confirmation'])->name('cart.order.confirmation');


Route::post('/wishlist/add',[WishListController::class , 'add_to_wishlist'])->name('wishlist.add');
Route::get('/wishlist',[WishListController::class,'index'])->name('wishlist.index');
Route::delete('/wishlist/remove/{rowId}',[WishListController::class ,'remove_from_wishlist'])->name('wishlist.items.remove');
Route::delete('/wishlist/clear',[WishListController::class ,'empty_wishList'])->name('wishlist.items.clear');
Route::post('/wishlist/move-to-cart/{rowId}',[WishListController::class,'move_to_cart'])->name('wishlist.move.to.cart');

Route::get('/contact-us',[HomeController::class,'contact'])->name('home.contact');
Route::post('/contact-us/store',[HomeController::class,'contact_store'])->name('home.contact.store');

Route::get('/search', [HomeController::class, 'search'])->name('home.search');
Route::get('/about-us',[HomeController::class,'aboutUs'])->name('home.about');



Route::middleware(['auth'])->group(function () {
    Route::get('/acount-dashboard', [UserController::class, 'index'])->name('user.index');
    Route::get('/user/orders',[UserController::class,'orders'])->name('user.orders');
    Route::get('/acount-order/{order_id}/details',[UserController::class ,'order_details'])->name('user.order.details');
    Route::put('/acount/order/cancel-order',[UserController::class,'order_cancel'])->name('user.order.cancel');
});
Route::middleware(['auth', AuthAdmin::class])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');

    Route::get('/admin/brands', [AdminController::class, 'brands'])->name('admin.brands');
    Route::get('/admin/brands-add', [AdminController::class, 'addBrand'])->name('admin.brands.add');
    Route::post('/admin/brand/store', [AdminController::class, 'brand_store'])->name('admin.brand.store');
    Route::get('/admin/brand/edit/{id}', [AdminController::class, 'brand_edit'])->name('admin.brand.edit');
    Route::put('/admin/brand/update/{id}', [AdminController::class, 'brand_update'])->name('admin.brand.update');
    Route::delete('/admin/brand/delete/{id}', [AdminController::class, 'brand_destroy'])->name('admin.brand.delete');


    Route::get('/admin/categories', [AdminController::class, 'categories'])->name('admin.categories');
    Route::get('/admin/categories-add', [AdminController::class, 'addCategory'])->name('admin.categories.add');
    Route::post('/admin/categories/store', [AdminController::class, 'category_store'])->name('admin.categories.store');
    Route::get('/admin/categories/edit/{id}', [AdminController::class, 'category_edit'])->name('admin.categories.edit');
    Route::put('/admin/categories/update/{id}', [AdminController::class, 'category_update'])->name('admin.categories.update');
    Route::delete('/admin/categories/delete/{id}', [AdminController::class, 'category_destroy'])->name('admin.categories.delete');


    Route::get('/admin/products',[AdminController::class,'products'])->name('admin.products');
    Route::get('/admin/products-add',[AdminController::class,'addProduct'])->name('admin.products.add');
    Route::post('/admin/product/store',[AdminController::class,'storeProduct'])->name('admin.product.store');
    Route::get('/admin/product/edit/{id}',[AdminController::class,'editProduct'])->name('admin.products.edit');
    Route::put('/admin/product/update/{id}',[AdminController::class,'updateProduct'])->name('admin.products.update');
    Route::delete('/admin/product/delete/{id}',[AdminController::class,'destroyProduct'])->name('admin.products.destroy');

    Route::get('/admin/coupons',[AdminController::class ,'coupons'])->name('admin.coupons');
    Route::get('/admin/coupons-add',[AdminController::class ,'coupon_add'])->name('admin.coupon.add');
    Route::post('/admin/coupon/store',[AdminController::class ,'coupon_store'])->name('admin.coupon.store');
    Route::get('/admin/coupon/edit/{id}',[AdminController::class ,'coupon_edit'])->name('admin.coupon.edit');
    Route::put('/admin/coupon/update/{id}',[AdminController::class ,'coupon_update'])->name('admin.coupon.update');
    Route::delete('/admin/coupon/delete/{id}',[AdminController::class ,'coupon_delete'])->name('admin.coupon.delete');

    Route::get('/admin/orders',[AdminController::class,'orders'])->name('admin.orders');
    Route::get('/admin/order/details/{order_id}',[AdminController::class,'order_details'])->name('admin.order.details');
    Route::put('/admin/order/status',[AdminController::class,'updateOrderStatus'])->name('admin.order.status.update');

    Route::get('/admin/slides',[AdminController::class,'slides'])->name('admin.slides');
    Route::get('/admin/slide-add',[AdminController::class,'addSlide'])->name('admin.add.slide');
    Route::post('/admin/slide/store',[AdminController::class,'slideStore'])->name('admin.slide.store');
    Route::get('/admin/slide/edit/{id}',[AdminController::class,'editSlide'])->name('admin.slide.edit');
    Route::put('/admin/slide/update/{id}',[AdminController::class,'updateSlide'])->name('admin.slide.update');
    Route::delete('/admin/slide/delete/{id}',[AdminController::class,'deleteSlide'])->name('admin.slide.delete');

    Route::get('/admin/contact',[AdminController::class,'contacts'])->name('admin.contacts');
    Route::delete('/admin/contact/{id}/delete',[AdminController::class , 'removeContact'])->name('admin.contact.delete');

    Route::get('/admin/search',[AdminController::class,'search'])->name('admin.search');
});
