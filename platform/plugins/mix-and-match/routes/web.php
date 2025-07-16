<?php

use Botble\Base\Facades\BaseHelper;
use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Botble\MixAndMatch\Http\Controllers', 'middleware' => ['web', 'core']], function () {
    Route::group(['prefix' => BaseHelper::getAdminPrefix(), 'middleware' => 'auth'], function () {
        Route::group(['prefix' => 'mix-and-match', 'as' => 'mix-and-match.'], function () {
            Route::get('search-products', [
                'as' => 'search-products',
                'uses' => 'MixAndMatchController@searchProducts',
                'permission' => 'products.index',
            ]);
        });
    });
    
    // Frontend routes
    Route::post('mix-and-match/add-to-cart', [
        'as' => 'public.mix-and-match.add-to-cart',
        'uses' => 'MixAndMatchCartController@addToCart',
    ]);
    
    // Debug route
    Route::get('mix-and-match/debug-cart', [
        'as' => 'public.mix-and-match.debug-cart',
        'uses' => 'MixAndMatchCartController@debugCart',
    ]);
});
