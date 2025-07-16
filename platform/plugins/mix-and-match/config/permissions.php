<?php

return [
    [
        'name' => 'Mix and Match',
        'flag' => 'mix-and-match.index',
    ],
    [
        'name' => 'Create',
        'flag' => 'mix-and-match.create',
        'parent_flag' => 'mix-and-match.index',
    ],
    [
        'name' => 'Edit',
        'flag' => 'mix-and-match.edit',
        'parent_flag' => 'mix-and-match.index',
    ],
    [
        'name' => 'Delete',
        'flag' => 'mix-and-match.destroy',
        'parent_flag' => 'mix-and-match.index',
    ],
];
