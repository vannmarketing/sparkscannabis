{{-- Only show debug info in development --}}
@if(config('app.env') === 'local' && config('app.debug'))
<div style="display: none;">
    <pre>{{ json_encode($options, JSON_PRETTY_PRINT) }}</pre>
</div>
@endif

@if (!empty($options['attributes']) && !empty($options['is_mix_and_match']))
    <div class="mix-match-items-list">
        {!! nl2br(e($options['attributes'])) !!}
    </div>
@endif
