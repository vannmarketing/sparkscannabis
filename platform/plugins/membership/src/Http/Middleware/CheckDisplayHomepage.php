<?php

namespace Botble\Membership\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckDisplayHomepage
{
    public function handle(Request $request, Closure $next, string $guard = 'customer')
    {
        // Bỏ qua middleware cho các route admin
        if (str_starts_with($request->path(), 'admin')) {
            return $next($request);
        }

        $currentUrlWithQuery = $request->fullUrl();
        $segments = explode('/', $currentUrlWithQuery);

        $routes = [
            'blog' => 'display_blog',
            'categories' => 'display_catalog',
            'products' => 'display_products',
            'page' => 'display_page',
            'tag' => 'display_tag',
            'home' => 'display_homepage',
        ];

        // Kiểm tra xem đang ở trang login hay register không
        $isAuthPage = in_array($request->path(), ['login', 'register']);
        $isHomePage = $request->path() === '/';

        // Check segment [3] in the URL and the settings
        if (isset($segments[3])) {
            $segment = $segments[3];
            if (array_key_exists($segment, $routes)) {
                $settingFlag = $routes[$segment];

                // Nếu setting bị tắt
                if (!setting($settingFlag, true)) {
                    if (!Auth::guard($guard)->check() && !$isAuthPage) {
                        // Nếu chưa login và không ở trang login, redirect về login
                        return redirect('/login');
                    }
                    // Nếu đã login, cho phép tiếp tục đến trang đích
                }
            }
        } else {
            // Default case for the homepage
            if (!setting('display_homepage', true)) {
                if (!Auth::guard($guard)->check() && !$isAuthPage) {
                    // Nếu chưa login và không ở trang login, redirect về login
                    return redirect('/login');
                }
                // Nếu đã login, cho phép hiển thị homepage
            }
        }

        // Process the next middleware
        $response = $next($request);

        // Inject custom CSS for login/register pages
        if (
            $isAuthPage &&
            $response->headers->get('Content-Type') === 'text/html; charset=UTF-8'
        ) {
            $content = $response->getContent();
            $customCssLink = '<link rel="stylesheet" href="' . asset('themes/serveci/css/customcss.css') . '">' . PHP_EOL;
            $content = preg_replace('/(<\/head>)/i', $customCssLink . '$1', $content, 1);
            $response->setContent($content);
        }

        // Final comprehensive check for all settings
        if (
            !setting('display_blog', true) &&
            !setting('display_catalog', true) &&
            !setting('display_products', true) &&
            !setting('display_page', true) &&
            !setting('display_tag', true) &&
            !setting('display_homepage', true)
        ) {
            if (!Auth::guard($guard)->check() && !$isAuthPage) {
                return redirect('/login');
            }
            // Nếu đã login, cho phép tiếp tục
        }

        return $response;
    }
}