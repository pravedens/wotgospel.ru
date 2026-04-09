<?php

return [

    'title' => 'Сбросить пароль',

    'heading' => 'Сбросить пароль',

    'form' => [

        'email' => [
            'label' => 'Адрес электронной почты',
        ],

        'password' => [
            'label' => 'Новый пароль',
            'validation_attribute' => 'password',
        ],

        'password_confirmation' => [
            'label' => 'Подтвердите новый пароль',
        ],

        'actions' => [

            'reset' => [
                'label' => 'Сбросить пароль',
            ],

        ],

    ],

    'notifications' => [

        'throttled' => [
            'title' => 'Слишком много попыток сброса',
            'body' => 'Пожалуйста, попробуйте еще раз через :seconds секунд.',
        ],

    ],

];
