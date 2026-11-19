<?php

return [
    'dsn' => getenv('CARESHARE_DSN') ?: 'mysql:host=127.0.0.1;port=8889;dbname=careshare;charset=utf8mb4',
    'user' => getenv('CARESHARE_DB_USER') ?: 'root',
    'password' => getenv('CARESHARE_DB_PASSWORD') ?: 'root',
];
