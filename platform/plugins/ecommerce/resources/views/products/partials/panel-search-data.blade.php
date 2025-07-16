<x-core::card.body class="p-0">
    <div class="list-search-data">
        <div class="list-group list-group-flush overflow-auto" style="max-height: 25rem;">
            @if (!$availableProducts->isEmpty())
                @foreach ($availableProducts as $availableProduct)
                    <a
                        href="javascript:void(0);"
                        class="list-group-item list-group-item-action selectable-item"
                        data-name="{{ $availableProduct->display_name ?? $availableProduct->name }}"
                        data-image="{{ RvMedia::getImageUrl($availableProduct->image, 'thumb', false, RvMedia::getDefaultImage()) }}"
                        data-id="{{ $availableProduct->id }}"
                        data-url="{{ route('products.edit', $availableProduct->id) }}"
                        data-price="{{ $availableProduct->price }}"
                        data-is-variation="{{ $availableProduct->is_variation ? '1' : '0' }}"
                        @if ($availableProduct->is_variation && isset($availableProduct->configurable_product_id))
                            data-parent-id="{{ $availableProduct->configurable_product_id }}"
                        @endif
                    >
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar" style="background-image: url('{{ RvMedia::getImageUrl($availableProduct->image, 'thumb', false, RvMedia::getDefaultImage()) }}')"></span>
                            </div>
                            <div class="col text-truncate">
                                <h4 class="text-body d-block mb-0">
                                    {{ $availableProduct->display_name ?? $availableProduct->name }}
                                    @if ($availableProduct->is_variation)
                                        <span class="badge bg-info text-white">{{ __('Variation') }}</span>
                                    @endif
                                </h4>
                                <small class="d-block text-muted text-truncate mt-n1">
                                    {{ $availableProduct->sku }}
                                    @if ($availableProduct->price != $availableProduct->sale_price && $availableProduct->sale_price > 0)
                                        <del>{{ format_price($availableProduct->price) }}</del>
                                        <span class="text-success">{{ format_price($availableProduct->sale_price) }}</span>
                                    @else
                                        <span>{{ format_price($availableProduct->price) }}</span>
                                    @endif
                                </small>
                            </div>
                        </div>
                    </a>
                @endforeach
            @else
                <div class="p-3">
                    <p class="text-muted my-0">{{ __('plugins/ecommerce::products.form.no_results') }}</p>
                </div>
            @endif
        </div>
    </div>
</x-core::card.body>

@if ($availableProducts->hasPages())
    <x-core::card.footer class="pb-0 d-flex justify-content-end">
        {{ $availableProducts->links() }}
    </x-core::card.footer>
@endif
