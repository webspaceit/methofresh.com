<?php

declare(strict_types=1);

/**
 * Front controller — every request passes through here.
 */

require_once dirname(__DIR__) . '/app/wsit_bootstrap.php';

use App\Core\wsit_Router;
use App\Controllers\wsit_AuthController;
use App\Controllers\wsit_CartController;
use App\Controllers\wsit_CategoryController;
use App\Controllers\wsit_CheckoutController;
use App\Controllers\wsit_ContactController;
use App\Controllers\wsit_ErrorController;
use App\Controllers\wsit_HomeController;
use App\Controllers\wsit_OrderController;
use App\Controllers\wsit_ProductController;
use App\Controllers\wsit_AdminController;
use App\Controllers\wsit_AdminProductController;
use App\Controllers\wsit_AdminCategoryController;
use App\Controllers\wsit_AdminPageController;
use App\Controllers\wsit_PageController;

$router = app()->router;

// ---- Storefront ----
$router->get('/',                        [wsit_HomeController::class, 'index']);
$router->post('/',                       [wsit_HomeController::class, 'newsletter']);
$router->get('/products',                [wsit_ProductController::class, 'index']);
$router->get('/products/load-more',      [wsit_ProductController::class, 'loadMore']);
$router->get('/products/{slug}',         [wsit_ProductController::class, 'show']);
$router->get('/search/suggest',          [wsit_ProductController::class, 'suggest']);
$router->get('/categories',              [wsit_CategoryController::class, 'index']);
$router->get('/categories/{slug}',       [wsit_CategoryController::class, 'show']);
$router->get('/pages/{slug}',            [wsit_PageController::class, 'show']);

// ---- Cart ----
$router->get('/cart',                    [wsit_CartController::class, 'index']);
$router->post('/cart/add',               [wsit_CartController::class, 'add']);
$router->post('/cart/update',            [wsit_CartController::class, 'update']);
$router->post('/cart/remove',            [wsit_CartController::class, 'remove']);
$router->post('/cart/clear',             [wsit_CartController::class, 'destroy']);
$router->post('/cart/coupon',            [wsit_CartController::class, 'applyCoupon']);

// ---- Checkout ----
$router->get('/checkout',                [wsit_CheckoutController::class, 'show']);
$router->post('/checkout',               [wsit_CheckoutController::class, 'place']);
$router->get('/order/success/{number}',  [wsit_CheckoutController::class, 'success']);

// ---- Contact ----
$router->get('/contact',                  [wsit_ContactController::class, 'show']);
$router->post('/contact/submit',          [wsit_ContactController::class, 'submit']);
$router->get('/contact/captcha',          [wsit_ContactController::class, 'captchaImage']);

// ---- Auth ----
$router->get('/login',                   [wsit_AuthController::class, 'showLogin']);
$router->post('/login',                  [wsit_AuthController::class, 'login']);
$router->get('/register',                [wsit_AuthController::class, 'showRegister']);
$router->post('/register',               [wsit_AuthController::class, 'register']);
$router->get('/logout',                  [wsit_AuthController::class, 'logout']);

// ---- Account ----
$router->get('/account',                 [wsit_OrderController::class, 'account']);
$router->get('/account/orders/{id}',     [wsit_OrderController::class, 'show']);

// ---- Admin ----
$router->get('/mf-dashboard',                              [wsit_AdminController::class, 'dashboard']);
$router->get('/mf-dashboard/settings',                     [wsit_AdminController::class, 'settings']);
$router->post('/mf-dashboard/settings',                    [wsit_AdminController::class, 'updateSettings']);
$router->post('/mf-dashboard/settings/logo',               [wsit_AdminController::class, 'updateLogo']);
$router->post('/mf-dashboard/settings/favicon',            [wsit_AdminController::class, 'updateFavicon']);
$router->post('/mf-dashboard/settings/captcha',            [wsit_AdminController::class, 'updateContactCaptcha']);
$router->post('/mf-dashboard/settings/contact-email',      [wsit_AdminController::class, 'updateContactEmail']);
$router->get('/mf-dashboard/homepage',                     [wsit_AdminController::class, 'homepage']);
$router->post('/mf-dashboard/homepage',                    [wsit_AdminController::class, 'updateHomepage']);
$router->get('/mf-dashboard/orders',                       [wsit_AdminController::class, 'orders']);
$router->get('/mf-dashboard/orders/search',                [wsit_AdminController::class, 'ordersJson']);
$router->get('/mf-dashboard/orders/{id}',                  [wsit_AdminController::class, 'orderShow']);
$router->post('/mf-dashboard/orders/{id}/status',          [wsit_AdminController::class, 'updateStatus']);
$router->get('/mf-dashboard/products',                     [wsit_AdminProductController::class, 'index']);
$router->get('/mf-dashboard/products/search',              [wsit_AdminProductController::class, 'searchJson']);
$router->get('/mf-dashboard/products/create',              [wsit_AdminProductController::class, 'create']);
$router->post('/mf-dashboard/products',                    [wsit_AdminProductController::class, 'store']);
$router->get('/mf-dashboard/products/{id}/edit',           [wsit_AdminProductController::class, 'edit']);
$router->post('/mf-dashboard/products/{id}/update',        [wsit_AdminProductController::class, 'update']);
$router->post('/mf-dashboard/products/{id}/delete',        [wsit_AdminProductController::class, 'destroy']);
$router->post('/mf-dashboard/products/{id}/toggle',        [wsit_AdminProductController::class, 'toggle']);
$router->post('/mf-dashboard/products/{id}/featured',      [wsit_AdminProductController::class, 'toggleFeatured']);
$router->post('/mf-dashboard/products/{id}/new-arrival',   [wsit_AdminProductController::class, 'toggleNewArrival']);
$router->get('/mf-dashboard/categories',                   [wsit_AdminCategoryController::class, 'index']);
$router->get('/mf-dashboard/categories/create',            [wsit_AdminCategoryController::class, 'create']);
$router->post('/mf-dashboard/categories',                  [wsit_AdminCategoryController::class, 'store']);
$router->get('/mf-dashboard/categories/{id}/edit',         [wsit_AdminCategoryController::class, 'edit']);
$router->post('/mf-dashboard/categories/{id}/update',      [wsit_AdminCategoryController::class, 'update']);
$router->post('/mf-dashboard/categories/{id}/delete',      [wsit_AdminCategoryController::class, 'destroy']);
$router->post('/mf-dashboard/categories/{id}/toggle',      [wsit_AdminCategoryController::class, 'toggle']);
$router->get('/mf-dashboard/pages',                        [wsit_AdminPageController::class, 'index']);
$router->get('/mf-dashboard/pages/create',                 [wsit_AdminPageController::class, 'create']);
$router->post('/mf-dashboard/pages',                       [wsit_AdminPageController::class, 'store']);
$router->get('/mf-dashboard/pages/{id}/edit',              [wsit_AdminPageController::class, 'edit']);
$router->post('/mf-dashboard/pages/{id}/update',           [wsit_AdminPageController::class, 'update']);
$router->post('/mf-dashboard/pages/{id}/delete',           [wsit_AdminPageController::class, 'destroy']);
$router->post('/mf-dashboard/pages/{id}/toggle',           [wsit_AdminPageController::class, 'toggle']);
$router->get('/mf-dashboard/footer',                       [wsit_AdminController::class, 'footer']);
$router->post('/mf-dashboard/footer',                      [wsit_AdminController::class, 'updateFooter']);
$router->get('/mf-dashboard/payment-methods',              [wsit_AdminController::class, 'paymentMethods']);
$router->post('/mf-dashboard/payment-methods',             [wsit_AdminController::class, 'updatePaymentMethods']);
$router->post('/mf-dashboard/translate',                   [wsit_AdminController::class, 'translate']);

// ---- Errors ----
$router->get('/404',                               [wsit_ErrorController::class, 'notFound']);
$router->get('/403',                               [wsit_ErrorController::class, 'forbidden']);

$app->run();