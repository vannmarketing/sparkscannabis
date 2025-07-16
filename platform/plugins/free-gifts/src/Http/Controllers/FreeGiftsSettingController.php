<?php

namespace Botble\FreeGifts\Http\Controllers;

use Botble\Base\Facades\PageTitle;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\FreeGifts\Http\Requests\FreeGiftsSettingRequest;
use Botble\FreeGifts\Services\FreeGiftsSettingService;

class FreeGiftsSettingController extends BaseController
{
    public function __construct(protected FreeGiftsSettingService $settingService)
    {
    }

    public function index()
    {
        PageTitle::setTitle(trans('plugins/free-gifts::settings.name'));

        $settings = $this->settingService->getSettings();

        return view('plugins/free-gifts::settings.index', compact('settings'));
    }

    public function store(FreeGiftsSettingRequest $request, BaseHttpResponse $response)
    {
        $this->settingService->saveSettings($request->validated());

        return $response
            ->setMessage(trans('core/base::notices.update_success_message'));
    }
}
