<?php

namespace Botble\Membership\Http\Controllers;

use Botble\Membership\Http\Requests\MembershipRequest;
use Botble\Membership\Models\Membership;
use Botble\Base\Facades\PageTitle;
use Botble\Base\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Exception;
use Botble\Membership\Tables\MembershipTable;
use Botble\Base\Events\CreatedContentEvent;
use Botble\Base\Events\DeletedContentEvent;
use Botble\Base\Events\UpdatedContentEvent;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Membership\Forms\MembershipForm;
use Botble\Base\Forms\FormBuilder;

class MembershipController extends BaseController
{
    public function index(MembershipTable $table)
    {
        PageTitle::setTitle(trans('plugins/membership::membership.name'));

        return $table->renderTable();
    }

    public function create(FormBuilder $formBuilder)
    {
        PageTitle::setTitle(trans('plugins/membership::membership.create'));

        return $formBuilder->create(MembershipForm::class)->renderForm();
    }

    public function store(MembershipRequest $request, BaseHttpResponse $response)
    {
        $membership = Membership::query()->create($request->input());

        event(new CreatedContentEvent(MEMBERSHIP_MODULE_SCREEN_NAME, $request, $membership));

        return $response
            ->setPreviousUrl(route('membership.index'))
            ->setNextUrl(route('membership.edit', $membership->getKey()))
            ->setMessage(trans('core/base::notices.create_success_message'));
    }

    public function edit(Membership $membership, FormBuilder $formBuilder)
    {
        PageTitle::setTitle(trans('core/base::forms.edit_item', ['name' => $membership->name]));

        return $formBuilder->create(MembershipForm::class, ['model' => $membership])->renderForm();
    }

    public function update(Membership $membership, MembershipRequest $request, BaseHttpResponse $response)
    {
        $membership->fill($request->input());

        $membership->save();

        event(new UpdatedContentEvent(MEMBERSHIP_MODULE_SCREEN_NAME, $request, $membership));

        return $response
            ->setPreviousUrl(route('membership.index'))
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    public function destroy(Membership $membership, Request $request, BaseHttpResponse $response)
    {
        try {
            $membership->delete();

            event(new DeletedContentEvent(MEMBERSHIP_MODULE_SCREEN_NAME, $request, $membership));

            return $response->setMessage(trans('core/base::notices.delete_success_message'));
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }

    public function deletes(Request $request, BaseHttpResponse $response)
    {
        $ids = $request->input('ids');
        if (empty($ids)) {
            return $response
                ->setError()
                ->setMessage(trans('core/base::notices.no_select'));
        }

        foreach ($ids as $id) {
            $membership = Membership::query()->findOrFail($id);
            $membership->delete();
            event(new DeletedContentEvent(MEMBERSHIP_MODULE_SCREEN_NAME, $request, $membership));
        }

        return $response->setMessage(trans('core/base::notices.delete_success_message'));
    }
}
