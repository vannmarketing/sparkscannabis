<?php

return [
    [
        'name' => 'Free Gifts',
        'flag' => 'free-gifts.index',
    ],
    [
        'name' => 'Create',
        'flag' => 'free-gifts.create',
        'parent_flag' => 'free-gifts.index',
    ],
    [
        'name' => 'Edit',
        'flag' => 'free-gifts.edit',
        'parent_flag' => 'free-gifts.index',
    ],
    [
        'name' => 'Delete',
        'flag' => 'free-gifts.destroy',
        'parent_flag' => 'free-gifts.index',
    ],
    [
        'name' => 'Rules',
        'flag' => 'gift-rules.index',
        'parent_flag' => 'free-gifts.index',
    ],
    [
        'name' => 'Create',
        'flag' => 'gift-rules.create',
        'parent_flag' => 'gift-rules.index',
    ],
    [
        'name' => 'Edit',
        'flag' => 'gift-rules.edit',
        'parent_flag' => 'gift-rules.index',
    ],
    [
        'name' => 'Delete',
        'flag' => 'gift-rules.destroy',
        'parent_flag' => 'gift-rules.index',
    ],
    [
        'name' => 'Manual Gifts',
        'flag' => 'manual-gifts.index',
        'parent_flag' => 'free-gifts.index',
    ],
    [
        'name' => 'Send Gift',
        'flag' => 'manual-gifts.send',
        'parent_flag' => 'manual-gifts.index',
    ],
    [
        'name' => 'Settings',
        'flag' => 'free-gifts.settings',
        'parent_flag' => 'free-gifts.index',
    ],
];
