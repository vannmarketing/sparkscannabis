<script>
    window.freeGiftsSettings = {!! json_encode($settings) !!};
    window.trans = window.trans || {};
    window.trans['plugins.free-gifts.no_eligible_gifts'] = '{{ trans('plugins/free-gifts::free-gifts.no_eligible_gifts') }}';
</script>
