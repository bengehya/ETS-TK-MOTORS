<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identité de l'application
    |--------------------------------------------------------------------------
    |
    | Ces valeurs reflètent l'identité officielle d'ETS TK MOTORS.
    | Elles ne doivent pas être inventées côté frontend.
    |
    */

    'name' => 'TK MOTORS',

    'company' => 'ETS TK MOTORS',

    'slogan' => 'Votre Moto, Notre Passion !',

    'city' => 'Kinshasa',

    'organization_slug' => 'ets-tk-motors',

    /*
    |--------------------------------------------------------------------------
    | Fonctionnalités
    |--------------------------------------------------------------------------
    |
    | V1 : la gestion des locations n'est pas exposée. Les modèles, services,
    | migrations et routes restent en place. Passer « rentals » à true réactive
    | la navigation, les pages et les alertes d'échéance, sans nouvelle
    | architecture.
    |
    */

    'features' => [
        'rentals' => (bool) env('TKMOTORS_FEATURE_RENTALS', false),
    ],

];
