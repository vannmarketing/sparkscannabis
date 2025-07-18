<?php

namespace Botble\Ads\Models;

use Botble\Ads\Database\Factories\AdsFactory;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Models\BaseModel;
use Botble\Media\Facades\RvMedia;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ads extends BaseModel
{
    use HasFactory;
    protected $table = 'ads';

    protected $fillable = [
        'name',
        'key',
        'status',
        'open_in_new_tab',
        'expired_at',
        'location',
        'image',
        'tablet_image',
        'mobile_image',
        'url',
        'clicked',
        'order',
        'ads_type',
        'google_adsense_slot_id',
        'active_days',
    ];

    protected $casts = [
        'status' => BaseStatusEnum::class,
        'expired_at' => 'date',
        'open_in_new_tab' => 'boolean',
        'active_days' => 'array',
    ];

    public function scopeNotExpired(Builder $query): Builder
    {
        $today = strtolower(Carbon::now()->format('D'));
        
        return $query->whereDate('expired_at', '>=', Carbon::now())
            ->where(function ($query) use ($today) {
                $query->whereNull('active_days')
                    ->orWhere('active_days', '[]')
                    ->orWhereRaw("JSON_CONTAINS(active_days, '\"$today\"')");
            });
    }

    protected function randomHash(): Attribute
    {
        return Attribute::get(fn () => hash('sha1', $this->key . $this->getKey()));
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(
            function (): ?string {
                if (config('plugins.ads.general.use_real_image_url')) {
                    return RvMedia::getImageUrl($this->image);
                }

                return $this->parseImageUrl();
            }
        );
    }

    protected function tabletImageUrl(): Attribute
    {
        return Attribute::get(
            function (): ?string {
                if (config('plugins.ads.general.use_real_image_url')) {
                    return RvMedia::getImageUrl($this->tablet_image ?: $this->image);
                }

                return $this->parseImageUrl('tablet');
            }
        );
    }

    protected function mobileImageUrl(): Attribute
    {
        return Attribute::get(
            function (): ?string {
                if (config('plugins.ads.general.use_real_image_url')) {
                    return RvMedia::getImageUrl(($this->mobile_image ?: $this->tablet_image) ?: $this->image);
                }

                return $this->parseImageUrl('mobile');
            }
        );
    }

    protected function clickUrl(): Attribute
    {
        return Attribute::get(
            fn ($_, array $attributes = []): string =>
                route('public.ads-click.alternative', [
                    'randomHash' => $this->random_hash,
                    'adsKey' => $attributes['key'],
                ])
        );
    }

    public function parseImageUrl(string $size = 'default'): string
    {
        return route('public.ads-click.image', [
            'randomHash' => $this->random_hash,
            'adsKey' => $this->key,
            'size' => $size,
            'hashName' => md5($this->key),
        ]);
    }

    protected static function newFactory()
    {
        return AdsFactory::new();
    }
}
