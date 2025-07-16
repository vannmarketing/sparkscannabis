<?php

namespace Botble\FreeGifts\Http\Controllers;

use Botble\Base\Events\CreatedContentEvent;
use Botble\Base\Events\DeletedContentEvent;
use Botble\Base\Events\UpdatedContentEvent;
use Botble\Base\Facades\PageTitle;
use Botble\Base\Forms\FormBuilder;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\FreeGifts\Forms\GiftRuleForm;
use Botble\FreeGifts\Http\Requests\GiftRuleRequest;
use Botble\FreeGifts\Models\GiftRule;
use Botble\FreeGifts\Tables\GiftRuleTable;
use Exception;
use Illuminate\Http\Request;

class GiftRuleController extends BaseController
{
    public function index(GiftRuleTable $table)
    {
        PageTitle::setTitle(trans('plugins/free-gifts::gift-rules.name'));

        return $table->renderTable();
    }

    public function create(FormBuilder $formBuilder)
    {
        PageTitle::setTitle(trans('plugins/free-gifts::gift-rules.create'));

        return $formBuilder->create(GiftRuleForm::class)->renderForm();
    }

    public function store(GiftRuleRequest $request, BaseHttpResponse $response)
    {
        $giftRule = GiftRule::query()->create($request->input());

        // Handle gift products
        if ($request->has('gift_products')) {
            $giftProducts = $request->input('gift_products', []);
            foreach ($giftProducts as $productId => $attributes) {
                $giftRule->giftProducts()->attach($productId, [
                    'quantity' => $attributes['quantity'] ?? 1,
                    'is_same_product' => $attributes['is_same_product'] ?? false,
                ]);
            }
        }

        event(new CreatedContentEvent(GIFT_RULE_MODULE_SCREEN_NAME, $request, $giftRule));

        return $response
            ->setPreviousUrl(route('gift-rules.index'))
            ->setNextUrl(route('gift-rules.edit', $giftRule->id))
            ->setMessage(trans('core/base::notices.create_success_message'));
    }

    public function edit(int $id, FormBuilder $formBuilder)
    {
        $giftRule = GiftRule::with('giftProducts')->findOrFail($id);

        PageTitle::setTitle(trans('plugins/free-gifts::gift-rules.edit', ['name' => $giftRule->name]));

        return $formBuilder->create(GiftRuleForm::class, ['model' => $giftRule])->renderForm();
    }

    public function update(int $id, GiftRuleRequest $request, BaseHttpResponse $response)
    {
        $giftRule = GiftRule::findOrFail($id);
        $giftRule->fill($request->input());
        $giftRule->save();

        // Handle gift products
        $giftRule->giftProducts()->detach();
        if ($request->has('gift_products')) {
            $giftProducts = $request->input('gift_products', []);
            foreach ($giftProducts as $productId => $attributes) {
                $giftRule->giftProducts()->attach($productId, [
                    'quantity' => $attributes['quantity'] ?? 1,
                    'is_same_product' => $attributes['is_same_product'] ?? false,
                ]);
            }
        }

        event(new UpdatedContentEvent(GIFT_RULE_MODULE_SCREEN_NAME, $request, $giftRule));

        return $response
            ->setPreviousUrl(route('gift-rules.index'))
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    public function destroy(int $id, Request $request, BaseHttpResponse $response)
    {
        try {
            $giftRule = GiftRule::findOrFail($id);
            $giftRule->delete();

            event(new DeletedContentEvent(GIFT_RULE_MODULE_SCREEN_NAME, $request, $giftRule));

            return $response->setMessage(trans('core/base::notices.delete_success_message'));
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }
}
