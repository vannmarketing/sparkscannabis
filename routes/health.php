<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function (Request $request) {
    try {
        // Check database connection
        \DB::connection()->getPdo();
        
        return response()->json([
            'status' => 'ok',
            'services' => [
                'database' => 'connected',
            ],
            'timestamp' => now()->toDateTimeString(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'error' => $e->getMessage(),
            'timestamp' => now()->toDateTimeString(),
        ], 500);
    }
});
