<?php

return [
    // mode, sandbox or live
    'mode'        => env('TREXNODES_MODE', 'sandbox'),
    'url'         => env('TREXNODES_LIVEURL', 'https://gateway.trexnodes.com'),
    'sandbox-url' => env('TREXNODES_SANDBOXURL', 'https://sandbox.trexnodes.com'),
    'client'      => [
        'id'     => env('TREXNODES_CLIENTID', null),
        'secret' => env('TREXNODES_SECRET', null),
        'token'  => env('TREXNODES_TOKEN', null),
    ],
];