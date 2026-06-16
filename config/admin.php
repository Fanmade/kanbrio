<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Admin User
    |--------------------------------------------------------------------------
    |
    | When both an email and password are provided, the database seeder will
    | create an administrator with every permission granted. This is handy for
    | bootstrapping a fresh install. The name is optional and falls back to
    | "Admin" when left empty, as it can be changed in the app afterwards.
    |
    */

    'name' => env('ADMIN_NAME', 'Admin'),

    'email' => env('ADMIN_EMAIL'),

    'password' => env('ADMIN_PASSWORD'),

];
