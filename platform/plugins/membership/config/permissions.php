<?php

return [
    [
        'name' => 'Memberships',
        'flag' => 'membership.index',
    ],
    [
        'name' => 'Create',
        'flag' => 'membership.create',
        'parent_flag' => 'membership.index',
    ],
    [
        'name' => 'Edit',
        'flag' => 'membership.edit',
        'parent_flag' => 'membership.index',
    ],
    [
        'name' => 'Delete',
        'flag' => 'membership.destroy',
        'parent_flag' => 'membership.index',
    ],
    [
        'name' => 'Memberships Settings',
        'flag' => 'membership.settings',
        'parent_flag' => 'plugins.membership',
    ],
];
