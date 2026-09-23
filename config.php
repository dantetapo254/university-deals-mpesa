<?php
// config.php

return [
    'mpesa' => [
        'environment' => 'sandbox',
        'consumer_key' => '3RxJjJ4lNNjcxnya1o0d56RIIf9cH48znxU9l4OGDS9KQBwg',
        'consumer_secret' => 'IAeTl2fQgV4T14FAXebrNb6RzZ2ihTeI8E94azcAuuUVVgSkzeS1pgr8ePAmKLSh',
        'shortcode' => '174379',
        'passkey' => 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919',
        'callback_url' => 'https://drearies-jannette-impuissant.ngrok-free.dev/callback.php',
    ],

    'database' => [
        // Environment variables will take precedence if set, otherwise fallback to Clever Cloud details
        'host'     => getenv('MYSQL_ADDON_HOST')     ?: 'biyxcp1m8vyhyuh50qeo-mysql.services.clever-cloud.com',
        'name'     => getenv('MYSQL_ADDON_DB')       ?: 'biyxcp1m8vyhyuh50qeo',
        'user'     => getenv('MYSQL_ADDON_USER')     ?: 'uwnh9iqzng3ed98c',
        'password' => getenv('MYSQL_ADDON_PASSWORD') ?: 'SWXQzThHuvr6LtzaUNk',
        'port'     => getenv('MYSQL_ADDON_PORT')     ?: '20873',
        'charset'  => 'utf8mb4',
    ],
];