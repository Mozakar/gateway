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
        'privateKey'    => '<RSAKeyValue><Modulus>tCZiqDS5BVQQZDBUYbyeoP4rENN4mX5FZJjjMNfGbyzfzH45RY2/YsMaY0yI1jMCOpukvkUyl153tcn0LXhMCDdsEhhZPoKbPUGMniKtFGjs18rv/b5FFUUW1utgwoL8+WJqjOqhQGgvbja63X9+WMFP0nM3d8yudn9C/X55KyM=</Modulus><Exponent>AQAB</Exponent><P>5HXvmU4IfqUG2jFLSqi/BMEQ3x1NsUDvx3zN5O1p9yLLspJ4sqAt4RUkxzcGodYgBSdXsR9IGcPwjQfbx3a7nQ==</P><Q>yd2hDCF/5Zqtz9DXjY1NRYGvBjTS4AQn83ERR46Y5eBSnLjpVjv6gPfARuhsUP44nikrQPvwPnjxQcOhJaOlvw==</Q><DP>ztuqUplBP8qU5cN0dOlN7DQT3rFdw30Unv/2Pa5qIAc1gT72YmZ+pCrM3kSIkMicvY3d7NZyJkIv8MKI0ZZEUQ==</DP><DQ>QFLJ5YarLWubZPQEK4vSCornTY/5ff51CIKH4ghTOjS/vkbBu4PDL+NCNpYLJcfMHMG7kap2BEIfhjgjGk5KGw==</DQ><InverseQ>WE6TqpcexQJwt9Mnp1FbeLtarBcFkXVdBauouFKHcbHCfQjA3IjUrGTxgSO74O/4QSKqaF2gnlL6GI7gKuGbzQ==</InverseQ><D>czYtWDfHsFGv3fNOs+cGaB3E+xDTiw7HYGuquJz2qjk/s69x/zqFEKuIH8Ndq+eZYFQUCx+EGGxxENDkmYPa0z8wbfFI6JEHpxaLmQfpkkbSL1BJIp9Z5BNM2gy6jJqgbWwQPcN/4jpiMefHZWAqhMKqenUu1KIq1ZX6Bz5xKYk=</D></RSAKeyValue>',
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
    // Paypal gateway
    //--------------------------------
    'paypal'   => [
        // Default product name that appear on paypal payment items
        'default_product_name' => 'My Product',
        'default_shipment_price' => 0,
        // set your paypal credential
        'client_id' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        'secret'    => 'xxxxxxxxxx_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        'settings'  => [
            'mode'                   => 'sandbox', //'sandbox' or 'live'
            'http.ConnectionTimeOut' => 30,
            'log.LogEnabled'         => true,
            'log.FileName'           => storage_path() . '/logs/paypal.log',
            /**
             * Available option 'FINE', 'INFO', 'WARN' or 'ERROR'
             *
             * Logging is most verbose in the 'FINE' level and decreases as you
             * proceed towards ERROR
             */
            'call_back_url'          => '/gateway/callback/paypal',
            'log.LogLevel'           => 'FINE'

        ]
    ],
    //-------------------------------
    // Tables names
    //--------------------------------
    'table'    => 'gateway_transactions',
];
