@if (isset($category) && $category->id)
    @php
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $category->name,
            'description' => Str::limit(trim(strip_tags($category->description)), 300),
            'url' => $category->url,
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListElement' => []
            ]
        ];

        // Add products if available
        if (isset($products) && $products->count() > 0) {
            foreach ($products->take(10) as $index => $product) {
                $schema['mainEntity']['itemListElement'][] = [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'item' => [
                        '@type' => 'Product',
                        'name' => $product->name,
                        'url' => $product->url,
                        'image' => RvMedia::getImageUrl($product->image, null, false, RvMedia::getDefaultImage()),
                        'offers' => [
                            '@type' => 'Offer',
                            'price' => $product->price,
                            'priceCurrency' => get_application_currency()->title,
                            'availability' => $product->isOutOfStock() ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock',
                        ]
                    ]
                ];
            }
        }
    @endphp

    <script type="application/ld+json">
        {!! json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endif
