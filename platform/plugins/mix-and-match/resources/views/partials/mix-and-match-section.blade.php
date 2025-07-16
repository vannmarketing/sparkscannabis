<x-core::card class="mb-3">
    <x-core::card.header>
        <x-core::card.title>
            {{ trans('plugins/mix-and-match::mix-and-match.name') }}
        </x-core::card.title>
    </x-core::card.header>

    <x-core::card.body>
        <div class="form-group mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_mix_and_match" id="is_mix_and_match" value="1" @if ($product && $product->isMixAndMatch()) checked @endif onclick="toggleMixAndMatchConfig()">
                <label class="form-check-label" for="is_mix_and_match">
                    {{ trans('plugins/mix-and-match::mix-and-match.is_mix_and_match_product') }}
                </label>
            </div>
            <small class="form-text text-muted">
                {{ trans('plugins/mix-and-match::mix-and-match.is_mix_and_match_product_description') }}
            </small>
        </div>

        <div id="mix_and_match_configuration" @if (!$product || !$product->isMixAndMatch()) style="display: none;" @endif>
        
        <script>
            function toggleMixAndMatchConfig() {
                var checkbox = document.getElementById('is_mix_and_match');
                var configSection = document.getElementById('mix_and_match_configuration');
                if (checkbox.checked) {
                    configSection.style.display = 'block';
                } else {
                    configSection.style.display = 'none';
                }
            }
            
            // Run on page load
            document.addEventListener('DOMContentLoaded', function() {
                toggleMixAndMatchConfig();
            });
        </script>
            <div class="row mt-3">
                <div class="col-md-6">
                    <x-core::form.text-input
                        :label="trans('plugins/mix-and-match::mix-and-match.minimum_container_size')"
                        name="min_container_size"
                        id="min_container_size"
                        type="number"
                        min="1"
                        :value="$product && $product->mixAndMatchSetting ? $product->mixAndMatchSetting->min_container_size : 1"
                        :helper-text="trans('plugins/mix-and-match::mix-and-match.minimum_container_size_description')"
                    />
                </div>
                <div class="col-md-6">
                    <x-core::form.text-input
                        :label="trans('plugins/mix-and-match::mix-and-match.maximum_container_size')"
                        name="max_container_size"
                        id="max_container_size"
                        type="number"
                        min="1"
                        :value="$product && $product->mixAndMatchSetting ? $product->mixAndMatchSetting->max_container_size : ''"
                        :helper-text="trans('plugins/mix-and-match::mix-and-match.maximum_container_size_description')"
                    />
                </div>
            </div>

            <div class="form-group mb-3 mt-3">
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
                <x-core::form.text-input
                    :label="trans('plugins/mix-and-match::mix-and-match.fixed_price_amount')"
                    name="fixed_price"
                    id="fixed_price"
                    type="number"
                    min="0"
                    step="0.01"
                    :value="$product && $product->mixAndMatchSetting ? $product->mixAndMatchSetting->fixed_price : ''"
                />
            </div>

            <div class="form-group mb-3 mt-3">
                <label class="form-label">{{ trans('plugins/mix-and-match::mix-and-match.select_products') }}</label>
                <div class="select-products-container">
                    <div class="position-relative mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control" id="search_products" placeholder="{{ trans('plugins/mix-and-match::mix-and-match.search_for_products') }}" autocomplete="off">
                            <button class="btn btn-outline-secondary" type="button" id="search_products_button">Search</button>
                            <div class="position-absolute top-50 start-0 translate-middle-y ps-3 d-none searching-spinner">
                                <div class="spinner-border spinner-border-sm text-muted" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                        <div id="search-debug" class="mt-2"></div>
                        <div class="list-search-data mt-2"></div>
                    </div>
                    
                    <div class="selected-products-table">
                        <x-core::table>
                            <x-core::table.header>
                                <x-core::table.header.cell>{{ trans('plugins/mix-and-match::mix-and-match.thumbnail') }}</x-core::table.header.cell>
                                <x-core::table.header.cell>{{ trans('plugins/mix-and-match::mix-and-match.products') }}</x-core::table.header.cell>
                                <x-core::table.header.cell>{{ trans('plugins/mix-and-match::mix-and-match.price') }}</x-core::table.header.cell>
                                <x-core::table.header.cell>{{ trans('plugins/mix-and-match::mix-and-match.min_qty') }}</x-core::table.header.cell>
                                <x-core::table.header.cell>{{ trans('plugins/mix-and-match::mix-and-match.max_qty') }}</x-core::table.header.cell>
                                <x-core::table.header.cell>{{ trans('plugins/mix-and-match::mix-and-match.action') }}</x-core::table.header.cell>
                            </x-core::table.header>
                            <x-core::table.body id="selected_products">
                                @if ($product && $product->mixAndMatchItems && $product->mixAndMatchItems->count() > 0)
                                    @foreach($product->mixAndMatchItems as $item)
                                        <x-core::table.body.row data-id="{{ $item->child_product_id }}">
                                            <x-core::table.body.cell>
                                                <img src="{{ $item->childProduct->image ? RvMedia::getImageUrl($item->childProduct->image, 'thumb') : '/vendor/core/core/base/images/placeholder.png' }}" width="50" alt="{{ $item->childProduct->name }}">
                                            </x-core::table.body.cell>
                                            <x-core::table.body.cell>{{ $item->childProduct->name }}</x-core::table.body.cell>
                                            <x-core::table.body.cell>{{ format_price($item->childProduct->price) }}</x-core::table.body.cell>
                                            <x-core::table.body.cell>
                                                <input type="number" name="mix_and_match_items[{{ $item->child_product_id }}][min_qty]" class="form-control" min="0" value="{{ $item->min_qty }}">
                                            </x-core::table.body.cell>
                                            <x-core::table.body.cell>
                                                <input type="number" name="mix_and_match_items[{{ $item->child_product_id }}][max_qty]" class="form-control" min="1" value="{{ $item->max_qty }}">
                                            </x-core::table.body.cell>
                                            <x-core::table.body.cell>
                                                <x-core::button type="button" color="danger" class="remove-product-btn" icon="ti ti-trash" />
                                            </x-core::table.body.cell>
                                        </x-core::table.body.row>
                                    @endforeach
                                @else
                                    <x-core::table.body.row class="no-products-selected">
                                        <x-core::table.body.cell colspan="6" class="text-center">{{ trans('plugins/mix-and-match::mix-and-match.no_products_selected') }}</x-core::table.body.cell>
                                    </x-core::table.body.row>
                                @endif
                            </x-core::table.body>
                        </x-core::table>
                    </div>
                </div>
            </div>
        </div>
    </x-core::card.body>
</x-core::card>
