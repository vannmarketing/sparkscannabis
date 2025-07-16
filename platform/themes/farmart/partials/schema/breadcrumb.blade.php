@php
    $breadcrumbs = [];
    $position = 1;
    
    // Add home breadcrumb
    $breadcrumbs[] = [
        '@type' => 'ListItem',
        'position' => $position++,
        'name' => 'Home',
        'item' => url('/')
    ];
    
    // Add category breadcrumbs if on product page
    if (isset($product) && $product->categories->isNotEmpty()) {
        $category = $product->categories->first();
        if ($category->ancestors->isNotEmpty()) {
            foreach ($category->ancestors as $ancestor) {
                $breadcrumbs[] = [
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => $ancestor->name,
                    'item' => $ancestor->url
                ];
            }
        }
        
        // Add current category
        $breadcrumbs[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => $category->name,
            'item' => $category->url
        ];
    }
    
    // Add current page (product or category)
    if (isset($product)) {
        $breadcrumbs[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => $product->name,
            'item' => $product->url
        ];
    } elseif (isset($category) && !isset($product)) {
        $breadcrumbs[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => $category->name,
            'item' => $category->url
        ];
    }
    
    if (count($breadcrumbs) > 1) {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbs
        ];
    }
@endphp

@if (isset($schema) && count($breadcrumbs) > 1)
    <script type="application/ld+json">
        {!! json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endif
