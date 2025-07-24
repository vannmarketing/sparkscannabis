<?php

use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// Add this to your routes/web.php file for health checks

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

/**
 * Health check endpoint for Coolify
 * This route will verify that all essential services are working
 */
Route::get('/health', function () {
    $health = [
        'status' => 'OK',
        'timestamp' => now()->toISOString(),
        'app' => config('app.name'),
        'environment' => config('app.env'),
        'version' => '1.0.0', // Update this as needed
        'services' => []
    ];

    try {
        // Check database connection
        DB::connection()->getPdo();
        $health['services']['database'] = 'OK';
    } catch (Exception $e) {
        $health['services']['database'] = 'FAILED: ' . $e->getMessage();
        $health['status'] = 'DEGRADED';
    }

    try {
        // Check Redis connection
        Redis::ping();
        $health['services']['redis'] = 'OK';
    } catch (Exception $e) {
        $health['services']['redis'] = 'FAILED: ' . $e->getMessage();
        $health['status'] = 'DEGRADED';
    }

    try {
        // Check cache functionality
        Cache::put('health_check', 'test', 60);
        $cached = Cache::get('health_check');
        if ($cached === 'test') {
            $health['services']['cache'] = 'OK';
        } else {
            $health['services']['cache'] = 'FAILED: Cache test failed';
            $health['status'] = 'DEGRADED';
        }
    } catch (Exception $e) {
        $health['services']['cache'] = 'FAILED: ' . $e->getMessage();
        $health['status'] = 'DEGRADED';
    }

    try {
        // Check storage permissions
        if (is_writable(storage_path('logs'))) {
            $health['services']['storage'] = 'OK';
        } else {
            $health['services']['storage'] = 'FAILED: Storage not writable';
            $health['status'] = 'DEGRADED';
        }
    } catch (Exception $e) {
        $health['services']['storage'] = 'FAILED: ' . $e->getMessage();
        $health['status'] = 'DEGRADED';
    }

    // Add cannabis-specific health checks if needed
    try {
        // Example: Check if cannabis license verification service is working
        // $licenseService = app(LicenseVerificationService::class);
        // if ($licenseService->isHealthy()) {
        //     $health['services']['license_verification'] = 'OK';
        // }
    } catch (Exception $e) {
        // $health['services']['license_verification'] = 'FAILED: ' . $e->getMessage();
        // $health['status'] = 'DEGRADED';
    }

    // Return appropriate HTTP status code
    $statusCode = $health['status'] === 'OK' ? 200 : 503;

    return response()->json($health, $statusCode);
});

/**
 * Simple ping endpoint for basic connectivity checks
 */
Route::get('/ping', function () {
    return response()->json([
        'message' => 'pong',
        'timestamp' => now()->toISOString()
    ]);
});

/**
 * Deep health check with more detailed information (admin only)
 */
Route::get('/health/deep', function () {
    // Add authentication if needed
    // $this->middleware('auth:admin');

    $health = [
        'status' => 'OK',
        'timestamp' => now()->toISOString(),
        'app' => [
            'name' => config('app.name'),
            'environment' => config('app.env'),
            'debug' => config('app.debug'),
            'url' => config('app.url'),
            'timezone' => config('app.timezone'),
        ],
        'php' => [
            'version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
        ],
        'laravel' => [
            'version' => app()->version(),
        ],
        'services' => [],
        'disk_usage' => [],
        'queue_stats' => []
    ];

    // Database detailed check
    try {
        $dbConnection = DB::connection();
        $health['services']['database'] = [
            'status' => 'OK',
            'driver' => $dbConnection->getDriverName(),
            'database' => $dbConnection->getDatabaseName(),
            'tables_count' => count(DB::select('SHOW TABLES'))
        ];
    } catch (Exception $e) {
        $health['services']['database'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
        $health['status'] = 'DEGRADED';
    }

    // Redis detailed check
    try {
        $redisInfo = Redis::info();
        $health['services']['redis'] = [
            'status' => 'OK',
            'version' => $redisInfo['redis_version'] ?? 'unknown',
            'memory_used' => $redisInfo['used_memory_human'] ?? 'unknown',
            'connected_clients' => $redisInfo['connected_clients'] ?? 'unknown'
        ];
    } catch (Exception $e) {
        $health['services']['redis'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
        $health['status'] = 'DEGRADED';
    }

    // Disk usage check
    $storagePath = storage_path();
    if (function_exists('disk_free_space')) {
        $freeBytes = disk_free_space($storagePath);
        $totalBytes = disk_total_space($storagePath);
        $health['disk_usage'] = [
            'free' => $freeBytes ? round($freeBytes / 1024 / 1024 / 1024, 2) . ' GB' : 'unknown',
            'total' => $totalBytes ? round($totalBytes / 1024 / 1024 / 1024, 2) . ' GB' : 'unknown',
            'used_percentage' => $totalBytes ? round((($totalBytes - $freeBytes) / $totalBytes) * 100, 2) . '%' : 'unknown'
        ];
    }

    $statusCode = $health['status'] === 'OK' ? 200 : 503;
    return response()->json($health, $statusCode);
});

// Sitemap route
Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');

