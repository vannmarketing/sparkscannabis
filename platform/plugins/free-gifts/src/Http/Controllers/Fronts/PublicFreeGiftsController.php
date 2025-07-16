<?php

namespace Botble\FreeGifts\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Models\Product;
use Botble\FreeGifts\Models\GiftRule;
use Botble\FreeGifts\Services\FreeGiftsService;
use Illuminate\Http\Request;

class PublicFreeGiftsController extends BaseController
{
    public function __construct(protected FreeGiftsService $freeGiftsService)
    {
    }

    public function getEligibleGifts(Request $request, BaseHttpResponse $response)
    {
        $eligibleRules = $this->freeGiftsService->getEligibleRules();
        $gifts = collect();

        foreach ($eligibleRules as $rule) {
            $ruleGifts = $this->freeGiftsService->getEligibleGifts($rule);
            $gifts = $gifts->merge($ruleGifts->map(function ($gift) use ($rule) {
                $gift->gift_rule_id = $rule->id;
                return $gift;
            }));
        }

        return $response->setData([
            'gifts' => $gifts,
            'rules' => $eligibleRules,
        ]);
    }

    public function addGiftToCart(Request $request, BaseHttpResponse $response)
    {
        $request->validate([
            'product_id' => 'required|exists:ec_products,id',
            'quantity' => 'required|integer|min:1',
            'gift_rule_id' => 'nullable|exists:fg_gift_rules,id',
        ]);

        $productId = $request->input('product_id');
        $quantity = $request->input('quantity');
        $giftRuleId = $request->input('gift_rule_id');

        $product = Product::findOrFail($productId);
        $giftRule = $giftRuleId ? GiftRule::findOrFail($giftRuleId) : null;

        // Verify this is a valid gift for the rule
        if ($giftRule) {
            $eligibleGifts = $this->freeGiftsService->getEligibleGifts($giftRule);
            $isEligible = $eligibleGifts->contains('id', $productId);

            if (!$isEligible) {
                return $response
                    ->setError()
                    ->setMessage(trans('plugins/free-gifts::free-gifts.product_not_eligible_for_gift'));
            }
        }

        $this->freeGiftsService->addGiftToCart($product, $quantity, $giftRule);

        return $response
            ->setMessage(trans('plugins/free-gifts::free-gifts.gift_added_to_cart_successfully'));
    }

    public function removeGiftFromCart(Request $request, BaseHttpResponse $response)
    {
        $request->validate([
            'row_id' => 'required|string',
        ]);

        $rowId = $request->input('row_id');

        $this->freeGiftsService->removeGiftFromCart($rowId);

        return $response
            ->setMessage(trans('plugins/free-gifts::free-gifts.gift_removed_from_cart_successfully'));
    }
}
