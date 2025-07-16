<template>
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3 position-relative">
                        <label class="form-label">{{ __('discount.select_type_of_discount') }}</label>
                        <select
                            class="form-select"
                            id="select-promotion"
                            name="type"
                            v-model="type"
                            @change="changeDiscountType()"
                        >
                            <option value="coupon">{{ __('discount.coupon_code') }}</option>
                            <option value="promotion">{{ __('discount.promotion') }}</option>
                        </select>
                    </div>

                    <div class="mb-3 position-relative">
                        <label class="form-label">
                            <template v-if="is_promotion">{{ __('discount.create_discount_promotion') }}</template>
                            <template v-else>{{ __('discount.create_coupon_code') }}</template>
                        </label>

                        <div v-show="!is_promotion" class="input-group input-group-flat">
                            <input 
                                type="text" 
                                class="form-control coupon-code-input" 
                                name="code" 
                                v-model="code" 
                                required 
                            />
                            <span class="input-group-text">
                                <a
                                    href="javascript:void(0)"
                                    @click="generateCouponCode($event)"
                                    class="input-group-link"
                                    >{{ __('discount.generate_coupon_code') }}</a
                                >
                            </span>
                        </div>

                        <input
                            type="text"
                            class="form-control"
                            name="title"
                            v-model="title"
                            v-show="is_promotion"
                            :placeholder="__('discount.enter_promotion_name')"
                        />
                        <small class="form-hint" v-show="!is_promotion">
                            {{ __('discount.customers_will_enter_this_coupon_code_when_they_checkout') }}.
                        </small>
                    </div>

                    <template v-if="!is_promotion">
                        <div class="mb-3 position-relative">
                            <label class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="can_use_with_promotion"
                                    v-model="can_use_with_promotion"
                                    value="1"
                                />
                                <span class="form-check-label">
                                    {{ __('discount.can_be_used_with_promotion') }}
                                </span>
                            </label>

                        </div>

                        <div class="mb-3 position-relative">
                            <label class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="can_use_with_flash_sale"
                                    v-model="can_use_with_flash_sale"
                                    value="1"
                                />
                                <span class="form-check-label">
                                    {{ __('discount.can_be_used_with_flash_sale') }}
                                </span>
                                <span class="form-check-description">
                                    {{ __('discount.can_be_used_with_flash_sale_help') }}
                                </span>
                            </label>
                        </div>

                        <div class="mb-3 position-relative">
                            <label class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="is_unlimited"
                                    v-model="is_unlimited"
                                    value="1"
                                />
                                <span class="form-check-label">
                                    {{ __('discount.unlimited_coupon') }}
                                </span>
                            </label>
                        </div>

                        <div class="mb-3 position-relative" v-show="!is_unlimited">
                            <label class="form-label">{{ __('discount.enter_number') }}</label>
                            <input
                                type="text"
                                class="form-control"
                                name="quantity"
                                v-model="quantity"
                                autocomplete="off"
                                :disabled="is_unlimited"
                            />
                        </div>

                        <div class="mb-3 position-relative">
                            <label class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="apply_via_url"
                                    v-model="apply_via_url"
                                    value="1"
                                />
                                <span class="form-check-label">
                                    {{ __('discount.apply_via_url') }}
                                </span>
                                <span
                                    class="form-check-description"
                                    v-html="__('discount.apply_via_url_description')"
                                ></span>
                            </label>
                        </div>

                        <div class="mb-3 position-relative">
                            <label class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="display_at_checkout"
                                    v-model="display_at_checkout"
                                    value="1"
                                />
                                <span class="form-check-label">
                                    {{ __('discount.display_at_checkout') }}
                                </span>
                                <span class="form-check-description">
                                    {{ __('discount.display_at_checkout_description') }}
                                </span>
                            </label>
                        </div>

                        <div class="mb-3 position-relative" v-show="!is_promotion && display_at_checkout">
                            <label for="description" class="form-label">{{ __('discount.description') }}</label>
                            <textarea
                                name="description"
                                id="description"
                                class="form-control"
                                v-model="description"
                                :placeholder="__('discount.description_placeholder')"
                            ></textarea>
                        </div>
                    </template>

                    <div class="border-top">
                        <h4 class="mt-3 mb-2">{{ __('discount.coupon_type') }}</h4>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <select
                                    id="discount-type-option"
                                    name="type_option"
                                    class="form-select"
                                    v-model="type_option"
                                    @change="handleChangeTypeOption()"
                                >
                                    <option value="amount">{{ currency }}</option>
                                    <option value="percentage">{{ __('discount.percentage_discount') }}</option>
                                    <option value="shipping" v-if="!is_promotion">
                                        {{ __('discount.free_shipping') }}
                                    </option>
                                    <option value="same-price">{{ __('discount.same_price') }}</option>
                                </select>
                            </div>

                            <div
                                class="mb-3"
                                :class="{
                                    'col-md-4': type_option !== 'shipping',
                                    'col-md-8': type_option === 'shipping',
                                }"
                            >
                                <div class="input-group input-group-flat">
                                    <span class="input-group-text">{{ value_label }}</span>
                                    <input
                                        type="number"
                                        class="form-control"
                                        name="value"
                                        v-model="value"
                                        required
                                        min="0"
                                        step="1"
                                        autocomplete="off"
                                        placeholder="0"
                                    />
                                    <span class="input-group-text">
                                        {{ discountUnit }}
                                    </span>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3" v-show="type_option !== 'shipping' && type_option">
                                <div class="input-group input-group-flat" @change="handleChangeTarget()">
                                    <span class="input-group-text" v-show="type_option !== 'shipping' && type_option">
                                        {{ __('discount.apply_for') }}
                                    </span>

                                    <select
                                        id="select-offers"
                                        class="form-control form-select"
                                        name="target"
                                        v-model="target"
                                    >
                                        <option value="all-orders" v-if="type_option !== 'same-price'">
                                            {{ __('discount.all_orders') }}
                                        </option>
                                        <option value="amount-minimum-order" v-if="type_option !== 'same-price'">
                                            {{ __('discount.order_amount_from') }}
                                        </option>
                                        <option value="group-products">{{ __('discount.product_collection') }}</option>
                                        <option value="products-by-category">
                                            {{ __('discount.product_category') }}
                                        </option>
                                        <option value="specific-product">{{ __('discount.product') }}</option>
                                        <option value="customer" v-if="type_option !== 'same-price'">
                                            {{ __('discount.customer') }}
                                        </option>
                                        <option value="product-variant">{{ __('discount.variant') }}</option>
                                        <option value="non-sale-items" v-if="type_option !== 'same-price'">
                                            Non Sale Items
                                        </option>
                                        <option
                                            value="once-per-customer"
                                            v-if="type_option !== 'same-price' && type === 'coupon'"
                                        >
                                            {{ __('discount.once_per_customer') }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3" v-if="target === 'group-products' && type_option !== 'shipping'">
                                <select name="product_collections" class="form-select" v-model="product_collection_id">
                                    <option
                                        v-for="product_collection in product_collections"
                                        :value="product_collection.id"
                                    >
                                        {{ product_collection.name }}
                                    </option>
                                </select>
                            </div>

                            <div
                                class="col-md-4 mb-3"
                                v-if="target === 'products-by-category' && type_option !== 'shipping'"
                            >
                                <select name="product_categories" class="form-select" v-model="product_category_id">
                                    <option
                                        v-for="productCategory in product_categories"
                                        :value="productCategory.id"
                                        v-html="productCategory.name"
                                    ></option>
                                </select>
                            </div>

                            <div
                                class="col-md-4 mb-3"
                                v-if="target === 'specific-product' && type_option !== 'shipping'"
                            >
                                <div class="position-relative box-search-advance product">
                                    <input
                                        type="text"
                                        class="form-control textbox-advancesearch"
                                        @click="loadListProductsForSearch(0)"
                                        @keyup="handleSearchProduct(0, $event.target.value)"
                                        :placeholder="__('discount.search_product')"
                                    />

                                    <div
                                        class="card position-absolute w-100 z-1"
                                        :class="{ active: products, hidden: hidden_product_search_panel }"
                                        :style="[loading ? { minHeight: '10rem' } : {}]"
                                    >
                                        <div v-if="loading" class="loading-spinner"></div>
                                        <div
                                            v-else
                                            class="list-group list-group-flush overflow-auto"
                                            style="max-height: 25rem"
                                        >
                                            <a
                                                class="list-group-item list-group-item-action"
                                                v-for="product in products.data"
                                                @click="handleSelectProducts(product)"
                                                href="javascript:void(0)"
                                            >
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        <span
                                                            class="avatar"
                                                            :style="{
                                                                backgroundImage: 'url(' + product.image_url + ')',
                                                            }"
                                                        ></span>
                                                    </div>
                                                    <div class="col text-truncate">
                                                        <div class="text-body d-block">{{ product.name }}</div>
                                                    </div>
                                                </div>
                                            </a>
                                            <div class="p-3" v-if="products.data.length === 0">
                                                <p class="text-muted text-center mb-0">
                                                    {{ __('discount.no_products_found') }}
                                                </p>
                                            </div>
                                        </div>
                                        <div
                                            class="card-footer"
                                            v-if="(products.next_page_url || products.prev_page_url) && !loading"
                                        >
                                            <discount-search-box-pagination
                                                :resource="products"
                                                @on-prev="
                                                    loadListProductsForSearch(
                                                        0,
                                                        products.prev_page_url
                                                            ? products.current_page - 1
                                                            : products.current_page,
                                                        true
                                                    )
                                                "
                                                @on-next="
                                                    loadListProductsForSearch(
                                                        0,
                                                        products.next_page_url
                                                            ? products.current_page + 1
                                                            : products.current_page,
                                                        true
                                                    )
                                                "
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3" v-if="target === 'customer' && type_option !== 'shipping'">
                                <div class="position-relative box-search-advance customer">
                                    <input
                                        type="text"
                                        class="form-control textbox-advancesearch customer"
                                        @click="loadListCustomersForSearch()"
                                        @keyup="handleSearchCustomer($event.target.value)"
                                        :placeholder="__('discount.search_customer')"
                                    />

                                    <div
                                        class="card position-absolute w-100 z-1"
                                        v-bind:class="{ active: customers, hidden: hidden_customer_search_panel }"
                                        :style="[loading ? { minHeight: '10rem' } : {}]"
                                    >
                                        <div v-if="loading" class="loading-spinner"></div>
                                        <div
                                            v-else
                                            class="list-group list-group-flush overflow-auto"
                                            style="max-height: 25rem"
                                        >
                                            <a
                                                class="list-group-item list-group-item-action"
                                                v-for="customer in customers.data"
                                                @click="handleSelectCustomers(customer)"
                                                href="javascript:void(0)"
                                            >
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        <span
                                                            class="avatar"
                                                            :style="{
                                                                backgroundImage: 'url(' + customer.avatar_url + ')',
                                                            }"
                                                        ></span>
                                                    </div>
                                                    <div class="col text-truncate">
                                                        <div class="text-body d-block">{{ customer.name }}</div>
                                                        <a
                                                            :href="`mailto:${customer.email}`"
                                                            class="text-secondary text-truncate mt-n1"
                                                            >{{ customer.email }}</a
                                                        >
                                                    </div>
                                                </div>
                                            </a>
                                            <div class="p-3" v-if="customers.data.length === 0">
                                                <p class="text-muted text-center mb-0">
                                                    {{ __('discount.no_customer_found') }}
                                                </p>
                                            </div>
                                        </div>

                                        <div
                                            class="card-footer"
                                            v-if="(customers.next_page_url || customers.prev_page_url) && !loading"
                                        >
                                            <discount-search-box-pagination
                                                :resource="customers"
                                                @on-prev="
                                                    loadListCustomersForSearch(
                                                        customers.prev_page_url
                                                            ? customers.current_page - 1
                                                            : customers.current_page,
                                                        true
                                                    )
                                                "
                                                @on-next="
                                                    loadListCustomersForSearch(
                                                        customers.next_page_url
                                                            ? customers.current_page + 1
                                                            : customers.current_page,
                                                        true
                                                    )
                                                "
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="col-md-4 mb-3"
                                v-if="
                                    !is_promotion &&
                                    (target === 'group-products' ||
                                        target === 'products-by-category' ||
                                        target === 'specific-product' ||
                                        target === 'product-variant') &&
                                    type_option === 'amount'
                                "
                            >
                                <select class="form-select" name="discount_on" v-model="discount_on">
                                    <option value="per-order">{{ __('discount.one_time_per_order') }}</option>
                                    <option value="per-every-item">
                                        {{ __('discount.one_time_per_product_in_cart') }}
                                    </option>
                                </select>
                            </div>

                            <div
                                class="col-md-4 mb-3"
                                v-if="target === 'amount-minimum-order' && type_option !== 'shipping'"
                            >
                                <div class="input-group input-group-flat">
                                    <input
                                        type="number"
                                        class="form-control form-control--invisible"
                                        v-model="min_order_price"
                                        name="min_order_price"
                                    />
                                    <span class="input-group-text">{{ currency }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-show="is_promotion" class="mb-3 position-relative">
                            <label class="form-label" for="product-quantity">
                                {{ __('discount.number_of_products') }}
                            </label>
                            <input
                                type="text"
                                class="form-control"
                                name="product_quantity"
                                id="product-quantity"
                                v-model="product_quantity"
                            />
                        </div>

                        <div
                            v-if="selected_variants.length && target === 'product-variant'"
                            class="list-group list-group-flush list-group-hoverable"
                        >
                            <input type="hidden" v-model="selected_variant_ids" name="variants" />

                            <h4>{{ __('discount.selected_products') }}</h4>

                            <div v-for="variant in selected_variants" class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span
                                            class="avatar"
                                            :style="{ backgroundImage: 'url(' + variant.image_url + ')' }"
                                        ></span>
                                    </div>
                                    <div class="col text-truncate">
                                        <a :href="variant.product_link" target="_blank" class="text-body d-block">
                                            {{ variant.product_name }}
                                        </a>

                                        <div class="text-secondary text-truncate">
                                            <span v-for="(variantItem, index) in variant.variation_items">
                                                {{ variantItem.attribute_title }}
                                                <span v-if="index !== variant.variation_items.length - 1"> / </span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <discount-list-item-remove-icon-button
                                            @click="handleRemoveVariant($event, variant)"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="selected_products.length && target === 'specific-product'"
                            class="list-group list-group-flush list-group-hoverable"
                        >
                            <input type="hidden" v-model="selected_product_ids" name="products" />

                            <h4>{{ __('discount.selected_products') }}</h4>

                            <div class="list-group-item" v-for="product in selected_products">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span
                                            class="avatar"
                                            :style="{ backgroundImage: 'url(' + product.image_url + ')' }"
                                        ></span>
                                    </div>
                                    <div class="col text-truncate">
                                        <a :href="product.product_link" class="text-body d-block" target="_blank">{{
                                            product.name
                                        }}</a>
                                    </div>
                                    <div class="col-auto">
                                        <discount-list-item-remove-icon-button
                                            @click="handleRemoveProduct($event, product)"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="selected_customers.length && target === 'customer'"
                            class="list-group list-group-flush list-group-hoverable"
                        >
                            <input type="hidden" v-model="selected_customer_ids" name="customers" />

                            <h4>{{ __('discount.selected_customers') }}</h4>

                            <div class="list-group-item" v-for="customer in selected_customers">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span
                                            class="avatar"
                                            :style="{ backgroundImage: 'url(' + customer.avatar_url + ')' }"
                                        ></span>
                                    </div>
                                    <div class="col text-truncate">
                                        <a :href="customer.customer_link" class="text-body d-block" target="_blank">{{
                                            customer.name
                                        }}</a>
                                        <div class="text-secondary text-truncate">{{ customer.email }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <discount-list-item-remove-icon-button
                                            @click="handleRemoveCustomer($event, customer)"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="meta-boxes card mb-3">
                <div class="card-header">
                    <h4 class="card-title">{{ __('discount.time') }}</h4>
                </div>
                <div class="card-body">
                    <div class="mb-3 position-relative">
                        <label class="form-label">{{ __('discount.start_date') }}</label>
                        <div class="d-flex">
                            <div class="input-icon datepicker">
                                <input
                                    type="text"
                                    placeholder="YYYY-MM-DD"
                                    data-date-format="Y-m-d"
                                    name="start_date"
                                    v-model="start_date"
                                    class="form-control rounded-end-0"
                                    readonly
                                    data-input
                                />
                                <span class="input-icon-addon">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="icon"
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                        stroke="currentColor"
                                        fill="none"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path
                                            d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z"
                                        />
                                        <path d="M16 3v4" />
                                        <path d="M8 3v4" />
                                        <path d="M4 11h16" />
                                        <path d="M11 15h1" />
                                        <path d="M12 15v3" />
                                    </svg>
                                </span>
                            </div>
                            <div class="input-icon">
                                <input
                                    type="text"
                                    placeholder="hh:mm"
                                    name="start_time"
                                    v-model="start_time"
                                    class="form-control rounded-start-0 timepicker timepicker-24"
                                />
                                <span class="input-icon-addon">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="icon icon-tabler icon-tabler-clock"
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                        stroke="currentColor"
                                        fill="none"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                                        <path d="M12 7v5l3 3" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 position-relative">
                        <label class="form-label">{{ __('discount.end_date') }}</label>
                        <div class="d-flex">
                            <div class="input-icon datepicker">
                                <input
                                    type="text"
                                    placeholder="YYYY-MM-DD"
                                    data-date-format="Y-m-d"
                                    name="end_date"
                                    v-model="end_date"
                                    class="form-control rounded-end-0"
                                    :disabled="unlimited_time"
                                    readonly
                                    data-input
                                />
                                <span class="input-icon-addon">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="icon"
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                        stroke="currentColor"
                                        fill="none"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path
                                            d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z"
                                        />
                                        <path d="M16 3v4" />
                                        <path d="M8 3v4" />
                                        <path d="M4 11h16" />
                                        <path d="M11 15h1" />
                                        <path d="M12 15v3" />
                                    </svg>
                                </span>
                            </div>
                            <div class="input-icon">
                                <input
                                    type="text"
                                    placeholder="hh:mm"
                                    name="end_time"
                                    v-model="end_time"
                                    class="form-control rounded-start-0 timepicker timepicker-24"
                                    :disabled="unlimited_time"
                                />
                                <span class="input-icon-addon">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="icon icon-tabler icon-tabler-clock"
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                        stroke="currentColor"
                                        fill="none"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                                        <path d="M12 7v5l3 3" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="position-relative">
                        <label class="form-check">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="unlimited_time"
                                v-model="unlimited_time"
                                value="1"
                            />
                            <span class="form-check-label">{{ __('discount.never_expired') }}</span>
                        </label>
                    </div>
                    
                    <div class="position-relative mt-3">
                        <label class="form-label">Active days of week</label>
                        <div class="d-flex flex-wrap gap-2">
                            <button 
                                type="button" 
                                class="btn" 
                                :class="{ 'btn-primary': activeDays.includes('mon'), 'btn-outline-primary': !activeDays.includes('mon') }"
                                @click="toggleActiveDay('mon')"
                            >Monday</button>
                            <button 
                                type="button" 
                                class="btn" 
                                :class="{ 'btn-primary': activeDays.includes('tue'), 'btn-outline-primary': !activeDays.includes('tue') }"
                                @click="toggleActiveDay('tue')"
                            >Tuesday</button>
                            <button 
                                type="button" 
                                class="btn" 
                                :class="{ 'btn-primary': activeDays.includes('wed'), 'btn-outline-primary': !activeDays.includes('wed') }"
                                @click="toggleActiveDay('wed')"
                            >Wednesday</button>
                            <button 
                                type="button" 
                                class="btn" 
                                :class="{ 'btn-primary': activeDays.includes('thu'), 'btn-outline-primary': !activeDays.includes('thu') }"
                                @click="toggleActiveDay('thu')"
                            >Thursday</button>
                            <button 
                                type="button" 
                                class="btn" 
                                :class="{ 'btn-primary': activeDays.includes('fri'), 'btn-outline-primary': !activeDays.includes('fri') }"
                                @click="toggleActiveDay('fri')"
                            >Friday</button>
                            <button 
                                type="button" 
                                class="btn" 
                                :class="{ 'btn-primary': activeDays.includes('sat'), 'btn-outline-primary': !activeDays.includes('sat') }"
                                @click="toggleActiveDay('sat')"
                            >Saturday</button>
                            <button 
                                type="button" 
                                class="btn" 
                                :class="{ 'btn-primary': activeDays.includes('sun'), 'btn-outline-primary': !activeDays.includes('sun') }"
                                @click="toggleActiveDay('sun')"
                            >Sunday</button>
                        </div>
                        <template v-if="activeDays.length > 0">
                            <template v-for="day in activeDays" :key="day">
                                <input type="hidden" name="active_days[]" :value="day" />
                            </template>
                        </template>
                        <!-- Ensure all critical values are included in form submission -->
                        <input type="hidden" name="type" :value="type" />
                        <input type="hidden" name="title" :value="title" />
                        <input type="hidden" name="can_use_with_promotion" :value="can_use_with_promotion ? 1 : 0" />
                        <input type="hidden" name="can_use_with_flash_sale" :value="can_use_with_flash_sale ? 1 : 0" />
                        <input type="hidden" name="quantity" :value="is_unlimited ? null : quantity" />
                        <input type="hidden" name="unlimited_time" :value="unlimited_time ? 1 : 0" />
                        <input type="hidden" name="target" :value="target" />
                        <input type="hidden" name="min_order_price" :value="min_order_price" />
                        <input type="hidden" name="discount_on" :value="discount_on" />
                        <input type="hidden" name="product_quantity" :value="product_quantity" />
                        <input type="hidden" name="type_option" :value="type_option" />
                        <input type="hidden" name="code" :value="code" />
                        <input type="hidden" name="display_at_checkout" :value="display_at_checkout ? 1 : 0" />
                        <input type="hidden" name="can_use_with_promotion" :value="can_use_with_promotion ? 1 : 0" />
                        <input type="hidden" name="can_use_with_flash_sale" :value="can_use_with_flash_sale ? 1 : 0" />
                        <input type="hidden" name="apply_via_url" :value="apply_via_url ? 1 : 0" />
                        <input type="hidden" name="value" :value="value" />
                        <small class="form-hint">Select days of week when coupon is active</small>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary">{{ __('discount.save') }}</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style lang="scss">
.date-time-group {
    .invalid-feedback {
        position: absolute;
        bottom: -15px;
    }
}
</style>

<script>
import DiscountSearchBoxPagination from './partials/DiscountSearchBoxPagination.vue'
import DiscountListItemRemoveIconButton from './partials/DiscountListItemRemoveIconButton.vue'

const moment = require('moment')

export default {
    components: { DiscountListItemRemoveIconButton, DiscountSearchBoxPagination },
    data: () => {
        return {
            title: null,
            code: null,
            can_use_with_promotion: false,
            can_use_with_flash_sale: false,
            is_unlimited: true,
            apply_via_url: false,
            display_at_checkout: false,
            description: null,
            quantity: 1,
            unlimited_time: true,
            start_date: moment().format('YYYY-MM-DD'),
            start_time: '00:00',
            end_date: moment().format('YYYY-MM-DD'),
            end_time: '23:59',
            dateFormat: 'YYYY-MM-DD',
            type_option: 'amount',
            value: null,
            target: 'all-orders',
            value_label: null,
            variants: {
                data: [],
            },
            selected_variants: [],
            selected_variant_ids: [],
            hidden_product_search_panel: true,
            product_collection_id: null,
            product_collections: [],
            product_category_id: null,
            product_categories: [],
            discount_on: 'per-order',
            min_order_price: 0,
            product_quantity: 1,
            products: {
                data: [],
            },
            selected_products: [],
            selected_product_ids: [],
            product_keyword: null,
            customers: {
                data: [],
            },
            selected_customers: [],
            selected_customer_ids: [],
            customer_keyword: null,
            hidden_customer_search_panel: true,
            loading: false,
            discountUnit: '$',
            activeDays: [],
            dayMap: {
                'Monday': 'mon',
                'Tuesday': 'tue',
                'Wednesday': 'wed',
                'Thursday': 'thu',
                'Friday': 'fri',
                'Saturday': 'sat',
                'Sunday': 'sun'
            },
        }
    },
    props: {
        currency: {
            type: String,
            default: () => null,
            required: true,
        },
        dateFormat: {
            type: String,
            default: () => 'Y-m-d',
            required: false,
        },
        discount: {
            type: Object,
            default: () => null,
        },
    },
    mounted: async function () {
        // Initialize data from discount if it exists
        if (this.discount) {
            // Initialize code and value
            this.code = this.discount.code || null;
            this.value = this.discount.value || null;
            this.title = this.discount.title || null;
            this.type = this.discount.type || 'coupon';
            this.type_option = this.discount.type_option || 'amount';
            this.display_at_checkout = this.discount.display_at_checkout || false;
            this.description = this.discount.description || null;
            this.can_use_with_promotion = this.discount.can_use_with_promotion || false;
            this.can_use_with_flash_sale = this.discount.can_use_with_flash_sale || false;
            this.is_unlimited = !this.discount.quantity;
            this.apply_via_url = this.discount.apply_via_url || false;
            
            // Initialize active days
            if (this.discount.active_days) {
                if (Array.isArray(this.discount.active_days)) {
                    this.activeDays = this.discount.active_days;
                } else if (typeof this.discount.active_days === 'string') {
                    try {
                        this.activeDays = JSON.parse(this.discount.active_days);
                    } catch (e) {
                        console.error('Failed to parse active days:', e);
                        this.activeDays = [];
                    }
                }
            } else {
                this.activeDays = [];
            }
            if (!this.data) {
                let saveData = {
                    active_days: this.activeDays
                }
            }
            this.data.active_days = this.activeDays
        }

        let context = this
        $(document).on('click', 'body', (e) => {
            let container = $('.box-search-advance')

            if (!container.is(e.target) && container.has(e.target).length === 0) {
                context.hidden_product_search_panel = true
                context.hidden_customer_search_panel = true
            }
        })

        this.value_label = this.__('discount.discount')
        this.discountUnit = this.currency

        if (this.discount) {
            this.title = this.discount.title
            this.type = this.discount.type
            this.code = this.discount.code
            
            // Handle dates
            if (this.discount.start_date) {
                const startDate = moment(this.discount.start_date)
                this.start_date = startDate.format('YYYY-MM-DD')
                this.start_time = startDate.format('HH:mm')
            }
            
            if (this.discount.end_date) {
                const endDate = moment(this.discount.end_date)
                this.end_date = endDate.format('YYYY-MM-DD')
                this.end_time = endDate.format('HH:mm')
                this.unlimited_time = false
            } else {
                this.unlimited_time = true
            }
            this.is_promotion = this.type === 'promotion'
            this.can_use_with_promotion = !!this.discount.can_use_with_promotion
            this.can_use_with_flash_sale = !!this.discount.can_use_with_flash_sale
            this.quantity = this.discount.quantity || 1
            this.unlimited_time = !this.discount.end_date
            this.start_date = this.discount.start_date
            this.end_date = this.discount.end_date
            this.type_option = this.discount.type_option
            this.target = this.discount.target
            this.min_order_price = this.discount.min_order_price
            this.discount_on = this.discount.discount_on
            this.value = this.discount.value
            this.product_quantity = this.discount.product_quantity
            this.apply_via_url = !!this.discount.apply_via_url
            this.display_at_checkout = !!this.discount.display_at_checkout
            this.description = this.discount.description
            // Initialize is_unlimited from the discount object
            this.is_unlimited = this.discount.is_unlimited || false
            this.activeDays = Array.isArray(this.discount.active_days) ? this.discount.active_days : (this.discount.active_days ? JSON.parse(this.discount.active_days) : [])

            if (this.discount.product_collections.length > 0) {
                await this.getListProductCollections()

                this.product_collection_id = this.discount.product_collections[0].id
            }

            if (this.discount.products.length > 0) {
                this.discount.products.forEach((product) => {
                    product.product_link = route('products.edit', product.id)
                    this.selected_products.push(product)
                    this.selected_product_ids.push(product.id)
                })
            }

            if (this.discount.customers.length > 0) {
                this.discount.customers.forEach((customer) => {
                    customer.customer_link = route('customers.edit', customer.id)
                    this.selected_customers.push(customer)
                    this.selected_customer_ids.push(customer.id)
                })
            }

            if (this.discount.product_categories.length > 0) {
                await this.getListProductCategories()

                this.product_category_id = this.discount.product_categories[0].id
            }

            this.discount.product_variants.forEach((variant) => {
                variant.product_link = route('products.edit', variant.id)
                variant.product_name = variant.name
                variant.variation_items = variant.variationItems
                this.selected_variants.push(variant)
                this.selected_variant_ids.push(variant.id)
            })

            if (this.type_option === 'shipping') {
                this.handleChangeTypeOption()
            }
        }
    },
    watch: {
        end_date(val) {
            if (val && !this.unlimited_time) {
                // Ensure the date is in the correct format
                const formattedDate = moment(val).format('YYYY-MM-DD')
                if (formattedDate !== val) {
                    this.end_date = formattedDate
                }
            }
        },
        start_date(val) {
            if (val) {
                // Ensure the date is in the correct format
                const formattedDate = moment(val).format('YYYY-MM-DD')
                if (formattedDate !== val) {
                    this.start_date = formattedDate
                }
            }
        }
    },

    methods: {
        generateCouponCode: function (event) {
            event.preventDefault()
            let context = this
            axios
                .post(route('discounts.generate-coupon'))
                .then((res) => {
                    context.code = res.data.data
                    context.title = null
                    $('.coupon-code-input').closest('div').find('.invalid-feedback').remove()
                })
                .catch((res) => {
                    Botble.handleError(res.response.data)
                })
        },
        changeDiscountType: function () {
            let context = this
            if (context.type === 'coupon') {
                context.is_promotion = false
                context.code = context.title
                context.title = null
            } else {
                context.is_promotion = true
                context.title = context.code
                context.code = null
            }
        },
        handleChangeTypeOption: function () {
            let context = this

            context.discountUnit = context.currency
            context.value_label = context.__('discount.discount')

            switch (context.type_option) {
                case 'amount':
                    context.target = 'all-orders'
                    break
                case 'percentage':
                    context.target = 'all-orders'
                    context.discountUnit = '%'
                    break
                case 'shipping':
                    context.value_label = context.__('discount.when_shipping_fee_less_than')
                    context.target = 'all-orders'
                    break
                case 'same-price':
                    context.target = 'group-products'
                    context.value_label = context.__('discount.is')
                    context.getListProductCollections()
                    break
            }
        },
        loadListProductsForSearch: function (include_variation = 1, page = 1, force = false) {
            let context = this
            context.hidden_product_search_panel = false
            $('.textbox-advancesearch').closest('.box-search-advance').find('.panel').removeClass('hidden')
            if (_.isEmpty(context.variants.data) || _.isEmpty(context.products.data) || force) {
                context.loading = true
                axios
                    .get(
                        route('products.get-list-products-for-select', {
                            keyword: context.product_keyword,
                            include_variation: include_variation,
                            page: page,
                        })
                    )
                    .then((res) => {
                        if (include_variation) {
                            context.variants = res.data.data
                        } else {
                            context.products = res.data.data
                        }

                        context.loading = false
                    })
                    .catch((res) => {
                        Botble.handleError(res.response.data)
                    })
            }
        },
        handleSearchProduct: function (include_variation = 1, value) {
            if (value !== this.product_keyword) {
                let context = this
                this.product_keyword = value
                setTimeout(() => {
                    context.loadListProductsForSearch(include_variation, 1, true)
                }, 500)
            }
        },
        handleChangeTarget: function () {
            let context = this
            switch (context.target) {
                case 'group-products':
                    context.getListProductCollections()
                    break
                case 'products-by-category':
                    context.getListProductCategories()
                    break
                case 'specific-product':
                    context.selected_variant_ids = []
                    context.selected_customers = []
                    context.selected_customer_ids = []
                    break
                case 'product-variant':
                    context.selected_products = []
                    context.selected_product_id = []
                    context.selected_customers = []
                    context.selected_customer_ids = []
                    break
                case 'customer':
                    context.selected_products = []
                    context.selected_product_ids = []
                    context.selected_variant_ids = []
                    break
            }
        },
        getListProductCollections: async function () {
            let context = this
            if (_.isEmpty(context.product_collections)) {
                context.loading = true
                await axios
                    .get(route('product-collections.get-list-product-collections-for-select'))
                    .then((res) => {
                        context.product_collections = res.data.data
                        if (!_.isEmpty(res.data.data)) {
                            context.product_collection_id = _.first(res.data.data).id
                        }
                        context.loading = false
                    })
                    .catch((res) => {
                        Botble.handleError(res.response.data)
                    })
            }
        },
        getListProductCategories: async function () {
            let context = this

            if (context.product_categories.length < 1) {
                context.loading = true

                await $httpClient
                    .make()
                    .get(route('product-categories.get-list-product-categories-for-select'))
                    .then(({ data }) => {
                        context.product_categories = data.data
                        if (data.data.length > 0) {
                            context.product_category_id = data.data[0].id
                        }
                    })
                    .catch(({ response }) => Botble.handleError(response.data))
                    .finally(() => (context.loading = false))
            }
        },
        loadListCustomersForSearch: function (page = 1, force = false) {
            let context = this
            context.hidden_customer_search_panel = false
            $('.textbox-advancesearch.customer')
                .closest('.box-search-advance.customer')
                .find('.panel')
                .addClass('active')
            if (_.isEmpty(context.customers.data) || force) {
                context.loading = true
                axios
                    .get(
                        route('customers.get-list-customers-for-search', {
                            keyword: context.customer_keyword,
                            page: page,
                        })
                    )
                    .then((res) => {
                        context.customers = res.data.data
                        context.loading = false
                    })
                    .catch((res) => {
                        Botble.handleError(res.response.data)
                    })
            }
        },
        handleSearchCustomer: function (value) {
            if (value !== this.customer_keyword) {
                let context = this
                this.customer_keyword = value
                setTimeout(() => {
                    context.loadListCustomersForSearch(1, true)
                }, 500)
            }
        },
        handleSelectProducts: function (item) {
            if (!_.includes(this.selected_product_ids, item.id)) {
                item.product_link = route('products.edit', item.id)
                this.selected_products.push(item)
                this.selected_product_ids.push(item.id)
            }
            this.hidden_product_search_panel = true
        },
        handleRemoveProduct: function ($event, currentItem) {
            $event.preventDefault()
            this.selected_product_ids = _.reject(this.selected_product_ids, (item) => {
                return item === currentItem.id
            })

            this.selected_products = _.reject(this.selected_products, (item) => {
                return item.id === currentItem.id
            })
        },
        handleSelectCustomers: function (item) {
            if (!_.includes(this.selected_customer_ids, item.id)) {
                item.customer_link = route('customers.edit', item.id)
                this.selected_customers.push(item)
                this.selected_customer_ids.push(item.id)
            }
            this.hidden_customer_search_panel = true
        },
        handleRemoveCustomer: function ($event, currentItem) {
            $event.preventDefault()
            this.selected_customer_ids = _.reject(this.selected_customer_ids, (item) => {
                return item === currentItem.id
            })

            this.selected_customers = _.reject(this.selected_customers, (item) => {
                return item.id === currentItem.id
            })
        },
        handleSelectVariants: function (productVariant, variation) {
            if (!_.includes(this.selected_variant_ids, variation.product_id)) {
                let variantItem = variation
                variantItem.product_name = productVariant.name
                variantItem.image_url = productVariant.image_url
                variantItem.product_link = route('products.edit', variation.configurable_product_id)
                this.selected_variants.push(variantItem)
                this.selected_variant_ids.push(variation.product_id)
            }
            this.hidden_product_search_panel = true
        },
        handleRemoveVariant: function ($event, variant) {
            $event.preventDefault()
            this.selected_variant_ids = _.reject(this.selected_variant_ids, (item) => {
                return item === variant.product_id
            })

            this.selected_variants = _.reject(this.selected_variants, (item) => {
                return item.product_id === variant.product_id
            })
        },
        toggleActiveDay: function (day) {
            // Day is now directly the code (mon, tue, etc)
            if (this.activeDays.includes(day)) {
                this.activeDays = this.activeDays.filter((d) => d !== day);
            } else {
                this.activeDays.push(day);
            }
            
            // Ensure data.active_days is set
            if (!this.data) {
                this.data = {};
            }
            this.data.active_days = [...this.activeDays];
            
            // Force reactivity update
            this.$forceUpdate();
        },
    },
}
</script>
