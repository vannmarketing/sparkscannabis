<?php

namespace Botble\FreeGifts\Models;

use Botble\Base\Models\BaseModel;
use Botble\Ecommerce\Models\Order;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftLog extends BaseModel
{
    protected $table = 'fg_gift_logs';

    protected $fillable = [
        'gift_rule_id',
        'order_id',
        'customer_id',
        'product_id',
        'quantity',
        'gift_type',
        'is_manual',
    ];

    public function giftRule(): BelongsTo
    {
        return $this->belongsTo(GiftRule::class, 'gift_rule_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
