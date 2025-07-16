<div class="tab-pane" id="tab-mix-and-match">
    <div class="form-group mb-3">
        <label class="form-label">
            <input type="checkbox" name="is_mix_and_match" value="1" @if ($product && $product->isMixAndMatch()) checked @endif class="form-check-input" id="is_mix_and_match">
            {{ trans('plugins/mix-and-match::mix-and-match.is_mix_and_match_product') }}
        </label>
        <small class="form-text text-muted">
            {{ trans('plugins/mix-and-match::mix-and-match.is_mix_and_match_product_description') }}
        </small>
    </div>

    <div id="mix_and_match_configuration" @if (!$product || !$product->isMixAndMatch()) style="display: none;" @endif>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label for="min_container_size" class="form-label">{{ trans('plugins/mix-and-match::mix-and-match.minimum_container_size') }}</label>
                    <input type="number" name="min_container_size" id="min_container_size" class="form-control" min="1" value="{{ $product && $product->mixAndMatchSetting ? $product->mixAndMatchSetting->min_container_size : 1 }}">
                    <small class="form-text text-muted">{{ trans('plugins/mix-and-match::mix-and-match.minimum_container_size_description') }}</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label for="max_container_size" class="form-label">{{ trans('plugins/mix-and-match::mix-and-match.maximum_container_size') }}</label>
                    <input type="number" name="max_container_size" id="max_container_size" class="form-control" min="1" value="{{ $product && $product->mixAndMatchSetting ? $product->mixAndMatchSetting->max_container_size : '' }}">
                    <small class="form-text text-muted">{{ trans('plugins/mix-and-match::mix-and-match.maximum_container_size_description') }}</small>
                </div>
            </div>
        </div>

        <div class="form-group mb-3">
            <label class="form-label">{{ trans('plugins/mix-and-match::mix-and-match.pricing_type') }}</label>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="pricing_type" id="pricing_type_fixed" value="fixed_price" @if ($product && $product->mixAndMatchSetting && $product->mixAndMatchSetting->pricing_type == 'fixed_price') checked @endif>
                <label class="form-check-label" for="pricing_type_fixed">
                    {{ trans('plugins/mix-and-match::mix-and-match.fixed_price') }}
                </label>
                <small class="form-text text-muted">{{ trans('plugins/mix-and-match::mix-and-match.fixed_price_description') }}</small>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="pricing_type" id="pricing_type_per_item" value="per_item" @if (!$product || !$product->mixAndMatchSetting || $product->mixAndMatchSetting->pricing_type == 'per_item') checked @endif>
                <label class="form-check-label" for="pricing_type_per_item">
                    {{ trans('plugins/mix-and-match::mix-and-match.per_item_pricing') }}
                </label>
                <small class="form-text text-muted">{{ trans('plugins/mix-and-match::mix-and-match.per_item_pricing_description') }}</small>
            </div>
        </div>

        <div id="fixed_price_container" class="form-group mb-3" @if (!$product || !$product->mixAndMatchSetting || $product->mixAndMatchSetting->pricing_type != 'fixed_price') style="display: none;" @endif>
            <label for="fixed_price" class="form-label">{{ trans('plugins/mix-and-match::mix-and-match.fixed_price_amount') }}</label>
            <input type="number" name="fixed_price" id="fixed_price" class="form-control" min="0" step="0.01" value="{{ $product && $product->mixAndMatchSetting ? $product->mixAndMatchSetting->fixed_price : '' }}">
        </div>

        <div class="form-group mb-3">
            <label class="form-label">{{ trans('plugins/mix-and-match::mix-and-match.select_products') }}</label>
            <div class="select-products-container">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="search_products" placeholder="{{ trans('plugins/mix-and-match::mix-and-match.search_for_products') }}">
                    <button class="btn btn-outline-secondary" type="button" id="search_products_button">{{ trans('plugins/mix-and-match::mix-and-match.search') }}</button>
                </div>
                
                <div class="selected-products-table">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>{{ trans('plugins/mix-and-match::mix-and-match.thumbnail') }}</th>
                                <th>{{ trans('plugins/mix-and-match::mix-and-match.products') }}</th>
                                <th>{{ trans('plugins/mix-and-match::mix-and-match.price') }}</th>
                                <th>{{ trans('plugins/mix-and-match::mix-and-match.min_qty') }}</th>
                                <th>{{ trans('plugins/mix-and-match::mix-and-match.max_qty') }}</th>
                                <th>{{ trans('plugins/mix-and-match::mix-and-match.action') }}</th>
                            </tr>
                        </thead>
                        <tbody id="selected_products">
                            @if ($product && $product->mixAndMatchItems && $product->mixAndMatchItems->count() > 0)
                                @foreach($product->mixAndMatchItems as $item)
                                    <tr data-id="{{ $item->child_product_id }}">
                                        <td><img src="{{ $item->childProduct->image ? RvMedia::getImageUrl($item->childProduct->image, 'thumb') : '/vendor/core/core/base/images/placeholder.png' }}" width="50" alt="{{ $item->childProduct->name }}"></td>
                                        <td>{{ $item->childProduct->name }}</td>
                                        <td>{{ format_price($item->childProduct->price) }}</td>
                                        <td><input type="number" name="mix_and_match_items[{{ $item->child_product_id }}][min_qty]" class="form-control" min="0" value="{{ $item->min_qty }}"></td>
                                        <td><input type="number" name="mix_and_match_items[{{ $item->child_product_id }}][max_qty]" class="form-control" min="1" value="{{ $item->max_qty }}"></td>
                                        <td><button type="button" class="btn btn-danger remove-product-btn"><i class="fa fa-trash"></i></button></td>
                                    </tr>
                                @endforeach
                            @else
                                <tr class="no-products-selected">
                                    <td colspan="6" class="text-center">{{ trans('plugins/mix-and-match::mix-and-match.no_products_selected') }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
