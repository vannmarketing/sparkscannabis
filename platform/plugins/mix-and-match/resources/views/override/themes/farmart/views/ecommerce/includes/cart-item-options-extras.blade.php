@props(['options'])

@if (Arr::get($options, 'is_mix_and_match') && ($items = Arr::get($options, 'mix_and_match_items')))
    <div class="mix-match-items-list variation-group">
        <p class="mb-0">
            <small>
                <strong>{{ trans('plugins/mix-and-match::mix-and-match.selected_items') }}:</strong>
                <ul style="margin: 5px 0 0 15px; padding: 0; list-style-type: none;">
                    @foreach ($items as $item)
                        <li style="margin-bottom: 3px;">
                            <span>{{ Arr::get($item, 'name') }} &times; {{ Arr::get($item, 'qty') }}</span>
                            @if ($attributes = Arr::get($item, 'attributes'))
                                <span style="color: #6c757d; font-size: 0.9em;"> - {{ $attributes }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </small>
        </p>
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
