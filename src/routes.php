<?php

use Illuminate\Support\Facades\Route;
use Lenius\LaravelEcommerce\Http\Controllers\EcommerceController;

Route::group(['middleware' => ['web']], function () {
    EcommerceController::routes();
});
