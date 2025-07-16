<?php

namespace Botble\FreeGifts\Forms;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Forms\FormAbstract;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\ProductCategory;
use Botble\FreeGifts\Http\Requests\GiftRuleRequest;
use Botble\FreeGifts\Models\GiftRule;
use Carbon\Carbon;

class GiftRuleForm extends FormAbstract
{
    public function buildForm(): void
    {
        $this
            ->setupModel(new GiftRule())
            ->setValidatorClass(GiftRuleRequest::class)
            ->withCustomFields()
            ->setFormOption('class', 'gift-rule-form')
            ->setBreakFieldPoint('status')
            ->add('name', 'text', [
                'label' => trans('core/base::forms.name'),
                'label_attr' => ['class' => 'control-label required'],
                'attr' => [
                    'placeholder' => trans('core/base::forms.name_placeholder'),
                    'data-counter' => 120,
                ],
            ])
            ->add('description', 'textarea', [
                'label' => trans('core/base::forms.description'),
                'label_attr' => ['class' => 'control-label'],
                'attr' => [
                    'rows' => 4,
                    'placeholder' => trans('core/base::forms.description_placeholder'),
                    'data-counter' => 400,
                ],
            ])
            ->add('main-tabs', 'html', [
                'html' => '<div class="mt-3 main-form-body">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#tab-general" role="tab">
                                <i class="fa fa-info-circle"></i> ' . trans('plugins/free-gifts::gift-rules.tab_general') . '
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-dates" role="tab">
                                <i class="fa fa-calendar"></i> ' . trans('plugins/free-gifts::gift-rules.tab_dates') . '
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-restrictions" role="tab">
                                <i class="fa fa-lock"></i> ' . trans('plugins/free-gifts::gift-rules.tab_restrictions') . '
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-filters" role="tab">
                                <i class="fa fa-filter"></i> ' . trans('plugins/free-gifts::gift-rules.tab_filters') . '
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-gift-products" role="tab">
                                <i class="fa fa-gift"></i> ' . trans('plugins/free-gifts::gift-rules.tab_gift_products') . '
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="tab-general" role="tabpanel">
                            <div class="row mt-3">
                                <div class="col-md-6">',
            ])
            ->add('gift_type', 'customSelect', [
                'label' => trans('plugins/free-gifts::gift-rules.gift_type'),
                'label_attr' => ['class' => 'control-label required'],
                'choices' => [
                    'manual' => trans('plugins/free-gifts::gift-rules.gift_types.manual'),
                    'automatic' => trans('plugins/free-gifts::gift-rules.gift_types.automatic'),
                    'buy_x_get_y' => trans('plugins/free-gifts::gift-rules.gift_types.buy_x_get_y'),
                    'coupon_based' => trans('plugins/free-gifts::gift-rules.gift_types.coupon_based'),
                ],
            ])
            ->add('general-col-break', 'html', [
                'html' => '</div><div class="col-md-6">',
            ])
            ->add('criteria_type', 'customSelect', [
                'label' => trans('plugins/free-gifts::gift-rules.criteria_type'),
                'label_attr' => ['class' => 'control-label required'],
                'choices' => [
                    'cart_subtotal' => trans('plugins/free-gifts::gift-rules.criteria_types.cart_subtotal'),
                    'cart_total' => trans('plugins/free-gifts::gift-rules.criteria_types.cart_total'),
                    'category_total' => trans('plugins/free-gifts::gift-rules.criteria_types.category_total'),
                    'cart_quantity' => trans('plugins/free-gifts::gift-rules.criteria_types.cart_quantity'),
                ],
            ])
            ->add('general-row-close', 'html', [
                'html' => '</div></div>',
            ])
            ->add('criteria_value', 'number', [
                'label' => trans('plugins/free-gifts::gift-rules.criteria_value'),
                'label_attr' => ['class' => 'control-label required'],
                'attr' => [
                    'placeholder' => trans('plugins/free-gifts::gift-rules.criteria_value_placeholder'),
                    'min' => 0,
                    'step' => 0.01,
                ],
            ])
            ->add('tab-general-close', 'html', [
                'html' => '</div>',
            ])
            
            // Dates tab
            ->add('tab-dates-open', 'html', [
                'html' => '<div class="tab-pane" id="tab-dates" role="tabpanel">
                    <div class="row mt-3">
                        <div class="col-md-6">',
            ])
            ->add('start_date', 'datePicker', [
                'label' => trans('plugins/free-gifts::gift-rules.start_date'),
                'label_attr' => ['class' => 'control-label'],
                'default_value' => Carbon::now()->format('Y-m-d'),
            ])
            ->add('dates-col-break', 'html', [
                'html' => '</div><div class="col-md-6">',
            ])
            ->add('end_date', 'datePicker', [
                'label' => trans('plugins/free-gifts::gift-rules.end_date'),
                'label_attr' => ['class' => 'control-label'],
            ])
            ->add('dates-row-close', 'html', [
                'html' => '</div></div>',
            ])
            ->add('active_days', 'multiCheckList', [
                'label' => trans('plugins/free-gifts::gift-rules.active_days'),
                'label_attr' => ['class' => 'control-label'],
                'choices' => [
                    'mon' => trans('plugins/free-gifts::gift-rules.days.monday'),
                    'tue' => trans('plugins/free-gifts::gift-rules.days.tuesday'),
                    'wed' => trans('plugins/free-gifts::gift-rules.days.wednesday'),
                    'thu' => trans('plugins/free-gifts::gift-rules.days.thursday'),
                    'fri' => trans('plugins/free-gifts::gift-rules.days.friday'),
                    'sat' => trans('plugins/free-gifts::gift-rules.days.saturday'),
                    'sun' => trans('plugins/free-gifts::gift-rules.days.sunday'),
                ],
                'help_block' => [
                    'text' => trans('plugins/free-gifts::gift-rules.active_days_help'),
                ],
            ])
            ->add('tab-dates-close', 'html', [
                'html' => '</div>',
            ])
            
            // Restrictions tab
            ->add('tab-restrictions-open', 'html', [
                'html' => '<div class="tab-pane" id="tab-restrictions" role="tabpanel">
                    <div class="row mt-3">
                        <div class="col-md-6">',
            ])
            ->add('max_gifts_per_order', 'number', [
                'label' => trans('plugins/free-gifts::gift-rules.max_gifts_per_order'),
                'label_attr' => ['class' => 'control-label'],
                'attr' => [
                    'placeholder' => trans('plugins/free-gifts::gift-rules.max_gifts_per_order_placeholder'),
                    'min' => 1,
                ],
            ])
            ->add('restrictions-col1-break', 'html', [
                'html' => '</div><div class="col-md-6">',
            ])
            ->add('max_gifts_per_customer', 'number', [
                'label' => trans('plugins/free-gifts::gift-rules.max_gifts_per_customer'),
                'label_attr' => ['class' => 'control-label'],
                'attr' => [
                    'placeholder' => trans('plugins/free-gifts::gift-rules.max_gifts_per_customer_placeholder'),
                    'min' => 1,
                ],
            ])
            ->add('restrictions-row1-close', 'html', [
                'html' => '</div></div>
                <div class="row">
                    <div class="col-md-6">',
            ])
            ->add('max_gifts_total', 'number', [
                'label' => trans('plugins/free-gifts::gift-rules.max_gifts_total'),
                'label_attr' => ['class' => 'control-label'],
                'attr' => [
                    'placeholder' => trans('plugins/free-gifts::gift-rules.max_gifts_total_placeholder'),
                    'min' => 1,
                ],
            ])
            ->add('restrictions-col2-break', 'html', [
                'html' => '</div><div class="col-md-6">',
            ])
            ->add('require_min_orders', 'onOff', [
                'label' => trans('plugins/free-gifts::gift-rules.require_min_orders'),
                'label_attr' => ['class' => 'control-label'],
                'default_value' => false,
            ])
            ->add('restrictions-row2-close', 'html', [
                'html' => '</div></div>',
            ])
            ->add('min_orders_count', 'number', [
                'label' => trans('plugins/free-gifts::gift-rules.min_orders_count'),
                'label_attr' => ['class' => 'control-label'],
                'attr' => [
                    'placeholder' => trans('plugins/free-gifts::gift-rules.min_orders_count_placeholder'),
                    'min' => 1,
                ],
                'wrapper' => [
                    'class' => $this->formHelper->getConfig('defaults.wrapper_class') . ' min-orders-count-wrapper',
                ],
            ])
            ->add('require_customer_login', 'onOff', [
                'label' => trans('plugins/free-gifts::gift-rules.require_customer_login'),
                'label_attr' => ['class' => 'control-label'],
                'default_value' => false,
            ])
            ->add('allow_coupon', 'onOff', [
                'label' => trans('plugins/free-gifts::gift-rules.allow_coupon'),
                'label_attr' => ['class' => 'control-label'],
                'default_value' => true,
            ])
            ->add('tab-restrictions-close', 'html', [
                'html' => '</div>',
            ])
            
            // Filters tab
            ->add('tab-filters-open', 'html', [
                'html' => '<div class="tab-pane" id="tab-filters" role="tabpanel">
                    <div class="mt-3">',
            ])
            ->add('product_filter_type', 'customSelect', [
                'label' => trans('plugins/free-gifts::gift-rules.product_filter_type'),
                'label_attr' => ['class' => 'control-label'],
                'choices' => [
                    'all' => trans('plugins/free-gifts::gift-rules.product_filter_types.all'),
                    'specific_products' => trans('plugins/free-gifts::gift-rules.product_filter_types.specific_products'),
                    'specific_categories' => trans('plugins/free-gifts::gift-rules.product_filter_types.specific_categories'),
                ],
                'default_value' => 'all',
            ])
            ->add('product_ids', 'customSelect', [
                'label' => trans('plugins/free-gifts::gift-rules.select_products'),
                'label_attr' => ['class' => 'control-label'],
                'choices' => Product::query()
                    ->where('is_variation', false)
                    ->where('status', 'published')
                    ->pluck('name', 'id')
                    ->all(),
                'attr' => [
                    'multiple' => true,
                ],
                'wrapper' => [
                    'class' => $this->formHelper->getConfig('defaults.wrapper_class') . ' product-ids-wrapper',
                ],
            ])
            ->add('category_ids', 'customSelect', [
                'label' => trans('plugins/free-gifts::gift-rules.select_categories'),
                'label_attr' => ['class' => 'control-label'],
                'choices' => ProductCategory::query()
                    ->where('status', 'published')
                    ->pluck('name', 'id')
                    ->all(),
                'attr' => [
                    'multiple' => true,
                ],
                'wrapper' => [
                    'class' => $this->formHelper->getConfig('defaults.wrapper_class') . ' category-ids-wrapper',
                ],
            ])
            ->add('customer_filter_type', 'customSelect', [
                'label' => trans('plugins/free-gifts::gift-rules.customer_filter_type'),
                'label_attr' => ['class' => 'control-label'],
                'choices' => [
                    'all' => trans('plugins/free-gifts::gift-rules.customer_filter_types.all'),
                    'specific_customers' => trans('plugins/free-gifts::gift-rules.customer_filter_types.specific_customers'),
                ],
                'default_value' => 'all',
            ])
            ->add('customer_ids', 'customSelect', [
                'label' => trans('plugins/free-gifts::gift-rules.select_customers'),
                'label_attr' => ['class' => 'control-label'],
                'choices' => Customer::query()
                    ->pluck('name', 'id')
                    ->all(),
                'attr' => [
                    'multiple' => true,
                ],
                'wrapper' => [
                    'class' => $this->formHelper->getConfig('defaults.wrapper_class') . ' customer-ids-wrapper',
                ],
            ])
            ->add('tab-filters-close', 'html', [
                'html' => '</div></div>',
            ])
            
            // Gift Products tab
            ->add('tab-gift-products-open', 'html', [
                'html' => '<div class="tab-pane" id="tab-gift-products" role="tabpanel">',
            ])
            ->add('gift_products', 'html', [
                'label' => trans('plugins/free-gifts::gift-rules.gift_products'),
                'label_attr' => ['class' => 'control-label required'],
                'html' => view('plugins/free-gifts::gift-products-form', [
                    'products' => Product::query()
                        ->where('is_variation', false)
                        ->where('status', 'published')
                        ->get(),
                    'giftRule' => $this->getModel(),
                ])->render(),
            ])
            ->add('tab-gift-products-close', 'html', [
                'html' => '</div>',
            ])
            
            // Close tabs
            ->add('tabs-close', 'html', [
                'html' => '</div></div>',
            ])
            ->setBreakFieldPoint('status')
            ->add('status', 'customSelect', [
                'label' => trans('core/base::tables.status'),
                'label_attr' => ['class' => 'control-label required'],
                'choices' => BaseStatusEnum::labels(),
            ]);
    }
}
