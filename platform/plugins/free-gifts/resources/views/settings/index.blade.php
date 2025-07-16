@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ trans('plugins/free-gifts::settings.name') }}</h4>
                </div>
                <div class="card-body">
                    {!! Form::open(['route' => 'free-gifts.settings.store', 'method' => 'POST']) !!}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('plugins/free-gifts::settings.display_mode') }}</label>
                                    {!! Form::customSelect('display_mode', [
                                        'inline' => trans('plugins/free-gifts::settings.display_modes.inline'),
                                        'popup' => trans('plugins/free-gifts::settings.display_modes.popup'),
                                    ], $settings['display_mode']) !!}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('plugins/free-gifts::settings.display_type') }}</label>
                                    {!! Form::customSelect('display_type', [
                                        'table' => trans('plugins/free-gifts::settings.display_types.table'),
                                        'carousel' => trans('plugins/free-gifts::settings.display_types.carousel'),
                                        'dropdown' => trans('plugins/free-gifts::settings.display_types.dropdown'),
                                    ], $settings['display_type']) !!}
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('plugins/free-gifts::settings.hide_gift_products_in_shop') }}</label>
                                    {!! Form::onOff('hide_gift_products_in_shop', $settings['hide_gift_products_in_shop']) !!}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('plugins/free-gifts::settings.allow_multiple_gift_quantities') }}</label>
                                    {!! Form::onOff('allow_multiple_gift_quantities', $settings['allow_multiple_gift_quantities']) !!}
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('plugins/free-gifts::settings.allow_remove_auto_gifts') }}</label>
                                    {!! Form::onOff('allow_remove_auto_gifts', $settings['allow_remove_auto_gifts']) !!}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('plugins/free-gifts::settings.charge_shipping_for_gifts') }}</label>
                                    {!! Form::onOff('charge_shipping_for_gifts', $settings['charge_shipping_for_gifts']) !!}
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('plugins/free-gifts::settings.log_retention_days') }}</label>
                                    {!! Form::number('log_retention_days', $settings['log_retention_days'], [
                                        'class' => 'form-control',
                                        'min' => 1,
                                    ]) !!}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('plugins/free-gifts::settings.eligibility_notice_enabled') }}</label>
                                    {!! Form::onOff('eligibility_notice_enabled', $settings['eligibility_notice_enabled']) !!}
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ trans('plugins/free-gifts::settings.eligibility_notice_text') }}</label>
                            {!! Form::text('eligibility_notice_text', $settings['eligibility_notice_text'], [
                                'class' => 'form-control',
                                'placeholder' => trans('plugins/free-gifts::settings.eligibility_notice_text_placeholder'),
                            ]) !!}
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ trans('plugins/free-gifts::settings.gift_selection_title') }}</label>
                            {!! Form::text('gift_selection_title', $settings['gift_selection_title'], [
                                'class' => 'form-control',
                                'placeholder' => trans('plugins/free-gifts::settings.gift_selection_title_placeholder'),
                            ]) !!}
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ trans('plugins/free-gifts::settings.gift_selection_description') }}</label>
                            {!! Form::text('gift_selection_description', $settings['gift_selection_description'], [
                                'class' => 'form-control',
                                'placeholder' => trans('plugins/free-gifts::settings.gift_selection_description_placeholder'),
                            ]) !!}
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('plugins/free-gifts::settings.add_gift_button_text') }}</label>
                                    {!! Form::text('add_gift_button_text', $settings['add_gift_button_text'], [
                                        'class' => 'form-control',
                                        'placeholder' => trans('plugins/free-gifts::settings.add_gift_button_text_placeholder'),
                                    ]) !!}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('plugins/free-gifts::settings.remove_gift_button_text') }}</label>
                                    {!! Form::text('remove_gift_button_text', $settings['remove_gift_button_text'], [
                                        'class' => 'form-control',
                                        'placeholder' => trans('plugins/free-gifts::settings.remove_gift_button_text_placeholder'),
                                    ]) !!}
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ trans('plugins/free-gifts::settings.gift_added_text') }}</label>
                            {!! Form::text('gift_added_text', $settings['gift_added_text'], [
                                'class' => 'form-control',
                                'placeholder' => trans('plugins/free-gifts::settings.gift_added_text_placeholder'),
                            ]) !!}
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">{{ trans('core/base::forms.save') }}</button>
                        </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
@endsection
