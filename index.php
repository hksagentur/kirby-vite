<?php

require __DIR__ . '/config/helpers.php';

Kirby::plugin('hksagentur/vite', [
    'options' => [
        'server' => [
            'host' => getenv('VITE_HOST'),
            'port' => getenv('VITE_PORT'),
        ],
        'client' => [
            'host' => getenv('VITE_CLIENT_HOST') ?: getenv('VITE_HOST'),
            'port' => getenv('VITE_CLIENT_PORT') ?: getenv('VITE_PORT'),
            'plugins' => [],
        ],
    ],
]);
