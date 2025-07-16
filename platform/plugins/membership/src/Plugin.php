<?php

namespace Botble\Membership;

use Illuminate\Support\Facades\Schema;
use Botble\PluginManagement\Abstracts\PluginOperationAbstract;

class Plugin extends PluginOperationAbstract
{
    public static function remove(): void
    {
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('memberships_translations');
    }
}
