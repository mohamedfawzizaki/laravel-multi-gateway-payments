<?php

return [
    'current_gateway' => 'tab',
    'payment_gatways' => [
        'paymob' => [
            'base_url'  => env('PAYMOB_BASE_URL', 'https://accept.paymob.com'),
            'charge_path'      => env('PAYMOB_CHARGE_PATH', '/v1/intention/'),
            'auth_path' => env('PAYMOB_AUTH_PATH', '/api/auth/tokens/'),
            'api_key'   => env('PAYMOB_API_KEY'),
            'public_key'   => env('PAYMOB_PUBLIC_KEY'),
            'secret_key'   => env('PAYMOB_SECRET_KEY'),
            'base_currency'   => env('PAYMOB_BASE_CURRENCY', 'EGP'),

            'order_path' => env('PAYMOB_ORDER_PATH', '/api/ecommerce/orders/'),
            'payment_key_path' => env('PAYMOB_PAYMENT_KEY_PATH', '/api/acceptance/payment_keys/'),
            'iframe_path' => env('PAYMOB_IFRAME_PATH', '/api/acceptance/iframes/'),
            'unified_intention_path' => env('PAYMOB_UNIFIED_INTENTION_PATH', '/v1/intention/'),
            'unified_checkout_path' => env('PAYMOB_UNIFIED_CHECKOUT_PATH', 'unifiedcheckout'), // secret_key
            'methods' => [
                'card' => [
                    'integration_id' => env('PAYMOB_INTEGRATION_ID_CARD'),
                    'iframe_id' => env('PAYMOB_IFRAME_ID_CARD'),
                ],
                'wallet' => [
                    'integration_id' => env('PAYMOB_INTEGRATION_ID_WALLET'),
                    'iframe_id' => env('PAYMOB_IFRAME_ID_WALLET'),
                ],
                'valu' => [
                    'integration_id' => env('PAYMOB_INTEGRATION_ID_WALLET'),
                    'iframe_id' => env('PAYMOB_IFRAME_ID_WALLET'),
                ],
            ],
            'header' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'billing_data_columns' => [
                'first_name',
                'last_name',
                'email',
                'phone_number',
                'apartment',
                'floor',
                'building',
                'street',
                'city',
                'state',
                'country',
                'postal_code',
                'shipping_method',
                'national_id'
            ]
        ],
        'tab' => [
            'base_url'      => env('TAB_BASE_URL', 'https://api.tap.company'),
            'auth_path'     => env('TAB_AUTH_PATH'),
            'charge_path'   => env('TAB_CHARGE_PATH', '/v2/charges/'),
            'api_key'       => env('TAB_API_KEY'),
            'public_key'    => env('TAB_PUBLIC_KEY'),
            'secret_key'    => env('TAB_SECRET_KEY'),
            'base_currency' => env('TAB_BASE_CURRENCY', 'EGP'),
            'methods' => [
                'all' => [
                    'integration_id' => 'src_all',
                ],
                'card' => [
                    'integration_id' => 'src_card',
                ],
            ],
            'header' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ],
    ],

];