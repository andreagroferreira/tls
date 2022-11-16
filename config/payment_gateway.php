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
        'allAll2all'=> [
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'gbAll2be' => [
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
                    "sandbox_host" => "https://pay.sandbox.realexpayments.com/pay",
                    "sandbox_merchant_id" => env("ENVPAY_GLO_COMMON_SANDBOX_MERCHANT_ID"),
                    "sandbox_secret" => env("ENVPAY_GLO_COMMON_SANDBOX_SECRET"),
                    "sandbox_account" => ''
                ],
                'production' => [
                    "host" => "https://pay.realexpayments.com/pay",
                    "merchant_id" => env("ENVPAY_GLO_COMMON_MERCHANT_ID"),
                    "secret" => env("ENVPAY_GLO_COMMON_SECRET"),
                    "account" => env("ENVPAY_GLO_BEgbALL2be_ACCOUNT")
                ]
            ]
        ],
        'maAll2be' => [
            'cmi' => [
                'label' => 'CMI pay',
                'active' => true,
                'common' => [
                    'storetype' => '3d_pay_hosting',
                    'tranType' => 'PreAuth',
                    'okUrl' => '/cmi/return',
                    'failUrl' => '/cmi/return',
                    'hashAlgorithm' => 'ver3',
                    'shopurl' => '/checkout/',
                    'callbackUrl' => '/cmi/notify',
                ],
                'sandbox' => [
                    'host' => 'https://testpayment.cmi.co.ma/fim/est3Dgate',
                    'merchant_id' => env('ENVPAY_CMI_BEmaAll2be_SANDBOX_MERCHANT_ID'),
                    'storeKey' => env('ENVPAY_CMI_BEmaAll2be_SANDBOX_STOREKEY'),
                ],
                'production' => [
                    'host' => 'https://payment.cmi.co.ma/fim/est3Dgate',
                    'merchant_id' => env("ENVPAY_CMI_BEmaAll2be_MERCHANT_ID"),
                    'storeKey' => env("ENVPAY_CMI_BEmaAll2be_STOREKEY")
                ]
            ],
            'binga' => [
                'label' => 'BINGA pay',
                'common' => [
                    'env' => 'live',
                    "currency" => "MAD",
                    'expiration' => '+2 hours',
                    "cash_url" => "/binga/cash",
                    "return_url" => "/binga/return",
                    "notify_url" => "/binga/notify"
                ],
                'sandbox' => [
                    "host" => "http://preprod.binga.ma:8080/v1.2/api/orders",
                    "merchant_login" => env("ENVPAY_BINGA_SANDBOX_MERCHANT_LOGIN"),
                    "merchant_password" => env("ENVPAY_BINGA_SANDBOX_MERCHANT_PASSWORD"),
                    "store_id" => env("ENVPAY_BINGA_SANDBOX_STORE_ID"),
                    "store_private_key" => env("ENVPAY_BINGA_SANDBOX_STORE_PRIVATE_KEY"),
                ],
                'production' => [
                    "host" => "https://api.binga.ma/bingaApi/api/orders",
                    "merchant_login" => env("ENVPAY_BINGA_MERCHANT_LOGIN"),
                    "merchant_password" => env("ENVPAY_BINGA_MERCHANT_PASSWORD"),
                    "store_id" => env("ENVPAY_BINGA_STORE_ID"),
                    "store_private_key" => env("ENVPAY_BINGA_STORE_PRIVATE_KEY"),
                ]
            ],
        ],
        'zaAll2be' => [
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
                'production' => [
                    'initiate_host' => 'https://secure.paygate.co.za/payweb3/initiate.trans',
                    'process_host' => 'https://secure.paygate.co.za/payweb3/process.trans',
                    'query_host' => 'https://secure.paygate.co.za/payweb3/query.trans',
                    'encryption_key' => env('PAYGATE_ZAALL2BE_ENCRYPTION_KEY'),
                    'paygate_id' => env('PAYGATE_ZAALL2BE_ID'),
                    'seller_email' => env('PAYGATE_ZAALL2BE_SELLER_EMAIL')
                ]
            ]
        ],
        'cmAll2be' => [
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
                    'clientSecret'  => env("ENVPAY_TINGG_COMMON_SANDBOX_CLIENT_SECRET")
                ],
                /*'production'    => [
                    'host'          => 'https://online.tingg.africa/v2/express/',
                    'accountNumber' => env("ENVPAY_TINGG_BEcmYAO2be_ACCOUNT_NUMBER"),
                    'serviceCode'   => env("ENVPAY_TINGG_BEcmYAO2be_SERVICE_CODE"),
                    'ivKey'         => env("ENVPAY_TINGG_BEcmYAO2be_IVKEY"),
                    'secretKey'     => env("ENVPAY_TINGG_BEcmYAO2be_SECRET_KEY"),
                    'accessKey'     => env("ENVPAY_TINGG_BEcmYAO2be_ACCESS_KEY")
                ]*/
            ],
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'snAll2be' => [
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
                'production'    => [
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
        'etAll2be' => [
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
                    'clientSecret'  => env("ENVPAY_TINGG_COMMON_SANDBOX_CLIENT_SECRET")
                ],
                /*'production'    => [
                    'host'          => 'https://online.tingg.africa/v2/express/',
                    'accountNumber' => env("ENVPAY_TINGG_BEetADD2be_ACCOUNT_NUMBER"),
                    'serviceCode'   => env("ENVPAY_TINGG_BEetADD2be_SERVICE_CODE"),
                    'ivKey'         => env("ENVPAY_TINGG_BEetADD2be_IVKEY"),
                    'secretKey'     => env("ENVPAY_TINGG_BEetADD2be_SECRET_KEY"),
                    'accessKey'     => env("ENVPAY_TINGG_BEetADD2be_ACCESS_KEY")
                ]*/
            ],
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'ugAll2be' => [
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
                'production'    => [
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
        'egAll2be' => [
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
                'production' => [
                    'host' => 'https://www.atfawry.com',
                    'merchant_id' => 'ENVPAY_FAW_egAll2be_MERCHANT_ID',
                    'secret_key' => 'ENVPAY_FAW_egAll2be_SECURITY_KEY',
                ]
            ]
        ],
        'ruAll2be' => [
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
                'production' => [
                    "host" => "https://www.paypal.com/cgi-bin/webscr",
                    "account" => env("ENVPAY_PAY_BEruMOW2be_ACCOUNT")
                ]
            ]
        ],
        'ngAll2be' => [
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
                'production' => [
                    'app_id'      => env('PAYU_NGALL2BE_APP_ID'),
                    'private_key' => env('PAYU_NGALL2BE_PRIVATE_KEY'),
                    'api_version' => '1.3.0',
                    'payments_os_env' => 'live'
                ]
            ]
        ],
        'keAll2be' => [
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
                'production' => [
                    'app_id'      => env('PAYU_KEALL2BE_APP_ID'),
                    'private_key' => env('PAYU_KEALL2BE_PRIVATE_KEY'),
                    'api_version' => '1.3.0',
                    'payments_os_env' => 'live'
                ]
            ]
        ],
        'iqAll2be' => [
            'switch' => [
                'label' => 'Switch pay',
                'active' => true,
                'common' => [
                    'env' => 'live',
                    'currency' => 'USD',
                    'return_url' => '/switch/return',
                ],
                'sandbox' => [
                    'host' => env('ENVPAY_SWITCH_COMMON_SANDBOX_HOST'),
                    'entity_id' => env('ENVPAY_SWITCH_COMMON_SANDBOX_ENTITY_ID'),
                    'access_token' => env('ENVPAY_SWITCH_COMMON_SANDBOX_ACCESS_TOKEN')
                ],
                /*'production' => [
                    'host' => env('ENVPAY_SWITCH_iqAll2be_HOST'),
                    'entity_id' => env('ENVPAY_SWITCH_iqAll2be_ENTITY_ID'),
                    'access_token' => env('ENVPAY_SWITCH_iqAll2be_ACCESS_TOKEN')
                ]*/
            ],
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'thAll2be' => [
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
                'production'    => [
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
        'tnAll2be' => [
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
                'production'    => [
                    'host'      => 'https://ipay.clictopay.com/payment/rest',
                    'user_name' => env('CLICTOPAY_USER_NAME'),
                    'password'  => env('CLICTOPAY_PASSWORD')
                ]
            ]
        ],
    ],
    'ch' => [
        'allAll2all'=> [
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'gbAll2ch' => [
            'globaliris' => [
                'label' => 'GLOBALIRIS pay',
                'common' => [
                    'env' => 'live',
                    'activated' => true,
                    'currency' => 'GBP',
                    'min_fraction_digits' => '2',
                    'txn_fee_rate' => '0',
                    'txn_fee_extra' => '0',
                    'return_url' => '/globaliris/return',
                ],
                'sandbox' => [
                    'sandbox_host' => 'https://pay.sandbox.realexpayments.com/pay',
                    'sandbox_merchant_id' => env("ENVPAY_GLO_COMMON_SANDBOX_MERCHANT_ID"),
                    'sandbox_secret' => env("ENVPAY_GLO_COMMON_SANDBOX_SECRET"),
                    'sandbox_account' => '',
                ],
                /*'production' => [
                    'account' => env('ENVPAY_GLO_BEgbALL2be_ACCOUNT'),
                    'secret' => env('ENVPAY_GLO_COMMON_SECRET'),
                    'merchant_id' => env('ENVPAY_GLO_COMMON_MERCHANT_ID'),
                      'host' => 'https://pay.realexpayments.com/pay',
                ],*/
            ],
        ],
    ],
    'de' => [
        'allAll2all'=> [
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'cnAll2de' => [
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
                'production'    => [
                    'app_id' => env('ALI_DEcnBJS2de_APP_ID'),
                    'gateway' => env('ALIPAY_GATEWAY'),
                    'private_key' => env('ALI_DEcnBJS2de_PRIVATE_KEY'),
                    'public_key' => env('ALI_DEcnBJS2de_PUBLIC_KEY'),
                ]
            ]
        ],
        'keAll2de' => [
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
                'production' => [
                    'app_id'      => env('PAYU_KEALL2DE_APP_ID'),
                    'private_key' => env('PAYU_KEALL2DE_PRIVATE_KEY'),
                    'api_version' => '1.3.0',
                    'payments_os_env' => 'live'
                ]
            ],
        ],
        'gbAll2de' => [
            'globaliris' => [
                'label' => 'GLOBALIRIS pay',
                'common' => [
                    'env' => 'live',
                    'activated' => true,
                    'currency' => 'GBP',
                    'min_fraction_digits' => '2',
                    'txn_fee_rate' => '0',
                    'txn_fee_extra' => '0',
                    'return_url' => '/globaliris/return',
                ],
                'sandbox' => [
                    'sandbox_host' => 'https://pay.sandbox.realexpayments.com/pay',
                    'sandbox_merchant_id' => env("ENVPAY_GLO_COMMON_SANDBOX_MERCHANT_ID"),
                    'sandbox_secret' => env("ENVPAY_GLO_COMMON_SANDBOX_SECRET"),
                    'sandbox_account' => '',
                ],
                'production' => [
                    'account' => env('ENVPAY_GLO_DEgbALL2de_ACCOUNT'),
                    'secret' => env('ENVPAY_GLO_COMMON_SECRET'),
                    'merchant_id' => env('ENVPAY_GLO_COMMON_MERCHANT_ID'),
                    'host' => 'https://pay.realexpayments.com/pay',
                ],
            ],
        ],
        'ieAll2de' => [
            'globaliris' => [
                'label' => 'GLOBALIRIS pay',
                'common' => [
                    'env' => 'live',
                    'activated' => true,
                    'currency' => 'GBP',
                    'min_fraction_digits' => '2',
                    'txn_fee_rate' => '0',
                    'txn_fee_extra' => '0',
                    'return_url' => '/globaliris/return',
                ],
                'sandbox' => [
                    'sandbox_host' => 'https://pay.sandbox.realexpayments.com/pay',
                    'sandbox_merchant_id' => env("ENVPAY_GLO_COMMON_SANDBOX_MERCHANT_ID"),
                    'sandbox_secret' => env("ENVPAY_GLO_COMMON_SANDBOX_SECRET"),
                    'sandbox_account' => '',
                ],
                'production' => [
                    'account' => env('ENVPAY_GLO_DEieALL2de_ACCOUNT'),
                    'secret' => env('ENVPAY_GLO_COMMON_SECRET'),
                    'merchant_id' => env('ENVPAY_GLO_COMMON_MERCHANT_ID'),
                    'host' => 'https://pay.realexpayments.com/pay',
                ],
            ],
        ],
        'egCAI2de' => [
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
                    'merchant_id' => 'ENVPAY_FAW_DEegAll2de_SANDBOX_MERCHANT_ID',
                    'secret_key' => 'ENVPAY_FAW_DEegAll2de_SANDBOX_SECRET_KEY',
                ],
                'production' => [
                    'host' => 'https://www.atfawry.com',
                    'merchant_id' => 'ENVPAY_FAW_DEegCAI2de_MERCHANT_ID',
                    'secret_key' => 'ENVPAY_FAW_DEegCAI2de_SECRET_KEY',
                ]
            ],
        ],
        'egALY2de' => [
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
                    'merchant_id' => 'ENVPAY_FAW_DEegAll2de_SANDBOX_MERCHANT_ID',
                    'secret_key' => 'ENVPAY_FAW_DEegAll2de_SANDBOX_SECRET_KEY',
                ],
                'production' => [
                    'host' => 'https://www.atfawry.com',
                    'merchant_id' => 'ENVPAY_FAW_DEegALY2de_MERCHANT_ID',
                    'secret_key' => 'ENVPAY_FAW_DEegALY2de_SECRET_KEY',
                ]
            ]
        ],
        'egHRG2de' => [
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
                    'merchant_id' => 'ENVPAY_FAW_DEegAll2de_SANDBOX_MERCHANT_ID',
                    'secret_key' => 'ENVPAY_FAW_DEegAll2de_SANDBOX_SECRET_KEY',
                ],
                'production' => [
                    'host' => 'https://www.atfawry.com',
                    'merchant_id' => 'ENVPAY_FAW_DEegHRG2de_MERCHANT_ID',
                    'secret_key' => 'ENVPAY_FAW_DEegHRG2de_SECRET_KEY',
                ]
            ]
        ],
        'maAll2de'=> [
            'binga' => [
                'label' => 'BINGA pay',
                'common' => [
                    'env' => 'live',
                    "currency" => "MAD",
                    'expiration' => '+2 hours',
                    "cash_url" => "/binga/cash",
                    "return_url" => "/binga/return",
                    "notify_url" => "/binga/notify"
                ],
                'sandbox' => [
                    "host" => "http://preprod.binga.ma:8080/v1.2/api/orders",
                    "merchant_login" => env("ENVPAY_BINGA_SANDBOX_MERCHANT_LOGIN"),
                    "merchant_password" => env("ENVPAY_BINGA_SANDBOX_MERCHANT_PASSWORD"),
                    "store_id" => env("ENVPAY_BINGA_SANDBOX_STORE_ID"),
                    "store_private_key" => env("ENVPAY_BINGA_SANDBOX_STORE_PRIVATE_KEY"),
                ],
                'production' => [
                    "host" => "https://api.binga.ma/bingaApi/api/orders",
                    "merchant_login" => env("ENVPAY_BINGA_MERCHANT_LOGIN"),
                    "merchant_password" => env("ENVPAY_BINGA_MERCHANT_PASSWORD"),
                    "store_id" => env("ENVPAY_BINGA_STORE_ID"),
                    "store_private_key" => env("ENVPAY_BINGA_STORE_PRIVATE_KEY"),
                ]
            ],
            'cmi' => [
                'label' => 'CMI pay',
                'active' => true,
                'common' => [
                    'storetype' => '3d_pay_hosting',
                    'tranType' => 'PreAuth',
                    'okUrl' => '/cmi/return',
                    'failUrl' => '/cmi/return',
                    'hashAlgorithm' => 'ver3',
                    'shopurl' => '/checkout/',
                    'callbackUrl' => '/cmi/notify',
                ],
                'sandbox' => [
                    'host' => 'https://testpayment.cmi.co.ma/fim/est3Dgate',
                    'merchant_id' => env('ENVPAY_CMI_DEmaAll2de_SANDBOX_MERCHANT_ID'),
                    'storeKey' => env('ENVPAY_CMI_DEmaAll2de_SANDBOX_STOREKEY'),
                ],
                'production' => [
                    'host' => 'https://payment.cmi.co.ma/fim/est3Dgate',
                    'merchant_id' => env('ENVPAY_CMI_DEmaAll2de_MERCHANT_ID'),
                    'storeKey' => env('ENVPAY_CMI_DEmaAll2de_STOREKEY')
                ]
            ]
        ],
    ],
    'pl' => [
        'allAll2all'=> [
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'uaAll2pl' => [
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
                'production' => [
                    'host' => env('ENVPAY_PAYSOFT_uaKBP2pl_HOST'),
                    'merchant_id' => env('ENVPAY_PAYSOFT_uaKBP2pl_MERCHANT_ID'),
                    'signature_algorithm' => env('ENVPAY_PAYSOFT_uaKBP2pl_SIGNATURE_ALGORITHM'),
                    'signature_secret_key' => env('ENVPAY_PAYSOFT_uaKBP2pl_SIGNATURE_SECRET_KEY'),
                ]
            ],
        ],
    ],
    'fr' => [
        'allAll2all'=> [
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'uzAll2fr'=> [
            'globaliris' => [
                'label' => 'GLOBALIRIS pay',
                'common' => [
                    'env' => 'live',
                    "activated" => true,
                    "currency" => "EUR",
                    "min_fraction_digits" => "2",
                    "txn_fee_rate" => "0",
                    "txn_fee_extra" => "0",
                    "return_url" => "/globaliris/return"
                ],
                'sandbox' => [
                    "sandbox_host" => "https://pay.sandbox.realexpayments.com/pay",
                    "sandbox_merchant_id" => env("ENVPAY_GLO_COMMON_SANDBOX_MERCHANT_ID"),
                    "sandbox_secret" => env("ENVPAY_GLO_COMMON_SANDBOX_SECRET"),
                    "sandbox_account" => ''
                ],
                'production' => [
                    "host" => "https://pay.realexpayments.com/pay",
                    "merchant_id" => env("ENVPAY_GLO_COMMON_MERCHANT_ID"),
                    "secret" => env("ENVPAY_GLO_COMMON_SECRET"),
                    "account" => env("ENVPAY_GLO_FRuzTAS2fr_ACCOUNT")
                ]
            ]
        ],
        'egAll2fr'=> [
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
                    'merchant_id' => 'ENVPAY_FAW_FRegAll2fr_SANDBOX_MERCHANT_ID',
                    'secret_key' => 'ENVPAY_FAW_FRegAll2fr_SANDBOX_SECRET_KEY',
                ],
                'production' => [
                    'host' => 'https://www.atfawry.com',
                    'merchant_id' => 'ENVPAY_FAW_FRegAll2fr_MERCHANT_ID',
                    'secret_key' => 'ENVPAY_FAW_FRegAll2fr_SECURITY_KEY',
                ]
            ],
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'egCAI2fr'=> [
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
                    'merchant_id' => 'ENVPAY_FAW_FRegAll2fr_SANDBOX_MERCHANT_ID',
                    'secret_key' => 'ENVPAY_FAW_FRegAll2fr_SANDBOX_SECRET_KEY',
                ],
                'production' => [
                    'host' => 'https://www.atfawry.com',
                    'merchant_id' => 'ENVPAY_FAW_egCAI2fr_MERCHANT_ID',
                    'secret_key' => 'ENVPAY_FAW_egCAI2fr_SECURITY_KEY',
                ]
            ],
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'egALY2fr'=> [
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
                    'merchant_id' => 'ENVPAY_FAW_FRegAll2fr_SANDBOX_MERCHANT_ID',
                    'secret_key' => 'ENVPAY_FAW_FRegAll2fr_SANDBOX_SECRET_KEY',
                ],
                'production' => [
                    'host' => 'https://www.atfawry.com',
                    'merchant_id' => 'ENVPAY_FAW_egALY2fr_MERCHANT_ID',
                    'secret_key' => 'ENVPAY_FAW_egALY2fr_SECURITY_KEY',
                ]
            ],
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'dzAll2fr'=> [
            'bnp' => [
                'label' => 'BNP Paribas pay',
                'active' => true,
                'common' => [
                    'env' => 'live',
                    'return_url' => '/bnp/return',
                    "currency" => "DZD",
                    "txn_fee_rate" => "0",
                    "txn_fee_extra" => "0",
                    "min_fraction_digits" => "2",
                    "language" => "FR",
                ],
                'sandbox' => [
                    'host' => env('ENVPAY_BNP_PARIBAS_COMMON_SANDBOX_HOST'),
                    'user_name' => env('ENVPAY_BNP_PARIBAS_COMMON_SANDBOX_USER_NAME'),
                    'password' => env('ENVPAY_BNP_PARIBAS_COMMON_SANDBOX_PASSWORD'),
                    'terminal_id' => env('ENVPAY_BNP_PARIBAS_COMMON_SANDBOX_TERMINAL_ID'),
                ]
            ],
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'gbAll2fr'=> [
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
                    "sandbox_host" => "https://pay.sandbox.realexpayments.com/pay",
                    "sandbox_merchant_id" => env("ENVPAY_GLO_COMMON_SANDBOX_MERCHANT_ID"),
                    "sandbox_secret" => env("ENVPAY_GLO_COMMON_SANDBOX_SECRET"),
                    "sandbox_account" => ''
                ],
                'production' => [
                    "host" => "https://pay.realexpayments.com/pay",
                    "merchant_id" => env("ENVPAY_GLO_COMMON_MERCHANT_ID"),
                    "secret" => env("ENVPAY_GLO_COMMON_SECRET"),
                    "account" => env("ENVPAY_GLO_FRgbALL2fr_ACCOUNT")
                ]
            ]
        ],
        'maAll2fr'=> [
            'binga' => [
                'label' => 'BINGA pay',
                'common' => [
                    'env' => 'live',
                    "currency" => "MAD",
                    'expiration' => '+2 hours',
                    "cash_url" => "/binga/cash",
                    "return_url" => "/binga/return",
                    "notify_url" => "/binga/notify"
                ],
                'sandbox' => [
                    "host" => "http://preprod.binga.ma:8080/v1.2/api/orders",
                    "merchant_login" => env("ENVPAY_BINGA_SANDBOX_MERCHANT_LOGIN"),
                    "merchant_password" => env("ENVPAY_BINGA_SANDBOX_MERCHANT_PASSWORD"),
                    "store_id" => env("ENVPAY_BINGA_SANDBOX_STORE_ID"),
                    "store_private_key" => env("ENVPAY_BINGA_SANDBOX_STORE_PRIVATE_KEY"),
                ],
                'production' => [
                    "host" => "https://api.binga.ma/bingaApi/api/orders",
                    "merchant_login" => env("ENVPAY_BINGA_MERCHANT_LOGIN"),
                    "merchant_password" => env("ENVPAY_BINGA_MERCHANT_PASSWORD"),
                    "store_id" => env("ENVPAY_BINGA_STORE_ID"),
                    "store_private_key" => env("ENVPAY_BINGA_STORE_PRIVATE_KEY"),
                ]
            ],
            'cmi' => [
                'label' => 'CMI pay',
                'active' => true,
                'common' => [
                    'storetype' => '3d_pay_hosting',
                    'tranType' => 'PreAuth',
                    'okUrl' => '/cmi/return',
                    'failUrl' => '/cmi/return',
                    'hashAlgorithm' => 'ver3',
                    'shopurl' => '/checkout/',
                    'callbackUrl' => '/cmi/notify',
                ],
                'sandbox' => [
                    'host' => 'https://testpayment.cmi.co.ma/fim/est3Dgate',
                    'merchant_id' => env('ENVPAY_CMI_FRmaAll2fr_SANDBOX_MERCHANT_ID'),
                    'storeKey' => env('ENVPAY_CMI_FRmaAll2fr_SANDBOX_STOREKEY'),
                ],
                'production' => [
                    'host' => 'https://payment.cmi.co.ma/fim/est3Dgate',
                    'merchant_id' => env("ENVPAY_CMI_FRmaAll2fr_MERCHANT_ID"),
                    'storeKey' => env("ENVPAY_CMI_FRmaAll2fr_STOREKEY")
                ]
            ]
        ],
        'tnAll2fr'=> [
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
                    'sandbox_user_name' => env('CLICTOPAY_FRtnAll2fr_SANDBOX_USER_NAME'),
                    'sandbox_password'  => env('CLICTOPAY_FRtnAll2fr_SANDBOX_PASSWORD')
                ],
                'production'    => [
                    'host'      => 'https://ipay.clictopay.com/payment/rest',
                    'user_name' => env('CLICTOPAY_FRtnAll2fr_USER_NAME'),
                    'password'  => env('CLICTOPAY_FRtnAll2fr_PASSWORD')
                ]
            ]
        ],
    ],
    'it' =>[
        'allAll2all'=> [
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
    ],
    'hmpo_uk' => [
        'allAll2all'=> [
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
                    "sandbox_host" => "https://pay.sandbox.realexpayments.com/pay",
                    "sandbox_merchant_id" => env("ENVPAY_GLO_COMMON_SANDBOX_MERCHANT_ID"),
                    "sandbox_secret" => env("ENVPAY_GLO_COMMON_SANDBOX_SECRET"),
                    "sandbox_account" => ''
                ],
                'production' => [
                    "host" => "https://pay.realexpayments.com/pay",
                    "merchant_id" => env("ENVPAY_GLO_COMMON_MERCHANT_ID"),
                    "secret" => env("ENVPAY_GLO_COMMON_SECRET"),
                    "account" => env("ENVPAY_GLO_HMPO_allALL2all_ACCOUNT")
                ]
            ]
        ],
        'itAll2uk' => [
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
                    "sandbox_host" => "https://pay.sandbox.realexpayments.com/pay",
                    "sandbox_merchant_id" => env("ENVPAY_GLO_COMMON_SANDBOX_MERCHANT_ID"),
                    "sandbox_secret" => env("ENVPAY_GLO_COMMON_SANDBOX_SECRET"),
                    "sandbox_account" => ''
                ],
                'production' => [
                    "host" => "https://pay.realexpayments.com/pay",
                    "merchant_id" => env("ENVPAY_GLO_COMMON_MERCHANT_ID"),
                    "secret" => env("ENVPAY_GLO_COMMON_SECRET"),
                    "account" => env("ENVPAY_GLO_HMPO_itALL2uk_ACCOUNT")
                ]
            ]
        ],
        'gbAll2uk' => [
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
                    "sandbox_host" => "https://pay.sandbox.realexpayments.com/pay",
                    "sandbox_merchant_id" => env("ENVPAY_GLO_COMMON_SANDBOX_MERCHANT_ID"),
                    "sandbox_secret" => env("ENVPAY_GLO_COMMON_SANDBOX_SECRET"),
                    "sandbox_account" => ''
                ],
                'production' => [
                    "host" => "https://pay.realexpayments.com/pay",
                    "merchant_id" => env("ENVPAY_GLO_COMMON_MERCHANT_ID"),
                    "secret" => env("ENVPAY_GLO_COMMON_SECRET"),
                    "account" => env("ENVPAY_GLO_BEgbALL2be_ACCOUNT")
                ]
            ]
        ],
        'byMSQ2uk' => [
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
                    "sandbox_host" => "https://pay.sandbox.realexpayments.com/pay",
                    "sandbox_merchant_id" => env("ENVPAY_GLO_COMMON_SANDBOX_MERCHANT_ID"),
                    "sandbox_secret" => env("ENVPAY_GLO_COMMON_SANDBOX_SECRET"),
                    "sandbox_account" => ''
                ],
                'production' => [
                    "host" => "https://pay.realexpayments.com/pay",
                    "merchant_id" => env("ENVPAY_GLO_COMMON_MERCHANT_ID"),
                    "secret" => env("ENVPAY_GLO_COMMON_SECRET"),
                    "account" => env("ENVPAY_GLO_BEgbALL2be_ACCOUNT")
                ]
            ]
        ],
        'ruEKA2uk' => [
            /*'globaliris' => [
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
                    "sandbox_host" => "https://pay.sandbox.realexpayments.com/pay",
                    "sandbox_merchant_id" => env("ENVPAY_GLO_COMMON_SANDBOX_MERCHANT_ID"),
                    "sandbox_secret" => env("ENVPAY_GLO_COMMON_SANDBOX_SECRET"),
                    "sandbox_account" => ''
                ],
                'production' => [
                    "host" => "https://pay.realexpayments.com/pay",
                    "merchant_id" => env("ENVPAY_GLO_COMMON_MERCHANT_ID"),
                    "secret" => env("ENVPAY_GLO_COMMON_SECRET"),
                    "account" => env("ENVPAY_GLO_BEgbALL2be_ACCOUNT")
                ]
            ],*/
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'ruOVB2uk' => [
            /*'globaliris' => [
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
                    "sandbox_host" => "https://pay.sandbox.realexpayments.com/pay",
                    "sandbox_merchant_id" => env("ENVPAY_GLO_COMMON_SANDBOX_MERCHANT_ID"),
                    "sandbox_secret" => env("ENVPAY_GLO_COMMON_SANDBOX_SECRET"),
                    "sandbox_account" => ''
                ],
                'production' => [
                    "host" => "https://pay.realexpayments.com/pay",
                    "merchant_id" => env("ENVPAY_GLO_COMMON_MERCHANT_ID"),
                    "secret" => env("ENVPAY_GLO_COMMON_SECRET"),
                    "account" => env("ENVPAY_GLO_BEgbALL2be_ACCOUNT")
                ]
            ],*/
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'ruLED2uk' => [
            /*'globaliris' => [
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
                    "sandbox_host" => "https://pay.sandbox.realexpayments.com/pay",
                    "sandbox_merchant_id" => env("ENVPAY_GLO_COMMON_SANDBOX_MERCHANT_ID"),
                    "sandbox_secret" => env("ENVPAY_GLO_COMMON_SANDBOX_SECRET"),
                    "sandbox_account" => ''
                ],
                'production' => [
                    "host" => "https://pay.realexpayments.com/pay",
                    "merchant_id" => env("ENVPAY_GLO_COMMON_MERCHANT_ID"),
                    "secret" => env("ENVPAY_GLO_COMMON_SECRET"),
                    "account" => env("ENVPAY_GLO_BEgbALL2be_ACCOUNT")
                ]
            ],*/
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'kzALA2uk' => [
            /*'globaliris' => [
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
                    "sandbox_host" => "https://pay.sandbox.realexpayments.com/pay",
                    "sandbox_merchant_id" => env("ENVPAY_GLO_COMMON_SANDBOX_MERCHANT_ID"),
                    "sandbox_secret" => env("ENVPAY_GLO_COMMON_SANDBOX_SECRET"),
                    "sandbox_account" => ''
                ],
                'production' => [
                    "host" => "https://pay.realexpayments.com/pay",
                    "merchant_id" => env("ENVPAY_GLO_COMMON_MERCHANT_ID"),
                    "secret" => env("ENVPAY_GLO_COMMON_SECRET"),
                    "account" => env("ENVPAY_GLO_BEgbALL2be_ACCOUNT")
                ]
            ],*/
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'lbAll2uk' => [
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'sdAll2uk' => [
            'pay_later' => [
                'label' => "Pay later",
            ]
        ]
    ],
    'leg_be' =>[
        'allAll2all'=> [
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'zaAll2be' => [
            'paypal' => [
                'label' => 'PAYPAL pay',
                'common' => [
                    'env' => 'live',
                    "activated" => true,
                    "currency" => "EUR",
                    "txn_fee_rate" => "0",
                    "txn_fee_extra" => "0",
                    "return_url" => "/paypal/return",
                    "notify_url" => "/paypal/notify"
                ],
                'sandbox' => [
                    "sandbox_host" => "https://www.sandbox.paypal.com/cgi-bin/webscr",
                    "sandbox_account" => env("ENVPAY_PAY_COMMON_SANDBOX_ACCOUNT")
                ],
                'production' => [
                    "host" => "https://www.paypal.com/cgi-bin/webscr",
                    "account" => env("ENVPAY_PAY_BEzaALL2be_ACCOUNT")
                ]
            ]
        ],
        'gbAll2be' => [
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
                    "sandbox_host" => "https://pay.sandbox.realexpayments.com/pay",
                    "sandbox_merchant_id" => env("ENVPAY_GLO_COMMON_SANDBOX_MERCHANT_ID"),
                    "sandbox_secret" => env("ENVPAY_GLO_COMMON_SANDBOX_SECRET"),
                    "sandbox_account" => ''
                ],
                'production' => [
                    "host" => "https://pay.realexpayments.com/pay",
                    "merchant_id" => env("ENVPAY_GLO_COMMON_MERCHANT_ID"),
                    "secret" => env("ENVPAY_GLO_COMMON_SECRET"),
                    "account" => env("ENVPAY_GLO_BEgbALL2be_ACCOUNT")
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
                'production' => [
                    "host" => "https://www.paypal.com/cgi-bin/webscr",
                    "account" => env("ENVPAY_PAY_BEruMOW2be_ACCOUNT")
                ]
            ]
        ],
    ],
    'leg_de' => [
        'allAll2all'=> [
            'pay_later' => [
                'label' => "Pay later",
            ]
        ],
        'egAll2de' => [
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
                    'merchant_id' => 'FAW_egAll2de_LEGALIZATION_SANDBOX_MERCHANT_ID',
                    'secret_key' => 'FAW_egAll2de_LEGALIZATION_SANDBOX_SECURITY_KEY',
                ],
                'production' => [
                    'host' => 'https://www.atfawry.com',
                    'merchant_id' => 'FAW_egAll2de_LEGALIZATION_MERCHANT_ID',
                    'secret_key' => 'FAW_egAll2de_LEGALIZATION_SECURITY_KEY',
                ]
            ]
        ],
        'maAll2de'=> [
            'binga' => [
                'label' => 'BINGA pay',
                'common' => [
                    'env' => 'live',
                    "currency" => "MAD",
                    'expiration' => '+2 hours',
                    "cash_url" => "/binga/cash",
                    "return_url" => "/binga/return",
                    "notify_url" => "/binga/notify"
                ],
                'sandbox' => [
                    "host" => "http://preprod.binga.ma:8080/v1.2/api/orders",
                    "merchant_login" => env("ENVPAY_BINGA_SANDBOX_MERCHANT_LOGIN"),
                    "merchant_password" => env("ENVPAY_BINGA_SANDBOX_MERCHANT_PASSWORD"),
                    "store_id" => env("ENVPAY_BINGA_SANDBOX_STORE_ID"),
                    "store_private_key" => env("ENVPAY_BINGA_SANDBOX_STORE_PRIVATE_KEY"),
                ],
                'production' => [
                    "host" => "https://api.binga.ma/bingaApi/api/orders",
                    "merchant_login" => env("ENVPAY_BINGA_MERCHANT_LOGIN"),
                    "merchant_password" => env("ENVPAY_BINGA_MERCHANT_PASSWORD"),
                    "store_id" => env("ENVPAY_BINGA_STORE_ID"),
                    "store_private_key" => env("ENVPAY_BINGA_STORE_PRIVATE_KEY"),
                ]
            ]
        ],
    ]
];
