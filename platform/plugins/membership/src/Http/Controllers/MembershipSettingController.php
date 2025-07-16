<?php

namespace Botble\Membership\Http\Controllers\Settings;

use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Membership\Forms\Settings\MembershipSettingForm;
use Botble\Membership\Http\Requests\Settings\BlogSettingRequest;
use Botble\Setting\Http\Controllers\SettingController;

class MembershipSettingController extends SettingController
{
    public function edit()
    {
        $this->pageTitle(trans('plugins/blog::base.settings.title'));

        return MembershipSettingForm::create()->renderForm();
    }

    public function update(BlogSettingRequest $request): BaseHttpResponse
    {
        return $this->performUpdate($request->validated());
    }
}
