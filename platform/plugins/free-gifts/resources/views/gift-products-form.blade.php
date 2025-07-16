<div class="gift-products-form">
    <div class="alert alert-info">
        {{ trans('plugins/free-gifts::gift-rules.gift_products_instructions') }}
    </div>

    <div class="gift-products-container">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>{{ trans('plugins/free-gifts::gift-rules.product') }}</th>
                        <th>{{ trans('plugins/free-gifts::gift-rules.quantity') }}</th>
                        <th>{{ trans('plugins/free-gifts::gift-rules.is_same_product') }}</th>
                        <th>{{ trans('plugins/free-gifts::gift-rules.actions') }}</th>
                    </tr>
                </thead>
                <tbody id="gift-products-table-body">
                    @if (isset($giftRule) && $giftRule->giftProducts->count() > 0)
                        @foreach($giftRule->giftProducts as $product)
                            <tr class="gift-product-row">
                                <td>
                                    <select name="gift_products[{{ $product->id }}][product_id]" class="form-control gift-product-select" disabled>
                                        <option value="{{ $product->id }}" selected>{{ $product->name }}</option>
                                    </select>
                                    <input type="hidden" name="gift_products[{{ $product->id }}][product_id]" value="{{ $product->id }}">
                                </td>
                                <td>
                                    <input type="number" name="gift_products[{{ $product->id }}][quantity]" class="form-control" value="{{ $product->pivot->quantity }}" min="1">
                                </td>
                                <td>
                                    <div class="onoffswitch">
                                        <input type="hidden" name="gift_products[{{ $product->id }}][is_same_product]" value="0">
                                        <input type="checkbox" name="gift_products[{{ $product->id }}][is_same_product]" class="onoffswitch-checkbox" id="is_same_product_{{ $product->id }}" value="1" {{ $product->pivot->is_same_product ? 'checked' : '' }}>
                                        <label class="onoffswitch-label" for="is_same_product_{{ $product->id }}">
                                            <span class="onoffswitch-inner"></span>
                                            <span class="onoffswitch-switch"></span>
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger remove-gift-product"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <div class="form-group">
            <div class="row">
                <div class="col-md-6">
                    <select id="add-gift-product-select" class="form-control">
                        <option value="">{{ trans('plugins/free-gifts::gift-rules.select_product') }}</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" id="add-gift-product-btn" class="btn btn-primary">{{ trans('plugins/free-gifts::gift-rules.add_product') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#add-gift-product-btn').on('click', function() {
            const productId = $('#add-gift-product-select').val();
            if (!productId) {
                return;
            }

            const productName = $('#add-gift-product-select option:selected').text();
            const rowExists = $(`input[name="gift_products[${productId}][product_id]"]`).length > 0;

            if (rowExists) {
                Botble.showError('{{ trans('plugins/free-gifts::gift-rules.product_already_added') }}');
                return;
            }

            const row = `
                <tr class="gift-product-row">
                    <td>
                        <select name="gift_products[${productId}][product_id]" class="form-control gift-product-select" disabled>
                            <option value="${productId}" selected>${productName}</option>
                        </select>
                        <input type="hidden" name="gift_products[${productId}][product_id]" value="${productId}">
                    </td>
                    <td>
                        <input type="number" name="gift_products[${productId}][quantity]" class="form-control" value="1" min="1">
                    </td>
                    <td>
                        <div class="onoffswitch">
                            <input type="hidden" name="gift_products[${productId}][is_same_product]" value="0">
                            <input type="checkbox" name="gift_products[${productId}][is_same_product]" class="onoffswitch-checkbox" id="is_same_product_${productId}" value="1">
                            <label class="onoffswitch-label" for="is_same_product_${productId}">
                                <span class="onoffswitch-inner"></span>
                                <span class="onoffswitch-switch"></span>
                            </label>
                        </div>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger remove-gift-product"><i class="fa fa-trash"></i></button>
                    </td>
                </tr>
            `;

            $('#gift-products-table-body').append(row);
            $('#add-gift-product-select').val('');
        });

        $(document).on('click', '.remove-gift-product', function() {
            $(this).closest('tr').remove();
        });

        // Show/hide fields based on selections
        function toggleFields() {
            const giftType = $('select[name="gift_type"]').val();
            const criteriaType = $('select[name="criteria_type"]').val();
            const productFilterType = $('select[name="product_filter_type"]').val();
            const customerFilterType = $('select[name="customer_filter_type"]').val();
            const requireMinOrders = $('input[name="require_min_orders"]').prop('checked');

            // Gift type specific fields
            $('.gift-products-form').toggle(giftType !== 'coupon_based');

            // Criteria type specific fields
            $('.category-ids-wrapper').toggle(criteriaType === 'category_total');

            // Product filter specific fields
            $('.product-ids-wrapper').toggle(productFilterType === 'specific_products');
            $('.category-ids-wrapper').toggle(productFilterType === 'specific_categories');

            // Customer filter specific fields
            $('.customer-ids-wrapper').toggle(customerFilterType === 'specific_customers');

            // Min orders fields
            $('.min-orders-count-wrapper').toggle(requireMinOrders);
        }

        $('select[name="gift_type"], select[name="criteria_type"], select[name="product_filter_type"], select[name="customer_filter_type"], input[name="require_min_orders"]').on('change', toggleFields);

        toggleFields();
    });
</script>
