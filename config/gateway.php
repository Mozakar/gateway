<?php

return [

    //-------------------------------
    // Timezone for insert dates in database
    // If you want Gateway not set timezone, just leave it empty
    //--------------------------------
    'timezone' => 'Asia/Tehran',

    //--------------------------------
    // Zarinpal gateway
    //--------------------------------
    'zarinpal' => [
        'merchant-id'  => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        'type'         => 'zarin-gate',             // Types: [zarin-gate || normal]
        'callback-url' => '/',
        'server'       => 'germany',                // Servers: [germany || iran || test]
        'email'        => 'email@gmail.com',
        'mobile'       => '09xxxxxxxxx',
        'description'  => 'description',
    ],

    //--------------------------------
    // Mellat gateway
    //--------------------------------
    'mellat' => [
        'username'     => '',
        'password'     => '',
        'terminalId'   => 0000000,
        'callback-url' => '/'
    ],

    //--------------------------------
    // Saman gateway
    //--------------------------------
    'saman' => [
        'merchant'     => '',
        'password'     => '',
        'callback-url'   => '/',
    ],

    //--------------------------------
    // PayIr gateway
    //--------------------------------
    'payir'    => [
        'api'          => 'xxxxxxxxxxxxxxxxxxxx',
        'callback-url' => '/'
    ],

    //--------------------------------
    // IranKish gateway
    //--------------------------------
    'irankish' => [
        'merchantId' => 'xxxxxxxxxxxxxxxxxxxx',
        'sha1key' => 'xxxxxxxxxxxxxxxxxxxx',
        'callback-url' => '/'
    ],

    //--------------------------------
    // Sadad gateway
    //--------------------------------
    'sadad' => [
        'merchant'      => '',
        'transactionKey'=> '',
        'terminalId'    => 000000000,
        'callback-url'  => '/'
    ],

    //--------------------------------
    // Nikan gateway
    //--------------------------------
    'nikan' => [
        'token'          => 'xxxxxxxxxxxxxxxxxxxx',
        'callback-url' => '/'
    ],

    //--------------------------------
    // PayPing gateway
    //--------------------------------
    'payping' => [
        'token'          => 'xxxxxxxxxxxxxxxxxxxx',
        'callback-url' => '/'
    ],

    //--------------------------------
    // Parsian gateway
    //--------------------------------
    'parsian' => [
        'pin'          => 'xxxxxxxxxxxxxxxxxxxx',
        'callback-url' => '/'
    ],
    //--------------------------------
    // Pasargad gateway
    //--------------------------------
    'pasargad' => [
        'terminalId'    => 000000,
        'merchantId'    => 000000,
        'username'      => '',
        'password'      => '',
        'callback-url' => '/gateway/callback/pasargad'
    ],

    //--------------------------------
    // Asan Pardakht gateway
    //--------------------------------
    'asanpardakht' => [
        'merchantId'     => '',
        'merchantConfigId'     => '',
        'username' => '',
        'password' => '',
        'key' => '',
        'iv' => '',
        'callback-url'   => '/',
    ],

    //--------------------------------
    // Vandar gateway
    //--------------------------------
    'vandar'    => [
        'api_key'            => '',
        'username'           => '',
        'password'           => '',
        'company'            => '',
        'callback-url'       => '/',
    ],

    //--------------------------------
    // Apsan gateway
    //--------------------------------
    'apsan' => [
        'terminalId'     => '',
        'username' => '',
        'password' => '',
        'callback-url'   => '/',
    ],
    //--------------------------------
    // Digipay gateway
    //--------------------------------
    'digipay' => [
        'username'     => '',
        'password' => '',
        'clientId' => '',
        'clientSecret' => '',
        'sandbox' => false,
        'callback-url'   => '/',
    ],
    //--------------------------------
    // Sadad BNPL gateway
    //--------------------------------
    'sadad_bnpl' => [
        'url'   => 'https://op-cpg-wrapper.bmicc.ir:44377/WEBAPIWrapper/ConsumerExternalWebapiWrapper',
        'username' => '',
        'password' => '',
        'merchant'      => '',
        'encryptionKey'=> '',
        'encryptionVector'=> '',
        'callback-url'  => '/'
    ],

    //--------------------------------
    // Setare Aval gateway
    //--------------------------------
    'setare_aval' => [
        'username'      => '',
        'password'      => '',
        'merchant'      => '',
        'callback-url'  => '/'
    ],

    //--------------------------------
    // Zibal gateway
    //--------------------------------
    'zibal' => [
        'merchant'      => '',
        'callback-url'  => '/'
    ],

    //--------------------------------
    // Mili Gold gateway
    //--------------------------------
    'mili_gold' => [
        'base_url'      => 'https://demo.mymili.ir/',
        'merchant'      => '',
        'terminal'      => '',
        'callback-url'  => '/'
    ],
    //-------------------------------
    // Tables names
    //--------------------------------
    'table'    => 'gateway_transactions',
];
