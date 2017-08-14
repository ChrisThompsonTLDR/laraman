<?php

return [
    'route' => [
        'middleware' => ['web'],
        'namespace'  => 'Manage',
        'prefix'     => 'manage',
    ],
    'view' => [
        'hintpath' => 'laraman',
        'layout'   => 'laraman::layout',  //  blade to use for layouts
    ],
    'limit' => 50,
];