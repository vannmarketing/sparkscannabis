<?php

namespace Botble\MixAndMatch\Models;

use Botble\Base\Models\BaseModel;
use Botble\Ecommerce\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MixAndMatchSetting extends BaseModel
{
    protected $table = 'mix_and_match_settings';

    protected $fillable = [
        'product_id',
        'min_container_size',
        'max_container_size',
        'pricing_type',
        'fixed_price',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
