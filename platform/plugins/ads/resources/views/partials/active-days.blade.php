<div class="mb-3" id="active-days-container">
    <label class="form-label">Active days of week</label>
    <div class="d-flex flex-wrap gap-2">
        @php
            // Ensure active_days is always an array
            $activeDays = old('active_days', $ads->active_days ?? []);
            if (!is_array($activeDays)) {
                $activeDays = [];
            }
            $days = [
                'mon' => 'Monday',
                'tue' => 'Tuesday',
                'wed' => 'Wednesday',
                'thu' => 'Thursday',
                'fri' => 'Friday',
                'sat' => 'Saturday',
                'sun' => 'Sunday',
            ];
        @endphp

        @foreach($days as $dayCode => $dayName)
            <div class="form-check form-check-inline">
                <input 
                    class="form-check-input" 
                    type="checkbox" 
                    name="active_days[]" 
                    value="{{ $dayCode }}" 
                    id="active_day_{{ $dayCode }}"
                    {{ in_array($dayCode, $activeDays) ? 'checked' : '' }}
                >
                <label class="form-check-label" for="active_day_{{ $dayCode }}">
                    {{ $dayName }}
                </label>
            </div>
        @endforeach
    </div>
    <div class="form-hint">Select days of week when ad is active</div>
</div>
