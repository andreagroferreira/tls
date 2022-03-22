<?php
return [

    'expiration_minutes' => 20,

    'invoice_disk' => 's3',

    'pay_later' => [
        'return_url' => '/pay_later/return'
    ],

    'as_fr' => [
        'maAAG2fr' => [
            'cmi' => [
                'label' => 'CMI pay'
            ],
            'alipay' => [
                'label' => 'Alipay'
            ],
            'fawry' => [
                'label' => 'Fawry pay'
            ]
        ]
    ],
    'be' => [
        'gbEDI2be' => [
            'globaliris' => [
                'label' => 'GLOBALIRIS pay',
                'common' => [
                    'env' => 'live',
                    "activated" => true,
                    "currency" => "GBP",
                    "min_fraction_digits" => "2",
                    "txn_fee_rate" => "0",
                    "txn_fee_extra" => "0",
                    "return_url" => "/globaliris/return"
                ],
                'sandbox' => [
                    "sandbox_host" => "https://hpp.sandbox.globaliris.com/pay",
                    "sandbox_merchant_id" => env("ENVPAY_GLO_COMMON_SANDBOX_MERCHANT_ID"),
                    "sandbox_secret" => env("ENVPAY_GLO_COMMON_SANDBOX_SECRET"),
                    "sandbox_account" => ''
                ],
                'prod' => [
                    "host" => "https://hpp.globaliris.com/pay",
                    "merchant_id" => env("ENVPAY_GLO_COMMON_MERCHANT_ID"),
                    "secret" => env("ENVPAY_GLO_COMMON_SECRET"),
                    "account" => env("ENVPAY_GLO_BEgbALL2be_ACCOUNT")
                ]
            ]
        ],
        'gbLON2be' => [
            'globaliris' => [
                'label' => 'GLOBALIRIS pay',
                'common' => [
                    'env' => 'live',
                    "activated" => true,
                    "currency" => "GBP",
                    "min_fraction_digits" => "2",
                    "txn_fee_rate" => "0",
                    "txn_fee_extra" => "0",
                    "return_url" => "/globaliris/return"
                ],
                'sandbox' => [
                    "sandbox_host" => "https://hpp.sandbox.globaliris.com/pay",
                    "sandbox_merchant_id" => env("ENVPAY_GLO_COMMON_SANDBOX_MERCHANT_ID"),
                    "sandbox_secret" => 'secret',
                    //env("ENVPAY_GLO_COMMON_SANDBOX_SECRET"),
                    "sandbox_account" => ''
                    //ppl.ru@tlscontact.com
                    //qa.seller_api1.tlscontact.com
                ],
                'prod' => [
                    "host" => "https://hpp.globaliris.com/pay",
                    "merchant_id" => env("ENVPAY_GLO_COMMON_MERCHANT_ID"),
                    "secret" => env("ENVPAY_GLO_COMMON_SECRET"),
                    "account" => env("ENVPAY_GLO_BEgbALL2be_ACCOUNT")
                ]
            ]
        ],
        'gbMNC2be' => [
            'globaliris' => [
                'label' => 'GLOBALIRIS pay',
                'common' => [
                    'env' => 'live',
                    "activated" => true,
                    "currency" => "GBP",
                    "min_fraction_digits" => "2",
                    "txn_fee_rate" => "0",
                    "txn_fee_extra" => "0",
                    "return_url" => "/globaliris/return"
                ],
                'sandbox' => [
                    "sandbox_host" => "https://hpp.sandbox.globaliris.com/pay",
                    "sandbox_merchant_id" => env("ENVPAY_GLO_COMMON_SANDBOX_MERCHANT_ID"),
                    "sandbox_secret" => env("ENVPAY_GLO_COMMON_SANDBOX_SECRET"),
                    "sandbox_account" => ''
                ],
                'prod' => [
                    "host" => "https://hpp.globaliris.com/pay",
                    "merchant_id" => env("ENVPAY_GLO_COMMON_MERCHANT_ID"),
                    "secret" => env("ENVPAY_GLO_COMMON_SECRET"),
                    "account" => env("ENVPAY_GLO_BEgbALL2be_ACCOUNT")
                ]
            ]
        ],
        'maCAS2be' => [
            'cmi' => [
                'label' => 'CMI pay',
                'active' => true,
                'common' => [
                    'storetype' => '3d_pay_hosting',
                    'tranType' => 'PreAuth',
                    'okUrl' => '/cmi/return',
                    'failUrl' => 'cmi/return',
                    'hashAlgorithm' => 'ver3',
                    'shopurl' => '/checkout/',
                    'callbackUrl' => '/cmi/notify',
                ],
                'sandbox' => [
                    'host' => 'https://testpayment.cmi.co.ma/fim/est3Dgate',
                    'merchant_id' => env('ENVPAY_CMI_BEmaAll2be_SANDBOX_MERCHANT_ID'),
                    'storeKey' => env('ENVPAY_CMI_BEmaAll2be_SANDBOX_STOREKEY'),
                ],
                'prod' => [
                    'host' => 'https://payment.cmi.co.ma/fim/est3Dgate',
                    'merchant_id' => env("ENVPAY_CMI_BEmaAll2be_MERCHANT_ID"),
                    'storeKey' => env("ENVPAY_CMI_BEmaAll2be_STOREKEY")
                ]
            ]
        ],
        'zaCPT2be' => [
            'paygate' => [
                'label' => 'Paygate pay',
                'common' => [
                    'env' => 'live',
                    'activated' => true,
                    'currency' => 'ZAR',
                    'country'  => 'ZAF',
                    'return_url' => '/paygate/return',
                    'notify_url' => '/paygate/notify'
                ],
                'sandbox' => [
                    'sandbox_initiate_host' => 'https://secure.paygate.co.za/payweb3/initiate.trans',
                    'sandbox_process_host' => 'https://secure.paygate.co.za/payweb3/process.trans',
                    'sandbox_query_host' => 'https://secure.paygate.co.za/payweb3/query.trans',
                    'sandbox_encryption_key' => env('PAYGATE_ZAALL2BE_ENCRYPTION_SANDBOX_KEY'),
                    'sandbox_paygate_id' => env('PAYGATE_ZAALL2BE_SANDBOX_ID'),
                    'sandbox_seller_email' => env('PAYGATE_ZAALL2BE_SELLER_EMAIL')
                ],
                'prod' => [
                    'initiate_host' => 'https://secure.paygate.co.za/payweb3/initiate.trans',
                    'process_host' => 'https://secure.paygate.co.za/payweb3/process.trans',
                    'query_host' => 'https://secure.paygate.co.za/payweb3/query.trans',
                    'encryption_key' => env('PAYGATE_ZAALL2BE_ENCRYPTION_KEY'),
                    'paygate_id' => env('PAYGATE_ZAALL2BE_ID'),
                    'seller_email' => env('PAYGATE_ZAALL2BE_SELLER_EMAIL')
                ]
            ]
        ],
        'zaJNB2be' => [
            'paygate' => [
                'label' => 'Paygate pay',
                'common' => [
                    'env' => 'live',
                    'activated' => true,
                    'currency' => 'ZAR',
                    'country'  => 'ZAF',
                    'return_url' => '/paygate/return',
                    'notify_url' => '/paygate/notify'
                ],
                'sandbox' => [
                    'sandbox_initiate_host' => 'https://secure.paygate.co.za/payweb3/initiate.trans',
                    'sandbox_process_host' => 'https://secure.paygate.co.za/payweb3/process.trans',
                    'sandbox_query_host' => 'https://secure.paygate.co.za/payweb3/query.trans',
                    'sandbox_encryption_key' => env('PAYGATE_ZAALL2BE_ENCRYPTION_SANDBOX_KEY'),
                    'sandbox_paygate_id' => env('PAYGATE_ZAALL2BE_SANDBOX_ID'),
                    'sandbox_seller_email' => env('PAYGATE_ZAALL2BE_SELLER_EMAIL')
                ],
                'prod' => [
                    'initiate_host' => 'https://secure.paygate.co.za/payweb3/initiate.trans',
                    'process_host' => 'https://secure.paygate.co.za/payweb3/process.trans',
                    'query_host' => 'https://secure.paygate.co.za/payweb3/query.trans',
                    'encryption_key' => env('PAYGATE_ZAALL2BE_ENCRYPTION_KEY'),
                    'paygate_id' => env('PAYGATE_ZAALL2BE_ID'),
                    'seller_email' => env('PAYGATE_ZAALL2BE_SELLER_EMAIL')
                ]
            ]
        ],
        'zaDUR2be' => [
            'paygate' => [
                'label' => 'Paygate pay',
                'common' => [
                    'env' => 'live',
                    'activated' => true,
                    'currency' => 'ZAR',
                    'country'  => 'ZAF',
                    'return_url' => '/paygate/return',
                    'notify_url' => '/paygate/notify'
                ],
                'sandbox' => [
                    'sandbox_initiate_host' => 'https://secure.paygate.co.za/payweb3/initiate.trans',
                    'sandbox_process_host' => 'https://secure.paygate.co.za/payweb3/process.trans',
                    'sandbox_query_host' => 'https://secure.paygate.co.za/payweb3/query.trans',
                    'sandbox_encryption_key' => env('PAYGATE_ZAALL2BE_ENCRYPTION_SANDBOX_KEY'),
                    'sandbox_paygate_id' => env('PAYGATE_ZAALL2BE_SANDBOX_ID'),
                    'sandbox_seller_email' => env('PAYGATE_ZAALL2BE_SELLER_EMAIL')
                ],
                'prod' => [
                    'initiate_host' => 'https://secure.paygate.co.za/payweb3/initiate.trans',
                    'process_host' => 'https://secure.paygate.co.za/payweb3/process.trans',
                    'query_host' => 'https://secure.paygate.co.za/payweb3/query.trans',
                    'encryption_key' => env('PAYGATE_ZAALL2BE_ENCRYPTION_KEY'),
                    'paygate_id' => env('PAYGATE_ZAALL2BE_ID'),
                    'seller_email' => env('PAYGATE_ZAALL2BE_SELLER_EMAIL')
                ]
            ]
        ],
        'cmYAO2be' => [
//            'tingg' => [
//                'label'   => 'Tingg pay',
//                'active'  => true,
//                'common'  => [
//                    'successRedirectUrl' => '/tingg/return',
//                    'failRedirectUrl'    => '/checkout/',
//                    'pendingRedirectUrl' => '/checkout/',
//                    'paymentWebhookUrl'  => '/tingg/notify'
//                ],
//                'sandbox' => [
//                    'host'          => 'https://developer.tingg.africa/checkout/v2/express/',
//                    'accountNumber' => env("ENVPAY_TINGG_COMMON_SANDBOX_ACCOUNT_NUMBER"),
//                    'serviceCode'   => env("ENVPAY_TINGG_COMMON_SANDBOX_SERVICE_CODE"),
//                    'ivKey'         => env("ENVPAY_TINGG_COMMON_SANDBOX_IVKEY"),
//                    'secretKey'     => env("ENVPAY_TINGG_COMMON_SANDBOX_SECRET_KEY"),
//                    'accessKey'     => env("ENVPAY_TINGG_COMMON_SANDBOX_ACCESS_KEY"),
//                    'clientID'      => env("ENVPAY_TINGG_COMMON_SANDBOX_CLIENT_ID"),
//                    'clientSecret'  => env("ENVPAY_TINGG_COMMON_SANDBOX_CLIENT_SECRET")
//                ],
//                'prod'    => [
//                    'host'          => 'https://online.tingg.africa/v2/express/',
//                    'accountNumber' => env("ENVPAY_TINGG_BEcmYAO2be_ACCOUNT_NUMBER"),
//                    'serviceCode'   => env("ENVPAY_TINGG_BEcmYAO2be_SERVICE_CODE"),
//                    'ivKey'         => env("ENVPAY_TINGG_BEcmYAO2be_IVKEY"),
//                    'secretKey'     => env("ENVPAY_TINGG_BEcmYAO2be_SECRET_KEY"),
//                    'accessKey'     => env("ENVPAY_TINGG_BEcmYAO2be_ACCESS_KEY")
//                ]
//            ],
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'cmDLA2be' => [
//            'tingg' => [
//                'label'   => 'Tingg pay',
//                'active'  => true,
//                'common'  => [
//                    'successRedirectUrl' => '/tingg/return',
//                    'failRedirectUrl'    => '/checkout/',
//                    'pendingRedirectUrl' => '/checkout/',
//                    'paymentWebhookUrl'  => '/tingg/notify'
//                ],
//                'sandbox' => [
//                    'host'          => 'https://developer.tingg.africa/checkout/v2/express/',
//                    'accountNumber' => env("ENVPAY_TINGG_COMMON_SANDBOX_ACCOUNT_NUMBER"),
//                    'serviceCode'   => env("ENVPAY_TINGG_COMMON_SANDBOX_SERVICE_CODE"),
//                    'ivKey'         => env("ENVPAY_TINGG_COMMON_SANDBOX_IVKEY"),
//                    'secretKey'     => env("ENVPAY_TINGG_COMMON_SANDBOX_SECRET_KEY"),
//                    'accessKey'     => env("ENVPAY_TINGG_COMMON_SANDBOX_ACCESS_KEY"),
//                    'clientID'      => env("ENVPAY_TINGG_COMMON_SANDBOX_CLIENT_ID"),
//                    'clientSecret'  => env("ENVPAY_TINGG_COMMON_SANDBOX_CLIENT_SECRET")
//                ],
//                'prod'    => [
//                    'host'          => 'https://online.tingg.africa/v2/express/',
//                    'accountNumber' => env("ENVPAY_TINGG_BEcmDLA2be_ACCOUNT_NUMBER"),
//                    'serviceCode'   => env("ENVPAY_TINGG_BEcmDLA2be_SERVICE_CODE"),
//                    'ivKey'         => env("ENVPAY_TINGG_BEcmDLA2be_IVKEY"),
//                    'secretKey'     => env("ENVPAY_TINGG_BEcmDLA2be_SECRET_KEY"),
//                    'accessKey'     => env("ENVPAY_TINGG_BEcmDLA2be_ACCESS_KEY")
//                ]
//            ],
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'snDKR2be' => [
            'tingg' => [
                'label'   => 'Tingg pay',
                'active'  => true,
                'common'  => [
                    'successRedirectUrl' => '/tingg/return',
                    'failRedirectUrl'    => '/checkout/',
                    'pendingRedirectUrl' => '/checkout/',
                    'paymentWebhookUrl'  => '/tingg/notify'
                ],
                'sandbox' => [
                    'host'          => 'https://developer.tingg.africa/checkout/v2/express/',
                    'accountNumber' => env("ENVPAY_TINGG_COMMON_SANDBOX_ACCOUNT_NUMBER"),
                    'serviceCode'   => env("ENVPAY_TINGG_COMMON_SANDBOX_SERVICE_CODE"),
                    'ivKey'         => env("ENVPAY_TINGG_COMMON_SANDBOX_IVKEY"),
                    'secretKey'     => env("ENVPAY_TINGG_COMMON_SANDBOX_SECRET_KEY"),
                    'accessKey'     => env("ENVPAY_TINGG_COMMON_SANDBOX_ACCESS_KEY"),
                    'clientID'      => env("ENVPAY_TINGG_COMMON_SANDBOX_CLIENT_ID"),
                    'clientSecret'  => env("ENVPAY_TINGG_COMMON_SANDBOX_CLIENT_SECRET"),
                    'oauthHost'     => env('ENVPAY_TINGG_COMMON_SANDBOX_OAUTH_HOST'),
                    'queryStatusHost' => env('ENVPAY_TINGG_COMMON_SANDBOX_QUERY_STATUS_HOST')
                ],
                'prod'    => [
                    'host'          => 'https://online.tingg.africa/v2/express/',
                    'accountNumber' => env("ENVPAY_TINGG_COMMON_ACCOUNT_NUMBER"),
                    'serviceCode'   => env("ENVPAY_TINGG_COMMON_SERVICE_CODE"),
                    'ivKey'         => env("ENVPAY_TINGG_COMMON_IVKEY"),
                    'secretKey'     => env("ENVPAY_TINGG_COMMON_SECRET_KEY"),
                    'accessKey'     => env("ENVPAY_TINGG_COMMON_ACCESS_KEY"),
                    'clientID'      => env("ENVPAY_TINGG_COMMON_CLIENT_ID"),
                    'clientSecret'  => env("ENVPAY_TINGG_COMMON_CLIENT_SECRET"),
                    'oauthHost'     => env('ENVPAY_TINGG_COMMON_OAUTH_HOST'),
                    'queryStatusHost' => env('ENVPAY_TINGG_COMMON_QUERY_STATUS_HOST')
                ]
            ]
        ],
        'etADD2be' => [
//            'tingg' => [
//                'label'   => 'Tingg pay',
//                'active'  => true,
//                'common'  => [
//                    'successRedirectUrl' => '/tingg/return',
//                    'failRedirectUrl'    => '/checkout/',
//                    'pendingRedirectUrl' => '/checkout/',
//                    'paymentWebhookUrl'  => '/tingg/notify'
//                ],
//                'sandbox' => [
//                    'host'          => 'https://developer.tingg.africa/checkout/v2/express/',
//                    'accountNumber' => env("ENVPAY_TINGG_COMMON_SANDBOX_ACCOUNT_NUMBER"),
//                    'serviceCode'   => env("ENVPAY_TINGG_COMMON_SANDBOX_SERVICE_CODE"),
//                    'ivKey'         => env("ENVPAY_TINGG_COMMON_SANDBOX_IVKEY"),
//                    'secretKey'     => env("ENVPAY_TINGG_COMMON_SANDBOX_SECRET_KEY"),
//                    'accessKey'     => env("ENVPAY_TINGG_COMMON_SANDBOX_ACCESS_KEY"),
//                    'clientID'      => env("ENVPAY_TINGG_COMMON_SANDBOX_CLIENT_ID"),
//                    'clientSecret'  => env("ENVPAY_TINGG_COMMON_SANDBOX_CLIENT_SECRET")
//                ],
//                'prod'    => [
//                    'host'          => 'https://online.tingg.africa/v2/express/',
//                    'accountNumber' => env("ENVPAY_TINGG_BEetADD2be_ACCOUNT_NUMBER"),
//                    'serviceCode'   => env("ENVPAY_TINGG_BEetADD2be_SERVICE_CODE"),
//                    'ivKey'         => env("ENVPAY_TINGG_BEetADD2be_IVKEY"),
//                    'secretKey'     => env("ENVPAY_TINGG_BEetADD2be_SECRET_KEY"),
//                    'accessKey'     => env("ENVPAY_TINGG_BEetADD2be_ACCESS_KEY")
//                ]
//            ],
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'rwKGL2be' => [
//            'tingg' => [
//                'label'   => 'Tingg pay',
//                'active'  => true,
//                'common'  => [
//                    'successRedirectUrl' => '/tingg/return',
//                    'failRedirectUrl'    => '/checkout/',
//                    'pendingRedirectUrl' => '/checkout/',
//                    'paymentWebhookUrl'  => '/tingg/notify'
//                ],
//                'sandbox' => [
//                    'host'          => 'https://developer.tingg.africa/checkout/v2/express/',
//                    'accountNumber' => env("ENVPAY_TINGG_COMMON_SANDBOX_ACCOUNT_NUMBER"),
//                    'serviceCode'   => env("ENVPAY_TINGG_COMMON_SANDBOX_SERVICE_CODE"),
//                    'ivKey'         => env("ENVPAY_TINGG_COMMON_SANDBOX_IVKEY"),
//                    'secretKey'     => env("ENVPAY_TINGG_COMMON_SANDBOX_SECRET_KEY"),
//                    'accessKey'     => env("ENVPAY_TINGG_COMMON_SANDBOX_ACCESS_KEY"),
//                    'clientID'      => env("ENVPAY_TINGG_COMMON_SANDBOX_CLIENT_ID"),
//                    'clientSecret'  => env("ENVPAY_TINGG_COMMON_SANDBOX_CLIENT_SECRET")
//                ],
//                'prod'    => [
//                    'host'          => 'https://online.tingg.africa/v2/express/',
//                    'accountNumber' => env("ENVPAY_TINGG_BErwKGL2be_ACCOUNT_NUMBER"),
//                    'serviceCode'   => env("ENVPAY_TINGG_BErwKGL2be_SERVICE_CODE"),
//                    'ivKey'         => env("ENVPAY_TINGG_BErwKGL2be_IVKEY"),
//                    'secretKey'     => env("ENVPAY_TINGG_BErwKGL2be_SECRET_KEY"),
//                    'accessKey'     => env("ENVPAY_TINGG_BErwKGL2be_ACCESS_KEY")
//                ]
//            ],
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'ugKLA2be' => [
            'tingg' => [
                'label'   => 'Tingg pay',
                'active'  => true,
                'common'  => [
                    'successRedirectUrl' => '/tingg/return',
                    'failRedirectUrl'    => '/checkout/',
                    'pendingRedirectUrl' => '/checkout/',
                    'paymentWebhookUrl'  => '/tingg/notify'
                ],
                'sandbox' => [
                    'host'          => 'https://developer.tingg.africa/checkout/v2/express/',
                    'accountNumber' => env("ENVPAY_TINGG_COMMON_SANDBOX_ACCOUNT_NUMBER"),
                    'serviceCode'   => env("ENVPAY_TINGG_COMMON_SANDBOX_SERVICE_CODE"),
                    'ivKey'         => env("ENVPAY_TINGG_COMMON_SANDBOX_IVKEY"),
                    'secretKey'     => env("ENVPAY_TINGG_COMMON_SANDBOX_SECRET_KEY"),
                    'accessKey'     => env("ENVPAY_TINGG_COMMON_SANDBOX_ACCESS_KEY"),
                    'clientID'      => env("ENVPAY_TINGG_COMMON_SANDBOX_CLIENT_ID"),
                    'clientSecret'  => env("ENVPAY_TINGG_COMMON_SANDBOX_CLIENT_SECRET"),
                    'oauthHost'     => env('ENVPAY_TINGG_COMMON_SANDBOX_OAUTH_HOST'),
                    'queryStatusHost' => env('ENVPAY_TINGG_COMMON_SANDBOX_QUERY_STATUS_HOST')
                ],
                'prod'    => [
                    'host'          => 'https://online.tingg.africa/v2/express/',
                    'accountNumber' => env("ENVPAY_TINGG_COMMON_ACCOUNT_NUMBER"),
                    'serviceCode'   => env("ENVPAY_TINGG_COMMON_SERVICE_CODE"),
                    'ivKey'         => env("ENVPAY_TINGG_COMMON_IVKEY"),
                    'secretKey'     => env("ENVPAY_TINGG_COMMON_SECRET_KEY"),
                    'accessKey'     => env("ENVPAY_TINGG_COMMON_ACCESS_KEY"),
                    'clientID'      => env("ENVPAY_TINGG_COMMON_CLIENT_ID"),
                    'clientSecret'  => env("ENVPAY_TINGG_COMMON_CLIENT_SECRET"),
                    'oauthHost'     => env('ENVPAY_TINGG_COMMON_OAUTH_HOST'),
                    'queryStatusHost' => env('ENVPAY_TINGG_COMMON_QUERY_STATUS_HOST')
                ]
            ]
        ],
        'egCAI2be' => [
            'fawry' => [
                'label' => 'Fawry pay',
                'common' => [
                    'env' => 'live',
                    'activated' => true,
                    'version' => 'v2',
                    'redirect_path_v1' => '/ECommercePlugin/scripts/FawryPay.js',
                    'redirect_path_v2' => '/atfawry/plugin/assets/payments/js/fawrypay-payments.js',
                    'verify_path_v1' => '/ECommerceWeb/Fawry/payments/status',
                    'verify_path_v2' => '/ECommerceWeb/Fawry/payments/status/v2',
                    'currency' => 'EGP',
                    'css_path' => '/atfawry/plugin/assets/payments/css/fawrypay-payments.css',
                    'return_url' => '/fawry/return',
                    'notify_url' => '/fawry/notify',
                    'txn_fee_rate' => 0,
                    'txn_fee_extra' => 0
                ],
                'sandbox' => [
                    'host' => 'https://atfawry.fawrystaging.com',
                    'merchant_id' => 'ENVPAY_FAW_COMMON_SANDBOX_MERCHANT_ID',
                    'secret_key' => 'ENVPAY_FAW_COMMON_SANDBOX_SECRET_KEY',
                ],
                'prod' => [
                    'host' => 'https://www.atfawry.com',
                    'merchant_id' => 'ENVPAY_FAW_egAll2be_MERCHANT_ID',
                    'secret_key' => 'ENVPAY_FAW_egAll2be_SECURITY_KEY',
                ]
            ]
        ],
        'ruMOW2be' => [
            'paypal' => [
                'label' => 'PAYPAL pay',
                'common' => [
                    'env' => 'live',
                    "activated" => true,
                    "currency" => "RUB",
                    "txn_fee_rate" => "0",
                    "txn_fee_extra" => "0",
                    "return_url" => "/paypal/return",
                    "notify_url" => "/paypal/notify"
                ],
                'sandbox' => [
                    "sandbox_host" => "https://www.sandbox.paypal.com/cgi-bin/webscr",
                    "sandbox_account" => env("ENVPAY_PAY_COMMON_SANDBOX_ACCOUNT")
                ],
                'prod' => [
                    "host" => "https://www.paypal.com/cgi-bin/webscr",
                    "account" => env("ENVPAY_PAY_BEruMOW2be_ACCOUNT")
                ]
            ]
        ],
        'ngABV2be' => [
            'payu' => [
                'label' => 'payu pay',
                'common' => [
                    'env' => 'live',
                    'activated' => true,
                    'currency'  => 'USD',
                    'payment_method'  => 'CREDITCARD',
                    'paymentsos_host' => 'https://api.paymentsos.com/payments',
                    'return_url' => '/payu/return'
                ],
                'sandbox' => [
                    'sandbox_app_id'      => env('PAYU_NGALL2BE_SANDBOX_APP_ID'),
                    'sandbox_private_key' => env('PAYU_NGALL2BE_SANDBOX_PRIVATE_KEY'),
                    'sandbox_api_version' => '1.3.0',
                    'sandbox_payments_os_env' => 'test'
                ],
                'prod' => [
                    'app_id'      => env('PAYU_NGALL2BE_APP_ID'),
                    'private_key' => env('PAYU_NGALL2BE_PRIVATE_KEY'),
                    'api_version' => '1.3.0',
                    'payments_os_env' => 'live'
                ]
            ]
        ],
        'ngLGV2be' => [
            'payu' => [
                'label' => 'payu pay',
                'common' => [
                    'env' => 'live',
                    'activated' => true,
                    'currency'  => 'USD',
                    'payment_method'  => 'CREDITCARD',
                    'paymentsos_host' => 'https://api.paymentsos.com/payments',
                    'return_url' => '/payu/return'
                ],
                'sandbox' => [
                    'sandbox_app_id'      => env('PAYU_NGALL2BE_SANDBOX_APP_ID'),
                    'sandbox_private_key' => env('PAYU_NGALL2BE_SANDBOX_PRIVATE_KEY'),
                    'sandbox_api_version' => '1.3.0',
                    'sandbox_payments_os_env' => 'test'
                ],
                'prod' => [
                    'app_id'      => env('PAYU_NGALL2BE_APP_ID'),
                    'private_key' => env('PAYU_NGALL2BE_PRIVATE_KEY'),
                    'api_version' => '1.3.0',
                    'payments_os_env' => 'live'
                ]
            ]
        ],
        'keNBO2be' => [
            'payu' => [
                'label' => 'payu pay',
                'common' => [
                    'env' => 'live',
                    'activated' => true,
                    'currency'  => 'USD',
                    'payment_method'  => 'CREDITCARD',
                    'paymentsos_host' => 'https://api.paymentsos.com/payments',
                    'return_url' => '/payu/return'
                ],
                'sandbox' => [
                    'sandbox_app_id'      => env('PAYU_KENYA_SANDBOX_APP_ID'),
                    'sandbox_private_key' => env('PAYU_KENYA_SANDBOX_PRIVATE_KEY'),
                    'sandbox_api_version' => '1.3.0',
                    'sandbox_payments_os_env' => 'test'
                ],
                'prod' => [
                    'app_id'      => env('PAYU_KEALL2BE_APP_ID'),
                    'private_key' => env('PAYU_KEALL2BE_PRIVATE_KEY'),
                    'api_version' => '1.3.0',
                    'payments_os_env' => 'live'
                ]
            ]
        ],
        'dzALG2be' => [
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'joAMM2be' => [
            'pay_later' => [
                'label' => "Pay later",
            ]
//            'payfort' => [
//                'label' => 'payfort pay',
//                'common' => [
//                    'env' => 'live',
//                    'activated' => true,
//                    'currency' => 'LBP',
//                    'return_url' => '/payfort/return',
//                    'notify_url' => '/payfort/notify',
//                ],
//                'sandbox' => [
//                    'host'            => 'https://sbcheckout.payfort.com/FortAPI/paymentPage',
//                    'merchant_id'     => env('SANDBOX_PAYFORT_MERCHANT_ID'),
//                    'access_code'     => env('SANDBOX_PAYFORT_ACCESS_CODE'),
//                    'request_phrase'  => env('SANDBOX_PAYFORT_REQUEST_PHRASE'),
//                    'response_phrase' => env('SANDBOX_PAYFORT_RESPONSE_PHRASE')
//                ],
//                'prod' => [
//                    'host'            => 'https://checkout.payfort.com/FortAPI/paymentPage',
//                    'merchant_id'     => env('PAYFORT_MERCHANT_ID'),
//                    'access_code'     => env('PAYFORT_ACCESS_CODE'),
//                    'request_phrase'  => env('PAYFORT_REQUEST_PHRASE'),
//                    'response_phrase' => env('PAYFORT_RESPONSE_PHRASE')
//                ]
//            ]
        ],
        'iqBGW2be' => [
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'iqEBL2be' => [
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'lbBEY2be' => [
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'thBKK2be' => [
            'k-bank' => [
                'label'   => 'k-bank pay',
                'active'  => true,
                'common'  => [
                    'env'         => 'live',
                    'activated'   => true,
                    'return_url'  => '/k-bank/return',
                    'notify_url'  => '/k-bank/notify'
                ],
                'sandbox' => [
                    'sandbox_host'          => 'https://dev-kpaymentgateway-services.kasikornbank.com',
                    'sandbox_redirect_host' => 'https://dev-kpaymentgateway.kasikornbank.com/ui/v2/kpayment.min.js',
                    'sandbox_apikey'        => env('KBANK_THBKK2BE_SANDBOX_API_KEY'),
                    'sandbox_secret'        => env('KBANK_THBKK2BE_SANDBOX_SECRET'),
                    'sandbox_mid'           => env('KBANK_THBKK2BE_SANDBOX_MID')
                ],
                'prod'    => [
                    'host'          => 'https://kpaymentgateway-services.kasikornbank.com',
                    'redirect_host' => 'https://kpaymentgateway.kasikornbank.com/ui/v2/kpayment.min.js',
                    'apikey'        => env('KBANK_THBKK2BE_API_KEY'),
                    'secret'        => env('KBANK_THBKK2BE_SECRET'),
                    'mid'           => env('KBANK_THBKK2BE_MID')
                ]
            ],
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'tnTUN2be' => [
            'clictopay' => [
                'label'   => 'clictopay pay',
                'active'  => true,
                'common'  => [
                    'env'         => 'live',
                    'activated'   => true,
                    'currency'    => 'TND',
                    'language'    => 'en',
                    'return_url'  => '/clictopay/return'
                ],
                'sandbox' => [
                    'sandbox_host'      => 'https://test.clictopay.com/payment/rest',
                    'sandbox_user_name' => env('CLICTOPAY_SANDBOX_USER_NAME'),
                    'sandbox_password'  => env('CLICTOPAY_SANDBOX_PASSWORD')
                ],
                'prod'    => [
                    'host'      => 'https://ipay.clictopay.com/payment/rest',
                    'user_name' => env('CLICTOPAY_USER_NAME'),
                    'password'  => env('CLICTOPAY_PASSWORD')
                ]
            ]
        ],
    ],
    'de' => [
        'cnBJS2de' => [
            'alipay' => [
                'label'   => 'Alipay pay',
                'common'  => [
                    'env' => 'live',
                    'activated' => true,
                    'product_code' => env('ALIPAY_PRODUCT_CODE'),
                    'method' => env('ALIPAY_METHOD'),
                    'return_url'  => '/alipay/return',
                    'notify_url'  => '/alipay/notify',
                ],
                'sandbox' => [
                    'app_id' => env('ALIPAY_SANDBOX_APP_ID'),
                    'gateway' => env('ALIPAY_SANDBOX_GATEWAY'),
                    'private_key' => env('ALIPAY_SANDBOX_PRIVATE_KEY'),
                    'public_key' => env('ALIPAY_SANDBOX_PUBLIC_KEY'),
                ],
                'prod'    => [
                    'app_id' => env('ALI_DEcnBJS2de_APP_ID'),
                    'gateway' => env('ALIPAY_GATEWAY'),
                    'private_key' => env('ALI_DEcnBJS2de_PRIVATE_KEY'),
                    'public_key' => env('ALI_DEcnBJS2de_PUBLIC_KEY'),
                ]
            ]
        ],
        'tzDAR2de' => [
            'tingg' => [
                'label'   => 'Tingg pay',
                'active'  => true,
                'common'  => [
                    'successRedirectUrl' => 'tingg/return',
                    'failRedirectUrl'    => 'checkout/',
                    'pendingRedirectUrl' => 'checkout/',
                    'paymentWebhookUrl'  => 'tingg/notify'
                ],
                'sandbox' => [
                    'host'          => 'https://developer.tingg.africa/checkout/v2/express/',
                    'accountNumber' => env("ENVPAY_TINGG_COMMON_SANDBOX_ACCOUNT_NUMBER"),
                    'serviceCode'   => env("ENVPAY_TINGG_COMMON_SANDBOX_SERVICE_CODE"),
                    'ivKey'         => env("ENVPAY_TINGG_COMMON_SANDBOX_IVKEY"),
                    'secretKey'     => env("ENVPAY_TINGG_COMMON_SANDBOX_SECRET_KEY"),
                    'accessKey'     => env("ENVPAY_TINGG_COMMON_SANDBOX_ACCESS_KEY")
                ],
//                'prod'    => [
//                    'host'          => 'https://online.tingg.africa/v2/express/',
//                    'accountNumber' => env("ENVPAY_TINGG_DEtzDAR2de_ACCOUNT_NUMBER"),
//                    'serviceCode'   => env("ENVPAY_TINGG_DEtzDAR2de_SERVICE_CODE"),
//                    'ivKey'         => env("ENVPAY_TINGG_DEtzDAR2de_IVKEY"),
//                    'secretKey'     => env("ENVPAY_TINGG_DEtzDAR2de_SECRET_KEY"),
//                    'accessKey'     => env("ENVPAY_TINGG_DEtzDAR2de_ACCESS_KEY")
//                ]
            ],
        ],
        'ugKLA2de' => [
            'tingg' => [
                'label'   => 'Tingg pay',
                'active'  => true,
                'common'  => [
                    'successRedirectUrl' => 'tingg/return',
                    'failRedirectUrl'    => 'checkout/',
                    'pendingRedirectUrl' => 'checkout/',
                    'paymentWebhookUrl'  => 'tingg/notify'
                ],
                'sandbox' => [
                    'host'          => 'https://developer.tingg.africa/checkout/v2/express/',
                    'accountNumber' => env("ENVPAY_TINGG_COMMON_SANDBOX_ACCOUNT_NUMBER"),
                    'serviceCode'   => env("ENVPAY_TINGG_COMMON_SANDBOX_SERVICE_CODE"),
                    'ivKey'         => env("ENVPAY_TINGG_COMMON_SANDBOX_IVKEY"),
                    'secretKey'     => env("ENVPAY_TINGG_COMMON_SANDBOX_SECRET_KEY"),
                    'accessKey'     => env("ENVPAY_TINGG_COMMON_SANDBOX_ACCESS_KEY")
                ],
//                'prod'    => [
//                    'host'          => 'https://online.tingg.africa/v2/express/',
//                    'accountNumber' => env("ENVPAY_TINGG_DEugKLA2de_ACCOUNT_NUMBER"),
//                    'serviceCode'   => env("ENVPAY_TINGG_DEugKLA2de_SERVICE_CODE"),
//                    'ivKey'         => env("ENVPAY_TINGG_DEugKLA2de_IVKEY"),
//                    'secretKey'     => env("ENVPAY_TINGG_DEugKLA2de_SECRET_KEY"),
//                    'accessKey'     => env("ENVPAY_TINGG_DEugKLA2de_ACCESS_KEY")
//                ]
            ],
        ],
        'keNBO2de' => [
            'payu' => [
                'label' => 'payu pay',
                'common' => [
                    'env' => 'live',
                    'activated' => true,
                    'currency'  => 'USD',
                    'payment_method'  => 'CREDITCARD',
                    'paymentsos_host' => 'https://api.paymentsos.com/payments',
                    'return_url' => '/payu/return'
                ],
                'sandbox' => [
                    'sandbox_app_id'      => env('PAYU_KENYA_SANDBOX_APP_ID'),
                    'sandbox_private_key' => env('PAYU_KENYA_SANDBOX_PRIVATE_KEY'),
                    'sandbox_api_version' => '1.3.0',
                    'sandbox_payments_os_env' => 'test'
                ],
                'prod' => [
                    'app_id'      => env('PAYU_KEALL2DE_APP_ID'),
                    'private_key' => env('PAYU_KEALL2DE_PRIVATE_KEY'),
                    'api_version' => '1.3.0',
                    'payments_os_env' => 'live'
                ]
            ],
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'tnTUN2de' => [
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'lyTIP2de' => [
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
    ],
    'pl' => [
        'uaKBP2pl' => [
            'paysoft' => [
                'label' => 'Paysoft pay',
                'active' => true,
                'common' => [
                    'env' => 'live',
                    'return_url' => '/paysoft/return',
                ],
                'sandbox' => [
                    'host' => env('ENVPAY_PAYSOFT_COMMON_SANDBOX_HOST'),
                    'merchant_id' => env('ENVPAY_PAYSOFT_COMMON_SANDBOX_MERCHANT_ID'),
                    'signature_algorithm' => env('ENVPAY_PAYSOFT_COMMON_SANDBOX_SIGNATURE_ALGORITHM'),
                    'signature_secret_key' => env('ENVPAY_PAYSOFT_COMMON_SANDBOX_SIGNATURE_SECRET_KEY'),
                ],
                'prod' => [
                    'host' => env('ENVPAY_PAYSOFT_uaKBP2pl_HOST'),
                    'merchant_id' => env('ENVPAY_PAYSOFT_uaKBP2pl_MERCHANT_ID'),
                    'signature_algorithm' => env('ENVPAY_PAYSOFT_uaKBP2pl_SIGNATURE_ALGORITHM'),
                    'signature_secret_key' => env('ENVPAY_PAYSOFT_uaKBP2pl_SIGNATURE_SECRET_KEY'),
                ]
            ],
        ],
    ]

];
