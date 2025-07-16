<?php

namespace Botble\Ecommerce\Supports;

use Botble\Base\Forms\FieldOptions\CheckboxFieldOption;
use Botble\Base\Forms\Fields\OnOffCheckboxField;
use Botble\Base\Rules\OnOffRule;
use Botble\Ecommerce\Forms\Settings\FlashSaleSettingForm;
use Botble\Ecommerce\Http\Requests\Settings\FlashSaleSettingRequest;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Repositories\Interfaces\FlashSaleInterface;
use Botble\Support\Http\Requests\Request;
use Illuminate\Support\Collection;

class FlashSaleSupport
{
    protected Collection|array $flashSales = [];

    public function flashSaleForProduct(Product $product): ?Product
    {
        if (! $this->flashSales) {
            $this->getAvailableFlashSales();
        }

        if (! $product->getKey()) {
            return null;
        }
        
        // First, check if this exact product (including variation) is in flash sale
        foreach ($this->flashSales as $flashSale) {
            foreach ($flashSale->products as $flashSaleProduct) {
                if ($product->id == $flashSaleProduct->id) {
                    return $flashSaleProduct;
                }
            }
        }
        
        // If it's a variation, check if its parent product is in flash sale
        if ($product->is_variation) {
            $parentId = $product->configurable_product_id;
            
            foreach ($this->flashSales as $flashSale) {
                foreach ($flashSale->products as $flashSaleProduct) {
                    if ($parentId == $flashSaleProduct->id) {
                        // Clone the flash sale product but use the variation's ID
                        $flashSaleProduct->id = $product->id;
                        return $flashSaleProduct;
                    }
                }
            }
        }

        return null;
    }

    public function getAvailableFlashSales(): Collection
    {
        if (! $this->flashSales instanceof Collection) {
            $this->flashSales = collect();
        }

        if ($this->flashSales->isEmpty()) {
            // Get flash sales with products and load necessary relationships for variations
            $this->flashSales = app(FlashSaleInterface::class)->getAvailableFlashSales(['products']);
            
            // Load variation information for all products in flash sales
            foreach ($this->flashSales as $flashSale) {
                foreach ($flashSale->products as $product) {
                    if ($product->is_variation) {
                        // Load variation info and attributes for variation products
                        $product->load(['variationInfo', 'variationInfo.productAttributes']);
                        
                        // Format the product name to include attributes without labels
                        if (preg_match('/(.+)\s+\((.+)\)/', $product->name, $matches)) {
                            $baseName = $matches[1];
                            $attributeInfo = $matches[2];
                            
                            // Extract just the values from attribute info
                            $attributeParts = explode(', ', $attributeInfo);
                            $attributeValues = [];
                            
                            foreach ($attributeParts as $part) {
                                if (strpos($part, ':') !== false) {
                                    $parts = explode(':', $part, 2);
                                    $attributeValues[] = trim($parts[1]);
                                } else {
                                    $attributeValues[] = trim($part);
                                }
                            }
                            
                            // Update the product name with just the values
                            $product->name = $baseName . ' (' . implode(', ', $attributeValues) . ')';
                        }
                    }
                }
            }
        }

        return $this->flashSales;
    }

    public function isEnabled(): bool
    {
        return (bool) get_ecommerce_setting('flash_sale_enabled', true);
    }

    public function isShowSaleCountLeft(): bool
    {
        return (bool) get_ecommerce_setting('flash_sale_show_sale_count_left', true);
    }

    public function addShowSaleCountLeftSetting(): void
    {
        add_filter('core_request_rules', function (array $rules, Request $request) {
            if ($request instanceof FlashSaleSettingRequest) {
                $rules['flash_sale_show_sale_count_left'] = [new OnOffRule()];
            }

            return $rules;
        }, 10, 2);

        FlashSaleSettingForm::extend(function (FlashSaleSettingForm $form): void {
            $form->addAfter(
                'open_wrapper',
                'flash_sale_show_sale_count_left',
                OnOffCheckboxField::class,
                CheckboxFieldOption::make()
                    ->label(trans('plugins/ecommerce::setting.flash_sale.show_sale_count_left'))
                    ->helperText(trans('plugins/ecommerce::setting.flash_sale.show_sale_count_left_description'))
                    ->colspan(2)
                    ->value($this->isShowSaleCountLeft())
            );
        });
    }
}
