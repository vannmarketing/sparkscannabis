<x-core::form.on-off.checkbox
    name="display_homepage"
    :label="trans('plugins/membership::base.settings.enable_homepage')"
    :checked="setting('display_homepage', true)"
    :description="trans('plugins/membership::base.settings.enable_homepage')"
    class="mb-3 mt-0"
    :wrapper="false"
/>
<x-core::form.on-off.checkbox
    name="display_blog"
    :label="trans('plugins/membership::base.settings.enable_blog')"
    :checked="setting('display_blog', true)"
    :description="trans('plugins/membership::base.settings.enable_blog')"
    class="mb-3 mt-0"
    :wrapper="false"
/>
<x-core::form.on-off.checkbox
    name="display_catalog"
    :label="trans('plugins/membership::base.settings.enable_catalog')"
    :checked="setting('display_catalog', true)"
    :description="trans('plugins/membership::base.settings.enable_catalog')"
    class="mb-3 mt-0"
    :wrapper="false"
/>

<x-core::form.on-off.checkbox
    name="display_products"
    :label="trans('plugins/membership::base.settings.enable_products')"
    :checked="setting('display_products', true)"
    :description="trans('plugins/membership::base.settings.enable_products')"
    class="mb-3 mt-0"
    :wrapper="false"
/>
<x-core::form.on-off.checkbox
    name="display_page"
    :label="trans('plugins/membership::base.settings.enable_page')"
    :checked="setting('display_page', true)"
    :description="trans('plugins/membership::base.settings.enable_page')"
    class="mb-3 mt-0"
    :wrapper="false"
/>
<x-core::form.on-off.checkbox
    name="display_tag"
    :label="trans('plugins/membership::base.settings.enable_tag')"
    :checked="setting('display_tag', true)"
    :description="trans('plugins/membership::base.settings.enable_tag')"
    class="mb-3 mt-0"
    :wrapper="false"
/>

<!-- <x-core::form.fieldset
    class="blog_post_schema_type mt-3"
    data-bb-value="1"
    @style(['display: none' => !setting('blog_post_schema_enabled', true)])
>
    <x-core::form.select
        name="blog_post_schema_type"
        :label="trans('plugins/membership::base.settings.schema_type')"
        :options="[
            'NewsArticle' => 'NewsArticle',
            'News' => 'News',
            'Article' => 'Article',
            'BlogPosting' => 'BlogPosting',
        ]"
        :value="setting('blog_post_schema_type', 'NewsArticle')"
    />
</x-core::form.fieldset> -->
