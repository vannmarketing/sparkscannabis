@if (isset($product) && $product->id)
    @php
        // Ensure we have the required review data loaded
        $product->loadMissing(['reviews' => function($query) {
            $query->where('status', 'approved')
                  ->with('user')
                  ->latest()
                  ->limit(5);
        }]);

        // Base product schema
        $schema = [
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => Str::limit(trim(strip_tags($product->description)), 300),
            'image' => RvMedia::getImageUrl($product->image, null, false, RvMedia::getDefaultImage()),
            'mpn' => $product->sku ?? $product->id,
            'sku' => $product->sku ?? $product->id,
            'brand' => [
                '@type' => 'Brand',
                'name' => $product->brand ? $product->brand->name : 'Sparks Cannabis'
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => $product->url,
                'priceCurrency' => get_application_currency()->title,
                'price' => number_format((float)$product->front_sale_price, 2, '.', ''),
                'priceValidUntil' => now()->addYear()->format('Y-m-d'),
                'itemCondition' => 'https://schema.org/NewCondition',
                'availability' => $product->isOutOfStock() ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock',
                'seller' => [
                    '@type' => 'Organization',
                    'name' => 'Sparks Cannabis',
                    'url' => url('/')
                ]
            ]
        ];

        // Add aggregate rating if reviews are enabled and exist
        if (EcommerceHelper::isReviewEnabled() && $product->reviews_count > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => number_format((float)$product->reviews_avg, 1, '.', ''),
                'reviewCount' => (int)$product->reviews_count,
                'bestRating' => '5',
                'worstRating' => '1',
                'ratingCount' => (int)$product->reviews_count
            ];
        }

        // Add review data if available
        if (EcommerceHelper::isReviewEnabled() && $product->reviews_count > 0 && $product->reviews->isNotEmpty()) {
            $reviews = [];
            
            foreach ($product->reviews as $review) {
                if ($review->status === 'approved' && !empty($review->comment)) {
                    $reviewData = [
                        '@type' => 'Review',
                        'reviewRating' => [
                            '@type' => 'Rating',
                            'ratingValue' => (string)$review->star,
                            'bestRating' => '5',
                            'worstRating' => '1',
                        ],
                        'author' => [
                            '@type' => 'Person',
                            'name' => $review->user ? e($review->user->name) : 'Anonymous',
                        ],
                        'datePublished' => $review->created_at->toIso8601String(),
                        'description' => e($review->comment),
                        'publisher' => [
                            '@type' => 'Organization',
                            'name' => 'Sparks Cannabis',
                            'url' => url('/')
                        ]
                    ];
                    
                    $reviews[] = $reviewData;
                }
            }
            
            if (!empty($reviews)) {
                $schema['review'] = $reviews;
            }
        }
    @endphp

    <script type="application/ld+json">
        {!! json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endif
