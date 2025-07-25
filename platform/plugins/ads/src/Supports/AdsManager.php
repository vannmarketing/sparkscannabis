<?php

namespace Botble\Ads\Supports;

use Botble\Ads\Events\AdsLoading;
use Botble\Ads\Models\Ads;
use Botble\Base\Enums\BaseStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AdsManager
{
    protected Collection $data;

    protected bool $loaded = false;

    protected array $locations = [];

    public function __construct()
    {
        $this->locations = [
            'not_set' => trans('plugins/ads::ads.not_set'),
        ];
    }

    public function display(string $location, array $attributes = []): string
    {
        $this->load();

        $data = $this
            ->filterExpired($this->data)
            ->where('location', $location)
            ->sortBy('order');

        if ($data->isNotEmpty()) {
            $data = $data->random(1);
        }

        return view('plugins/ads::partials.ad-display', compact('data', 'attributes'))->render();
    }

    public function load(bool $force = false, array $with = []): self
    {
        if (! $this->loaded || $force) {
            $this->data = $this->read($with);
            $this->loaded = true;

            AdsLoading::dispatch($this->data);
        }

        return $this;
    }

    protected function read(array $with): Collection
    {
        return Ads::query()->with($with)->get();
    }

    public function locationHasAds(string $location): bool
    {
        $this->load();

        return $this
            ->filterExpired($this->data)
            ->where('location', $location)
            ->sortBy('order')
            ->isNotEmpty();
    }

    public function displayAds(?string $key, array $attributes = []): ?string
    {
        if (! $key) {
            return null;
        }

        $this->load();

        $ads = $this
            ->filterExpired($this->data)
            ->where('key', $key)
            ->first();

        if (! $ads) {
            return null;
        }

        $data = [$ads];

        if (! isset($attributes['style'])) {
            $attributes['style'] = 'text-align: center;';
        }

        return view('plugins/ads::partials.ad-display', compact('data', 'attributes'))->render();
    }

    public function getData(bool $isLoad = false, bool $isNotExpired = false): Collection
    {
        if ($isLoad || ! isset($this->data)) {
            $this->load();
        }

        if ($isNotExpired) {
            return $this->filterExpired($this->data);
        }

        return $this->data;
    }

    public function registerLocation(string $key, string $name): self
    {
        $this->locations[$key] = $name;

        return $this;
    }

    public function getLocations(): array
    {
        return apply_filters('ads_locations', $this->locations);
    }

    public function getAds(string $key): ?Ads
    {
        if (! $key) {
            return null;
        }

        $ads = $this->getData(true)->firstWhere('key', $key);

        if (! $ads || ! $ads->image) {
            return null;
        }

        return $ads;
    }

    protected function filterExpired(Collection $data): Collection
    {
        $today = strtolower(Carbon::now()->format('D'));
        
        return $data
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->filter(function (Ads $item) use ($today) {
                // Check if ad is not expired
                $notExpired = $item->ads_type === 'google_adsense' || $item->expired_at->gte(Carbon::now());
                
                // Check if ad is active for today
                $isActiveToday = true;
                if (!empty($item->active_days) && is_array($item->active_days) && count($item->active_days) > 0) {
                    $isActiveToday = in_array($today, $item->active_days);
                }
                
                return $notExpired && $isActiveToday;
            });
    }
}
