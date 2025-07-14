<?php 

return [
    'name' => env('COOKIE_NAME', 'central_access_token'), // nama cookie
    'path' => env('COOKIE_PATH', '/'), // path
    'domain' => env('COOKIE_DOMAIN', '.universitaspertamina.ac.id'), // domain lintas subdomain (kalau dev atau prod ganti .universitaspertamina.ac.id)
    'secure' => env('COOKIE_SECURE', true), // secure (gunakan true (HTTPS) di produksi)
    'httpOnly' => env('COOKIE_HTTP_ONLY', true), // httpOnly (tidak bisa dibaca JS)
    'raw' => env('COOKIE_RAW', false), // SameSite ('Strict', 'Lax' atau 'None')
    'sameSite' => env('COOKIE_SAME_SITE', 'Lax'),
];