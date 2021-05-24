<?php
return [

    'expiration_minutes' => 20,

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
                    'okUrl' => 'cmi/return',
                    'failUrl' => 'cmi/return',
                    'hashAlgorithm' => 'ver3',
                    'shopurl' => 'checkout/',
                    'callbackUrl' => 'cmi/callback',
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
            ],
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
                'prod'    => [
                    'host'          => 'https://online.tingg.africa/v2/express/',
                    'accountNumber' => env("ENVPAY_TINGG_BEcmYAO2be_ACCOUNT_NUMBER"),
                    'serviceCode'   => env("ENVPAY_TINGG_BEcmYAO2be_SERVICE_CODE"),
                    'ivKey'         => env("ENVPAY_TINGG_BEcmYAO2be_IVKEY"),
                    'secretKey'     => env("ENVPAY_TINGG_BEcmYAO2be_SECRET_KEY"),
                    'accessKey'     => env("ENVPAY_TINGG_BEcmYAO2be_ACCESS_KEY")
                ]
            ]
        ],
        'cmDLA2be' => [
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
                'prod'    => [
                    'host'          => 'https://online.tingg.africa/v2/express/',
                    'accountNumber' => env("ENVPAY_TINGG_BEcmDLA2be_ACCOUNT_NUMBER"),
                    'serviceCode'   => env("ENVPAY_TINGG_BEcmDLA2be_SERVICE_CODE"),
                    'ivKey'         => env("ENVPAY_TINGG_BEcmDLA2be_IVKEY"),
                    'secretKey'     => env("ENVPAY_TINGG_BEcmDLA2be_SECRET_KEY"),
                    'accessKey'     => env("ENVPAY_TINGG_BEcmDLA2be_ACCESS_KEY")
                ]
            ],
        ],
        'snDKR2be' => [
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
                'prod'    => [
                    'host'          => 'https://online.tingg.africa/v2/express/',
                    'accountNumber' => env("ENVPAY_TINGG_BEsnDKR2be_ACCOUNT_NUMBER"),
                    'serviceCode'   => env("ENVPAY_TINGG_BEsnDKR2be_SERVICE_CODE"),
                    'ivKey'         => env("ENVPAY_TINGG_BEsnDKR2be_IVKEY"),
                    'secretKey'     => env("ENVPAY_TINGG_BEsnDKR2be_SECRET_KEY"),
                    'accessKey'     => env("ENVPAY_TINGG_BEsnDKR2be_ACCESS_KEY")
                ]
            ],
        ],
        'etADD2be' => [
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
                'prod'    => [
                    'host'          => 'https://online.tingg.africa/v2/express/',
                    'accountNumber' => env("ENVPAY_TINGG_BEetADD2be_ACCOUNT_NUMBER"),
                    'serviceCode'   => env("ENVPAY_TINGG_BEetADD2be_SERVICE_CODE"),
                    'ivKey'         => env("ENVPAY_TINGG_BEetADD2be_IVKEY"),
                    'secretKey'     => env("ENVPAY_TINGG_BEetADD2be_SECRET_KEY"),
                    'accessKey'     => env("ENVPAY_TINGG_BEetADD2be_ACCESS_KEY")
                ]
            ],
        ],
        'rwKGL2be' => [
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
                'prod'    => [
                    'host'          => 'https://online.tingg.africa/v2/express/',
                    'accountNumber' => env("ENVPAY_TINGG_BErwKGL2be_ACCOUNT_NUMBER"),
                    'serviceCode'   => env("ENVPAY_TINGG_BErwKGL2be_SERVICE_CODE"),
                    'ivKey'         => env("ENVPAY_TINGG_BErwKGL2be_IVKEY"),
                    'secretKey'     => env("ENVPAY_TINGG_BErwKGL2be_SECRET_KEY"),
                    'accessKey'     => env("ENVPAY_TINGG_BErwKGL2be_ACCESS_KEY")
                ]
            ],
        ],
        'ugKLA2be' => [
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
                'prod'    => [
                    'host'          => 'https://online.tingg.africa/v2/express/',
                    'accountNumber' => env("ENVPAY_TINGG_BEugKLA2be_ACCOUNT_NUMBER"),
                    'serviceCode'   => env("ENVPAY_TINGG_BEugKLA2be_SERVICE_CODE"),
                    'ivKey'         => env("ENVPAY_TINGG_BEugKLA2be_IVKEY"),
                    'secretKey'     => env("ENVPAY_TINGG_BEugKLA2be_SECRET_KEY"),
                    'accessKey'     => env("ENVPAY_TINGG_BEugKLA2be_ACCESS_KEY")
                ]
            ],
        ],
        'egCAI2be' => [
            'fawry' => [
                'label' => 'Fawry pay',
                'common' => [
                    'env' => 'live',
                    'activated' => true,
                    'version' => 'v1',
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
    ]

];
