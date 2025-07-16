<?php

use Botble\Base\Facades\BaseHelper;
use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Botble\FreeGifts\Http\Controllers', 'middleware' => ['web', 'core']], function () {
    Route::group(['prefix' => BaseHelper::getAdminPrefix(), 'middleware' => 'auth'], function () {
        Route::group(['prefix' => 'free-gifts', 'as' => 'free-gifts.'], function () {
            Route::resource('', 'FreeGiftsController')->parameters(['' => 'free-gift']);
            
            Route::group(['prefix' => 'settings', 'as' => 'settings.'], function () {
                Route::get('', [
                    'as' => 'index',
                    'uses' => 'FreeGiftsSettingController@index',
                    'permission' => 'free-gifts.settings',
                ]);

                Route::post('', [
                    'as' => 'store',
                    'uses' => 'FreeGiftsSettingController@store',
                    'permission' => 'free-gifts.settings',
                ]);
            });
        });

        Route::group(['prefix' => 'gift-rules', 'as' => 'gift-rules.'], function () {
            Route::resource('', 'GiftRuleController')->parameters(['' => 'gift-rule']);
        });

        Route::group(['prefix' => 'manual-gifts', 'as' => 'manual-gifts.'], function () {
            Route::get('', [
                'as' => 'index',
                'uses' => 'ManualGiftController@index',
                'permission' => 'manual-gifts.index',
            ]);

            Route::post('send', [
                'as' => 'send',
                'uses' => 'ManualGiftController@send',
                'permission' => 'manual-gifts.send',
            ]);
        });
    });
});

// Front-end routes
Route::group(['namespace' => 'Botble\FreeGifts\Http\Controllers\Fronts', 'middleware' => ['web', 'core']], function () {
    Route::group(['prefix' => 'ajax', 'as' => 'public.ajax.'], function () {
        Route::group(['prefix' => 'free-gifts', 'as' => 'free-gifts.'], function () {
            Route::post('add-to-cart', [
                'as' => 'add-to-cart',
                'uses' => 'PublicFreeGiftsController@addGiftToCart',
            ]);
            
            Route::post('remove-from-cart', [
                'as' => 'remove-from-cart',
                'uses' => 'PublicFreeGiftsController@removeGiftFromCart',
            ]);
            
            Route::get('eligible-gifts', [
                'as' => 'eligible-gifts',
                'uses' => 'PublicFreeGiftsController@getEligibleGifts',
            ]);
        });
    });
});
