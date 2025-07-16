<?php

namespace Botble\Membership\Forms\Settings;

use Botble\Membership\Http\Requests\Settings\MembershipSettingRequest;
use Botble\Setting\Forms\SettingForm;

class MembershipSettingForm extends SettingForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->setSectionTitle(trans('plugins/membership::base.settings.title'))
            ->setSectionDescription(trans('plugins/membership::base.settings.description'))
            ->setValidatorClass(MembershipSettingRequest::class)
            ->add('membership_setting', 'html', [
                'html' => view('plugins/membership::partials.membership-fields'),
                'wrapper' => [
                    'class' => 'mb-0',
                ],
            ]);
    }
}
