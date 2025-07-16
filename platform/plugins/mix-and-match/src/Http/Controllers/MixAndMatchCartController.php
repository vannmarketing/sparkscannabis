<?php

namespace Botble\MixAndMatch\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Facades\Cart;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Facades\OrderHelper;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Exceptions\ProductIsNotActivatedYetException;
use Botble\Media\Facades\RvMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Theme;

class MixAndMatchCartController extends BaseController
{
    /**
     * Add mix and match product to cart
     *
     * @param Request $request
     * @return BaseHttpResponse
     */
    public function addToCart(Request $request)
    {
        try {
            $debugData = [
                'timestamp' => date('Y-m-d H:i:s'),
                'all_data' => $request->all(),
                'id' => $request->input('id'),
                'qty' => $request->input('qty'),
                'mix_and_match' => $request->input('mix_and_match'),
                'request_method' => $request->method(),
                'request_url' => $request->url()
            ];

            file_put_contents(
                storage_path('logs/mix_match_debug.log'), 
                json_encode($debugData, JSON_PRETTY_PRINT) . "\n\n",
                FILE_APPEND
            );
            
            // Also try standard logging
            Log::info('Mix and Match cart request data', $debugData);
            
            $productId = $request->input('id');
            $qty = $request->input('qty', 1);
            
            $product = Product::query()->find($productId);
            
            if (!$product) {
                return $this->httpResponse()
                    ->setError()
                    ->setMessage(__('This product is out of stock or not exists!'));
            }

            // Process mix and match inputs
            $mixAndMatchInputs = $request->input('mix_and_match', []);
            $selectedItems = [];
            $itemsText = "Selected Items:\n";

            foreach ($mixAndMatchInputs as $childId => $childQty) {
                if (empty($childQty) || (int)$childQty <= 0) {
                    continue;
                }

                $childProduct = Product::find($childId);
                if (!$childProduct) {
                    continue;
                }

                // Get variation info if applicable
                $attributeText = '';
                
                // For variations, get the attribute information
                if ($childProduct->is_variation) {
                    // First try to get from the product itself
                    $attributes = $childProduct->variationProductAttributes;
                    if ($attributes && $attributes->count() > 0) {
                        $attributeItems = [];
                        foreach ($attributes as $attribute) {
                            $attributeItems[] = $attribute->attribute_set_title . ': ' . $attribute->title;
                        }
                        $attributeText = implode(', ', $attributeItems);
                    }
                    
                    // If still empty, try the variation ID method
                    if (empty($attributeText)) {
                        $attributeText = $this->getVariationAttributeInfo($childProduct->id);
                    }
                }
                
                // For products with variations, get the variation info from the name
                if (empty($attributeText) && preg_match('/\(([^\)]+)\)/', $childProduct->name, $matches)) {
                    $attributeText = $matches[1];
                }

                // Add to selected items list
                $selectedItems[] = [
                    'id' => $childId,
                    'name' => $childProduct->name,
                    'qty' => (int)$childQty,
                    'attribute_text' => $attributeText,
                ];

                // Extract the base product name without the attributes in parentheses
                $displayName = preg_replace('/\s*\([^\)]+\)\s*/', '', $childProduct->name);
                
                // Build enhanced text display with better formatting and more visible attributes
                $itemsText .= "• {$displayName} × {$childQty}";
                
                // Make attributes more visible by putting them on a new line with indentation
                if ($attributeText) {
                    $itemsText .= "\n    {$attributeText}";
                }
                
                // Add a line break after each item
                $itemsText .= "\n";
            }

            // Check if this is a mix and match product by name and settings
            $isMixAndMatch = false;
            
            // Check if product name contains "Mix & Match" and has mix and match settings
            if (stripos($product->name, 'Mix & Match') !== false && $product->mixAndMatchSetting) {
                $isMixAndMatch = true;
            }
            
            // If this is a mix and match product, require item selection
            if ($isMixAndMatch && empty($selectedItems)) {
                return $this->httpResponse()
                    ->setError()
                    ->setMessage(__('Please select at least one item for the mix and match product.'));
            }
            
            // If this is not a mix and match product, add it as a simple/variable product
            if (!$isMixAndMatch) {
                // Handle variable products
                if ($product->variations->count() > 0 && ! $product->is_variation && $product->defaultVariation->product->id) {
                    $product = $product->defaultVariation->product;
                }

                try {
                    // Use OrderHelper to handle cart addition which properly handles attributes
                    $cartItems = OrderHelper::handleAddCart($product, $request);
                    
                    return $this->httpResponse()
                        ->setData([
                            'status' => true,
                            'count' => Cart::instance('cart')->count(),
                            'html' => Theme::partial('cart-mini.list'),
                        ])
                        ->setMessage(__(
                            'Added product :product to cart successfully!',
                            ['product' => $product->original_product->name ?: $product->name]
                        ));
                } catch (ProductIsNotActivatedYetException $e) {
                    return $this->httpResponse()
                        ->setError()
                        ->setMessage($e->getMessage());
                } catch (Exception $e) {
                    return $this->httpResponse()
                        ->setError()
                        ->setMessage($e->getMessage());
                }
            }

            // Store the data in the cart with simple text display
            // Make sure all values are properly formatted for JSON encoding
            $options = [
                'image' => $product->image,
                'is_mix_and_match' => true,
                'attributes' => $itemsText,
                // Store selected items in a format that can be properly JSON encoded
                'selected_items' => array_map(function($item) {
                    return [
                        'id' => (int)$item['id'],
                        'name' => (string)$item['name'],
                        'qty' => (int)$item['qty'],
                        'attributes' => !empty($item['attribute_text']) ? (string)$item['attribute_text'] : '',
                    ];
                }, $selectedItems),
            ];

            $cartItem = Cart::instance('cart')->add(
                $product->id,
                $product->name,
                $qty,
                $product->price,
                $options
            );

            return $this->httpResponse()
                ->setData([
                    'status' => true,
                    'count' => Cart::instance('cart')->count(),
                    'message' => __('Added product :product to cart successfully!', ['product' => $product->name]),
                ]);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage() . ' - ' . $exception->getTraceAsString());

            return $this->httpResponse()
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }

    /**
     * Get attribute information for a variation product
     *
     * @param int $variationId
     * @return string
     */
    protected function getVariationAttributeInfo($variationId)
    {
        try {
            // Get variation items with attributes
            $variationItems = DB::table('ec_product_variation_items as pvi')
                ->join('ec_product_attributes as pa', 'pa.id', '=', 'pvi.attribute_id')
                ->join('ec_product_attribute_sets as pas', 'pas.id', '=', 'pa.attribute_set_id')
                ->where('pvi.variation_id', $variationId)
                ->select('pas.title as attribute_set', 'pa.title as attribute_value')
                ->get();

            if ($variationItems->isEmpty()) {
                // Try an alternative method to get attributes
                $variation = DB::table('ec_product_variations')
                    ->where('id', $variationId)
                    ->first();
                
                if ($variation && $variation->product_id) {
                    $product = Product::find($variation->product_id);
                    if ($product && $product->is_variation) {
                        $attributes = $product->variationProductAttributes;
                        if ($attributes && $attributes->count() > 0) {
                            $attributeInfo = [];
                            foreach ($attributes as $attribute) {
                                $attributeInfo[] = $attribute->attribute_set_title . ': ' . $attribute->title;
                            }
                            return implode(', ', $attributeInfo);
                        }
                    }
                }
                
                return '';
            }

            // Format attribute information: Size: XL, Color: Red
            $attributeInfo = [];
            foreach ($variationItems as $item) {
                $attributeInfo[] = $item->attribute_set . ': ' . $item->attribute_value;
            }

            return implode(', ', $attributeInfo);
        } catch (\Exception $e) {
            report($e);
            return '';
        }
    }
}
