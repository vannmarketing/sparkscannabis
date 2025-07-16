@php
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => theme_option('site_title'),
        'url' => url('/'),
        'logo' => RvMedia::getImageUrl(theme_option('logo')),
        'description' => theme_option('seo_description'),
        'sameAs' => array_filter([
            theme_option('facebook'),
            theme_option('twitter'),
            theme_option('instagram'),
            theme_option('youtube'),
            theme_option('linkedin'),
        ]),
        'contactPoint' => [
            '@type' => 'ContactPoint',
            'telephone' => theme_option('hotline'),
            'contactType' => 'customer service',
            'availableLanguage' => 'English',
            'areaServed' => 'US',
        ]
    ];

    // Add address if available
    if (theme_option('address')) {
        $schema['address'] = [
            '@type' => 'PostalAddress',
            'streetAddress' => theme_option('address'),
            'addressLocality' => theme_option('city'),
            'addressRegion' => theme_option('state'),
            'postalCode' => theme_option('zip_code'),
            'addressCountry' => 'US',
        ];
    }

    // Add opening hours if available
    if (theme_option('working_hours')) {
        $schema['openingHoursSpecification'] = [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            'opens' => '09:00',
            'closes' => '21:00',
        ];
    }
@endphp

<script type="application/ld+json">
    {!! json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
</script>
