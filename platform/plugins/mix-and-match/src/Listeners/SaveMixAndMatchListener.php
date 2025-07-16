<?php

namespace Botble\MixAndMatch\Listeners;

use Botble\Base\Events\CreatedContentEvent;
use Botble\Base\Events\UpdatedContentEvent;
use Botble\Base\Facades\BaseHelper;
use Botble\Ecommerce\Models\Product;
use Botble\MixAndMatch\Models\MixAndMatchProduct;
use Botble\MixAndMatch\Models\MixAndMatchSetting;
use Exception;
use Illuminate\Support\Facades\DB;

class SaveMixAndMatchListener
{
    public function handle(CreatedContentEvent|UpdatedContentEvent $event): void
    {
        if (!$event->data instanceof Product) {
            return;
        }

        $product = $event->data;
        $request = request();

        try {
            DB::beginTransaction();

            $isMixAndMatch = $request->input('is_mix_and_match');

            if ($isMixAndMatch) {
                // Save Mix and Match settings
                MixAndMatchSetting::query()->updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'min_container_size' => $request->input('min_container_size', 1),
                        'max_container_size' => $request->input('max_container_size'),
                        'pricing_type' => $request->input('pricing_type', 'per_item'),
                        'fixed_price' => $request->input('pricing_type') === 'fixed_price' ? $request->input('fixed_price') : null,
                    ]
                );

                // Delete existing mix and match items
                MixAndMatchProduct::query()->where('container_product_id', $product->id)->delete();

                // Save new mix and match items
                $items = $request->input('mix_and_match_items', []);
                foreach ($items as $childProductId => $item) {
                    MixAndMatchProduct::query()->create([
                        'container_product_id' => $product->id,
                        'child_product_id' => $childProductId,
                        'min_qty' => $item['min_qty'] ?? 0,
                        'max_qty' => $item['max_qty'] ?? 1,
                    ]);
                }
            } else {
                // Delete Mix and Match settings if product is no longer a Mix and Match product
                MixAndMatchSetting::query()->where('product_id', $product->id)->delete();
                MixAndMatchProduct::query()->where('container_product_id', $product->id)->delete();
            }

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            BaseHelper::logError($exception);
        }
    }
}
