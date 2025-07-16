<?php

namespace Botble\MixAndMatch\Models;

use Botble\Base\Models\BaseModel;
use Botble\Ecommerce\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MixAndMatchProduct extends BaseModel
{
    protected $table = 'mix_and_match_products';

    protected $fillable = [
        'container_product_id',
        'child_product_id',
        'min_qty',
        'max_qty',
    ];

    public function containerProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'container_product_id');
    }

    public function childProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'child_product_id');
    }
}
