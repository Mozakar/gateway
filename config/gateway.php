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
        'url'   => '',
        'vpg_url'  => '',
        'merchant'      => '',
        'transactionKey'=> '',
        'terminalId'    => 000000000,
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
