<?php

namespace App\Http\Controllers;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Blog\Models\Post;
use Botble\Blog\Models\Category as BlogCategory;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\ProductCategory;
use Botble\Page\Models\Page;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\URL;

class SitemapController extends Controller
{
    public function index()
    {
        // Create sitemap
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>
        <urlset
              xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
                    http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';

        // Homepage
        $sitemap .= $this->addUrl(URL::to('/'), Carbon::now()->toIso8601String(), '1.0', 'daily');

        // Static pages
        $pages = Page::where('status', BaseStatusEnum::PUBLISHED)->get();
        foreach ($pages as $page) {
            if (!in_array($page->template, ['homepage', 'full-width', 'no-sidebar'])) {
                $sitemap .= $this->addUrl(
                    $page->url,
                    $page->updated_at->toIso8601String(),
                    '0.8',
                    'weekly'
                );
            }
        }

        // Product categories
        $categories = ProductCategory::where('status', BaseStatusEnum::PUBLISHED)->get();
        foreach ($categories as $category) {
            $sitemap .= $this->addUrl(
                $category->url,
                $category->updated_at->toIso8601String(),
                '0.8',
                'weekly'
            );
        }

        // Products
        $products = Product::where('is_variation', 0)
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->get();

        foreach ($products as $product) {
            $sitemap .= $this->addUrl(
                $product->url,
                $product->updated_at->toIso8601String(),
                '0.8',
                'weekly',
                $product->images->isNotEmpty() ? [
                    'url' => RvMedia::getImageUrl($product->image),
                    'title' => $product->name,
                ] : null
            );
        }

        // Blog categories
        $blogCategories = BlogCategory::where('status', BaseStatusEnum::PUBLISHED)->get();
        foreach ($blogCategories as $category) {
            $sitemap .= $this->addUrl(
                $category->url,
                $category->updated_at->toIso8601String(),
                '0.7',
                'weekly'
            );
        }

        // Blog posts
        $posts = Post::where('status', BaseStatusEnum::PUBLISHED)->get();
        foreach ($posts as $post) {
            $sitemap .= $this->addUrl(
                $post->url,
                $post->updated_at->toIso8601String(),
                '0.7',
                'monthly',
                $post->image ? [
                    'url' => RvMedia::getImageUrl($post->image),
                    'title' => $post->name,
                ] : null
            );
        }

        $sitemap .= '</urlset>';

        return response($sitemap, 200, [
            'Content-Type' => 'application/xml'
        ]);
    }

    private function addUrl($loc, $lastmod, $priority, $changefreq, $image = null)
    {
        $url = "\n    <url>";
        $url .= "\n        <loc>" . htmlspecialchars($loc) . "</loc>";
        $url .= "\n        <lastmod>" . $lastmod . "</lastmod>";
        $url .= "\n        <priority>" . $priority . "</priority>";
        $url .= "\n        <changefreq>" . $changefreq . "</changefreq>";
        
        if ($image) {
            $url .= "\n        <image:image>";
            $url .= "\n            <image:loc>" . htmlspecialchars($image['url']) . "</image:loc>";
            if (isset($image['title'])) {
                $url .= "\n            <image:title>" . htmlspecialchars($image['title']) . "</image:title>";
            }
            $url .= "\n        </image:image>";
        }
        
        $url .= "\n    </url>";
        
        return $url;
    }
}
