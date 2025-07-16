<?php

namespace Botble\MixAndMatch\Traits;

use Botble\MixAndMatch\Models\MixAndMatchSetting;

trait HasMixAndMatch
{
    public function mixAndMatchSetting()
    {
        return $this->hasOne(MixAndMatchSetting::class, 'product_id');
    }

    public function isMixAndMatch(): bool
    {
        if (!$this->exists) {
            return false;
        }

        if (!isset($this->mixAndMatchSetting)) {
            $this->load('mixAndMatchSetting');
        }

        return $this->mixAndMatchSetting !== null;
    }
}
