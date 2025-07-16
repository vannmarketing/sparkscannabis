<?php

namespace Botble\MixAndMatch\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Ecommerce\Models\Product;
use Botble\Media\Facades\RvMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MixAndMatchController extends BaseController
{
    /**
     * Search for products to add to a Mix and Match container
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchProducts(Request $request)
    {
        try {
            $keyword = $request->input('keyword');
            $excludeIds = $request->input('exclude');

            if (!$keyword) {
                return response()->json([
                    'error' => true,
                    'message' => trans('plugins/mix-and-match::mix-and-match.please_enter_keyword'),
                ]);
            }

            $products = Product::query()
                ->wherePublished()
                ->where(function ($query) use ($keyword) {
                    $query->where('name', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('sku', 'LIKE', '%' . $keyword . '%');
                });

            if ($excludeIds) {
                if (is_array($excludeIds)) {
                    $products->whereNotIn('id', $excludeIds);
                } else {
                    $products->whereNotIn('id', [$excludeIds]);
                }
            }

            $products = $products->limit(20)->get();
            
            $data = [];
            foreach ($products as $product) {
                // Get variation attributes if this is a variation
                $attributeInfo = '';
                if ($product->is_variation) {
                    $attributeInfo = $this->getVariationAttributeInfo($product->id);
                }
                
                // Create display name - always include attributes in the name
                $displayName = $product->name;
                if (!empty($attributeInfo)) {
                    // If name doesn't already contain the attributes in parentheses
                    if (strpos($displayName, '(' . $attributeInfo . ')') === false) {
                        $displayName .= ' (' . $attributeInfo . ')';
                    }
                }
                
                // Add the product to results
                $data[] = [
                    'id' => $product->id,
                    'name' => $displayName, // Use display name with attributes included
                    'image' => RvMedia::getImageUrl($product->image, 'thumb', false, RvMedia::getDefaultImage()),
                    'price_html' => format_price($product->front_sale_price),
                    'type' => $product->product_type,
                    'is_variation' => $product->is_variation ? 1 : 0,
                    'sku' => $product->sku
                ];
            }
            
            // Debug log for search results
            Log::info('Search results', ['data' => $data]);

            return response()->json([
                'error' => false,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Get attribute information for a variation product
     *
     * @param int $variationId
     * @return string|null
     */
    protected function getVariationAttributeInfo($variationId)
    {
        try {
            // APPROACH 1: Direct query for variation attributes
            $attributes = DB::table('ec_product_variation_items as pvi')
                ->join('ec_product_attributes as pa', 'pa.id', '=', 'pvi.attribute_id')
                ->join('ec_product_attribute_sets as pas', 'pas.id', '=', 'pa.attribute_set_id')
                ->where('pvi.variation_id', $variationId)
                ->select('pas.title as attribute_set', 'pa.title as attribute_value')
                ->get();
            
            if ($attributes && count($attributes) > 0) {
                $result = [];
                foreach ($attributes as $attribute) {
                    $result[] = $attribute->attribute_set . ': ' . $attribute->attribute_value;
                }
                
                // Log successful attribute retrieval
                Log::info('Found attributes via direct query', [
                    'variation_id' => $variationId,
                    'attributes' => $result
                ]);
                
                return implode(', ', $result);
            }
            
            // APPROACH 2: Get via product model relationships
            $product = Product::find($variationId);
            if ($product && $product->is_variation) {
                // Try to get attributes from the product
                if (method_exists($product, 'variationProductAttributes') && $product->variationProductAttributes) {
                    $attributes = $product->variationProductAttributes;
                    if ($attributes && $attributes->count() > 0) {
                        $attributeInfo = [];
                        foreach ($attributes as $attribute) {
                            $attributeInfo[] = $attribute->attribute_set_title . ': ' . $attribute->title;
                        }
                        
                        // Log successful attribute retrieval
                        Log::info('Found attributes via model relationship', [
                            'variation_id' => $variationId,
                            'attributes' => $attributeInfo
                        ]);
                        
                        return implode(', ', $attributeInfo);
                    }
                }
                
                // APPROACH 3: Try to extract from product name if it contains parentheses
                if (preg_match('/\(([^\)]+)\)/', $product->name, $matches)) {
                    Log::info('Found attributes in parentheses', [
                        'variation_id' => $variationId,
                        'product_name' => $product->name,
                        'attributes' => $matches[1]
                    ]);
                    return $matches[1];
                }
                
                // APPROACH 4: Try to extract from name difference with parent
                if ($product->configurable_product_id) {
                    $parent = Product::find($product->configurable_product_id);
                    if ($parent) {
                        $diff = str_replace($parent->name, '', $product->name);
                        $diff = trim($diff);
                        if (!empty($diff)) {
                            // Clean up the diff if it starts with a dash or similar
                            $diff = ltrim($diff, '- ');
                            Log::info('Found attributes via name difference', [
                                'variation_id' => $variationId,
                                'product_name' => $product->name,
                                'parent_name' => $parent->name,
                                'attributes' => $diff
                            ]);
                            return $diff;
                        }
                    }
                }
            }
            
            // APPROACH 5: Try to get from product variations table
            $variation = DB::table('ec_product_variations')
                ->where('id', $variationId)
                ->first();
                
            if ($variation && $variation->product_id) {
                $variationProduct = Product::find($variation->product_id);
                if ($variationProduct) {
                    // Extract from name if it contains parentheses
                    if (preg_match('/\(([^\)]+)\)/', $variationProduct->name, $matches)) {
                        Log::info('Found attributes via variation product name', [
                            'variation_id' => $variationId,
                            'product_name' => $variationProduct->name,
                            'attributes' => $matches[1]
                        ]);
                        return $matches[1];
                    }
                }
            }
            
            // No attributes found
            Log::warning('No attributes found for variation', ['variation_id' => $variationId]);
            return null;
        } catch (\Exception $e) {
            report($e);
            Log::error('Error getting variation attributes', [
                'variation_id' => $variationId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
