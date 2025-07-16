<?php

namespace Botble\FreeGifts\Models;

use Botble\Base\Models\BaseModel;

class Setting extends BaseModel
{
    protected $table = 'fg_settings';

    protected $fillable = [
        'key',
        'value',
    ];
}
