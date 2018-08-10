<?php

return [
    'route' => [
        'middleware' => ['web'],
        'namespace'  => 'Manage',
        'prefix'     => 'manage',
        'as'         => 'manage.',
    ],
    'view' => [
        'hintpath'  => 'laraman',
        'layout'    => 'laraman::layout',  //  blade to use for layouts
        'icon_up'   => 'fa fa-chevron-up',
        'icon_down' => 'fa fa-chevron-down',
    ],
    'limit' => 50,
];