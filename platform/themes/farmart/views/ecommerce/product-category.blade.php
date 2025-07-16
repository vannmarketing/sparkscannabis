@php
   Theme::set('pageDescription', $category->description);
   
   // Set the current category for schema
   $currentCategory = $category;
   
   // Register breadcrumbs if the Breadcrumbs class is available
   if (class_exists('Breadcrumbs') && method_exists('Breadcrumbs', 'register')) {
       Breadcrumbs::register('category', function ($breadcrumbs) use ($currentCategory) {
           // Add home breadcrumb
           $breadcrumbs->push(trans('plugins/ecommerce::products.categories'), route('public.index'));
           
           // Add ancestor categories if any
           if ($currentCategory->ancestors->isNotEmpty()) {
               foreach ($currentCategory->ancestors as $ancestor) {
                   $breadcrumbs->push($ancestor->name, $ancestor->url);
               }
           }
           
           // Add current category
           $breadcrumbs->push($currentCategory->name, null, [], false);
       });
   }
@endphp

@includeIf('schema::breadcrumb')
@includeIf('schema::product-category')

@include(Theme::getThemeNamespace('views.ecommerce.products'))
