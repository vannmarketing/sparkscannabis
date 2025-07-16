<?php

namespace Botble\FreeGifts\Models;

use Botble\Base\Casts\SafeContent;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Models\BaseModel;
use Botble\Ecommerce\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GiftRule extends BaseModel
{
    protected $table = 'fg_gift_rules';

    protected $fillable = [
        'name',
        'description',
        'status',
        'gift_type',
        'criteria_type',
        'criteria_value',
        'start_date',
        'end_date',
        'active_days',
        'max_gifts_per_order',
        'max_gifts_per_customer',
        'max_gifts_total',
        'require_customer_login',
        'allow_coupon',
        'require_min_orders',
        'min_orders_count',
        'product_filter_type',
        'product_ids',
        'category_ids',
        'customer_filter_type',
        'customer_ids',
    ];

    protected $casts = [
        'name' => SafeContent::class,
        'description' => SafeContent::class,
        'status' => BaseStatusEnum::class,
        'active_days' => 'array',
        'product_ids' => 'array',
        'category_ids' => 'array',
        'customer_ids' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function giftProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'fg_gift_rule_products', 'gift_rule_id', 'product_id')
            ->withPivot(['quantity', 'is_same_product']);
    }

    public function isActive(): bool
    {
        if ($this->status !== BaseStatusEnum::PUBLISHED) {
            return false;
        }

        $now = Carbon::now();

        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        if ($this->active_days && count($this->active_days) > 0) {
            $currentDay = strtolower($now->format('D'));
            if (!in_array($currentDay, $this->active_days)) {
                return false;
            }
        }

        if ($this->max_gifts_total && GiftLog::where('gift_rule_id', $this->id)->count() >= $this->max_gifts_total) {
            return false;
        }

        return true;
    }
}
