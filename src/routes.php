<?php

use Illuminate\Support\Facades\Route;
use Lenius\LaravelEcommerce\Controllers\EcommerceController;

Route::group(['middleware' => ['web']], function () {
    EcommerceController::routes();
});
