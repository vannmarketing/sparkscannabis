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

class FreeGiftsController extends BaseController
{
    public function index(GiftRuleTable $table)
    {
        PageTitle::setTitle(trans('plugins/free-gifts::free-gifts.name'));

        return $table->renderTable();
    }

    public function create(FormBuilder $formBuilder)
    {
        PageTitle::setTitle(trans('plugins/free-gifts::free-gifts.create'));

        return $formBuilder->create(GiftRuleForm::class)->renderForm();
    }

    public function store(GiftRuleRequest $request, BaseHttpResponse $response)
    {
        $giftRule = GiftRule::query()->create($request->input());

        event(new CreatedContentEvent(GIFT_RULE_MODULE_SCREEN_NAME, $request, $giftRule));

        return $response
            ->setPreviousUrl(route('free-gifts.index'))
            ->setNextUrl(route('free-gifts.edit', $giftRule->id))
            ->setMessage(trans('core/base::notices.create_success_message'));
    }

    public function edit(GiftRule $giftRule, FormBuilder $formBuilder)
    {
        PageTitle::setTitle(trans('plugins/free-gifts::free-gifts.edit', ['name' => $giftRule->name]));

        return $formBuilder->create(GiftRuleForm::class, ['model' => $giftRule])->renderForm();
    }

    public function update(GiftRule $giftRule, GiftRuleRequest $request, BaseHttpResponse $response)
    {
        $giftRule->fill($request->input());
        $giftRule->save();

        event(new UpdatedContentEvent(GIFT_RULE_MODULE_SCREEN_NAME, $request, $giftRule));

        return $response
            ->setPreviousUrl(route('free-gifts.index'))
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    public function destroy(GiftRule $giftRule, Request $request, BaseHttpResponse $response)
    {
        try {
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
