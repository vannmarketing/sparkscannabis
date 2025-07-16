@if (!empty($cartItem->options['is_mix_and_match']) && !empty($cartItem->options['mix_and_match_items']))
    <div class="mix-and-match-items">
        <div class="mix-and-match-title">
            <strong>{{ __('Mix and Match Items') }}</strong>
        </div>
        <ul class="mix-and-match-list">
            @foreach($cartItem->options['mix_and_match_items'] as $item)
                <li class="mix-and-match-item">
                    @if(!empty($item['image']))
                        <div class="mix-and-match-item-image">
                            <img src="{{ RvMedia::getImageUrl($item['image'], 'thumb', false, RvMedia::getDefaultImage()) }}" alt="{{ $item['name'] }}">
                        </div>
                    @endif
                    <div class="mix-and-match-item-details">
                        <span class="mix-and-match-item-name">{{ $item['name'] }}</span>
                        @if(!empty($item['attributes']))
                            <span class="mix-and-match-item-attributes">{{ $item['attributes'] }}</span>
                        @endif
                        <span class="mix-and-match-item-qty">× {{ $item['qty'] }}</span>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endif
