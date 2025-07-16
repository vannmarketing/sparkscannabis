<?php

use Botble\Ecommerce\Models\Product;
use Botble\MixAndMatch\Models\MixAndMatchSetting;

if (!function_exists('is_mix_and_match_product')) {
    /**
     * Check if a product is a Mix and Match product
     *
     * @param Product|int $product
     * @return bool
     */
    function is_mix_and_match_product($product): bool
    {
        if (!$product) {
            return false;
        }

        if (is_numeric($product)) {
            $product = Product::find($product);
        }

        if (!$product) {
            return false;
        }

        return $product->mixAndMatchSetting()->exists();
    }
}

if (!function_exists('get_mix_and_match_setting')) {
    /**
     * Get Mix and Match settings for a product
     *
     * @param Product|int $product
     * @return MixAndMatchSetting|null
     */
    function get_mix_and_match_setting($product): ?MixAndMatchSetting
    {
        if (!$product) {
            return null;
        }

        if (is_numeric($product)) {
            $product = Product::find($product);
        }

        if (!$product) {
            return null;
        }

        return $product->mixAndMatchSetting;
    }
}
