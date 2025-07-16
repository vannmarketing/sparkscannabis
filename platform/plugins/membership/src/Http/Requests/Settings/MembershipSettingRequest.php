<?php

namespace Botble\Membership\Http\Requests\Settings;

use Botble\Base\Rules\OnOffRule;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class MembershipSettingRequest extends Request
{
    public function rules(): array
    {
        return [
            'display_page' => new OnOffRule(),
    'display_catalog' => new OnOffRule(),
    'display_tag' => new OnOffRule(),
    'display_products' => new OnOffRule(),
        'display_blog' => new OnOffRule(),
            'display_homepage' => new OnOffRule()
        ];
    }
}
