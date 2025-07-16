@props(['options'])

@if (Arr::get($options, 'description'))
    {!! Arr::get($options, 'description') !!}
@elseif (Arr::get($options, 'is_mix_and_match') && ($items = Arr::get($options, 'mix_and_match_items')))
    <div class="mix-match-items-list">
        <strong>{{ trans('plugins/mix-and-match::mix-and-match.selected_items') }}:</strong>
        <ul>
            @foreach ($items as $item)
                <li>
                    <span class="product-name">{{ Arr::get($item, 'name') }}</span> 
                    <span class="quantity">&times; {{ Arr::get($item, 'qty') }}</span>
                    @if ($attributes = Arr::get($item, 'attributes'))
                        <span class="attributes">{{ $attributes }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Add any other default 'extras' rendering logic here if needed from the original theme file --}}
{{-- Example: Displaying other 'extras' if they exist --}}
@if ($extras = Arr::get($options, 'extras'))
    @foreach ($extras as $extra)
        @if (!empty($extra['key']) && !empty($extra['value']))
            <p class="mb-0">
                <small>{{ $extra['key'] }}: <strong>{{ $extra['value'] }}</strong></small>
            </p>
        @endif
    @endforeach
@endif
