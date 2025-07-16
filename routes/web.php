<?php

use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// Sitemap route
Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');

