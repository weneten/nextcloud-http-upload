<?php
return [
    'routes' => [
        [
            'name' => 'upload#uploadChunk',
            'url' => '/upload/chunk',
            'verb' => 'POST',
        ],
        [
            'name' => 'upload#assembleFile',
            'url' => '/upload/assemble',
            'verb' => 'POST',
        ],
        [
            'name' => 'page#index',
            'url' => '/',
            'verb' => 'GET',
        ],
    ]
];