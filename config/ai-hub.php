<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default AI driver used by the library.
    |
    | Supported: "openai"
    |
    */

    'default' => env('AI_HUB_DRIVER', env('AI_DRIVER', 'openai')),

];
