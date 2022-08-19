<?php
return [
    'alipay' => [
        'label'   => 'Alipay pay',
        'common'  => [
            'env' => 'live',
            'activated' => true,
            'product_code' => 'FAST_INSTANT_TRADE_PAY',
            'method' => 'alipay.trade.page.pay',
            'return_url'  => '/alipay/return',
            'notify_url'  => '/alipay/notify',
        ],
        'sandbox' => [
            'app_id' => null,
            'gateway' => null,
            'private_key' => null,
            'public_key' => null,
        ],
        'prod'    => [
            'app_id' => null,
            'gateway' => null,
            'private_key' => null,
            'public_key' => null,
        ]
    ],
    'binga' => [
        'label' => 'BINGA pay',
        'common' => [
            'env' => 'live',
            'currency' => 'MAD',
            'cash_url' => '/binga/cash',
            'return_url' => '/binga/return',
            'notify_url' => '/binga/notify'
        ],
        'sandbox' => [
            'host' => 'http://preprod.binga.ma:8080/v1.2/api/orders',
            'merchant_login' => null,
            'merchant_password' => null,
            'store_id' => null,
            'store_private_key' => null,
        ],
        'prod' => [
            'host' => 'https://api.binga.ma/bingaApi/api/orders',
            'merchant_login' => null,
            'merchant_password' => null,
            'store_id' => null,
            'store_private_key' => null,
        ]
    ],
    'bnp' => [
        'label' => 'BNP Paribas pay',
        'active' => true,
        'common' => [
            'env' => 'live',
            'return_url' => '/bnp/return',
            'currency' => 'DZD',
            'txn_fee_rate' => '0',
            'txn_fee_extra' => '0',
            'min_fraction_digits' => '2',
            'language' => 'FR',
        ],
        'sandbox' => [
            'host' => 'https://test.satim.dz',
            'user_name' => null,
            'password' => null,
            'terminal_id' => null,
        ],
        'prod' => [
            // TODO: To be completed
            'host' => 'test', // env('ENVPAY_BNP_PARIBAS_dzALL2fr_HOST')
            'user_name' => null,
            'password' => null,
            'terminal_id' => null,
        ]
    ],
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
            'sandbox_user_name' => null,
            'sandbox_password'  => null
        ],
        'prod'    => [
            'host'      => 'https://ipay.clictopay.com/payment/rest',
            'user_name' => null,
            'password'  => null
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
            'merchant_id' => null,
            'storeKey' => null,
        ],
        'prod' => [
            'host' => 'https://payment.cmi.co.ma/fim/est3Dgate',
            'merchant_id' => null,
            'storeKey' => null
        ]
    ],
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
            'merchant_id' => null,
            'secret_key' => null,
        ],
        'prod' => [
            'host' => 'https://www.atfawry.com',
            'merchant_id' => null,
            'secret_key' => null,
        ]
    ],
    'globaliris' => [
        'label' => 'GLOBALIRIS pay',
        'common' => [
            'env' => 'live',
            'activated' => true,
            'currency' => 'GBP',
            'min_fraction_digits' => '2',
            'txn_fee_rate' => '0',
            'txn_fee_extra' => '0',
            'return_url' => '/globaliris/return'
        ],
        'sandbox' => [
            'sandbox_host' => 'https://hpp.sandbox.globaliris.com/pay',
            'sandbox_merchant_id' => null,
            'sandbox_secret' => null,
            'sandbox_account' => null
        ],
        'prod' => [
            'host' => 'https://hpp.globaliris.com/pay',
            'merchant_id' => null,
            'secret' => null,
            'account' => null
        ]
    ],
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
            'sandbox_apikey'        => null,
            'sandbox_secret'        => null,
            'sandbox_mid'           => null
        ],
        'prod'    => [
            'host'          => 'https://kpaymentgateway-services.kasikornbank.com',
            'redirect_host' => 'https://kpaymentgateway.kasikornbank.com/ui/v2/kpayment.min.js',
            'apikey'        => null,
            'secret'        => null,
            'mid'           => null
        ]
    ],
    'payfort' => [
        'label' => 'payfort pay',
        'common' => [
            'env' => 'live',
            'activated' => true,
            'currency' => 'LBP',
            'return_url' => '/payfort/return',
            'notify_url' => '/payfort/notify',
        ],
        'sandbox' => [
            'host'            => 'https://sbcheckout.payfort.com/FortAPI/paymentPage',
            'merchant_id'     => null,
            'access_code'     => null,
            'request_phrase'  => null,
            'response_phrase' => null
        ],
        'prod' => [
            'host'            => 'https://checkout.payfort.com/FortAPI/paymentPage',
            'merchant_id'     => null,
            'access_code'     => null,
            'request_phrase'  => null,
            'response_phrase' => null
        ]
    ],
    'paygate' => [
        'label' => 'Paygate pay',
        'common' => [
            'env' => 'live',
            'activated' => true,
            'currency' => 'ZAR',
            'country' => 'ZAF',
            'return_url' => '/paygate/return',
            'notify_url' => '/paygate/notify',
        ],
        'sandbox' => [
            'sandbox_initiate_host' => 'https://secure.paygate.co.za/payweb3/initiate.trans',
            'sandbox_process_host' => 'https://secure.paygate.co.za/payweb3/process.trans',
            'sandbox_query_host' => 'https://secure.paygate.co.za/payweb3/query.trans',
            'sandbox_encryption_key' => null,
            'sandbox_paygate_id' => null,
            'sandbox_seller_email' => null,
        ],
        'prod' => [
            'initiate_host' => 'https://secure.paygate.co.za/payweb3/initiate.trans',
            'process_host' => 'https://secure.paygate.co.za/payweb3/process.trans',
            'query_host' => 'https://secure.paygate.co.za/payweb3/query.trans',
            'encryption_key' => null,
            'paygate_id' => null,
            'seller_email' => null,
        ],
    ],
    'paypal' => [
        'label' => 'PAYPAL pay',
        'common' => [
            'env' => 'live',
            'activated' => true,
            'currency' => 'EUR',
            'txn_fee_rate' => '0',
            'txn_fee_extra' => '0',
            'return_url' => '/paypal/return',
            'notify_url' => '/paypal/notify'
        ],
        'sandbox' => [
            'sandbox_host' => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
            'sandbox_account' => null
        ],
        'prod' => [
            'host' => 'https://www.paypal.com/cgi-bin/webscr',
            'account' => null
        ]
    ],
    'paysoft' => [
        'label' => 'Paysoft pay',
        'active' => true,
        'common' => [
            'env' => 'live',
            'return_url' => '/paysoft/return',
        ],
        'sandbox' => [
            'host' => 'https://lmi.paysoft.solutions',
            'merchant_id' => null,
            'signature_algorithm' => null,
            'signature_secret_key' => null,
        ],
        'prod' => [
            // TODO: To be completed
            'host' => 'test', //env('ENVPAY_PAYSOFT_uaKBP2pl_HOST')
            'merchant_id' => null,
            'signature_algorithm' => null,
            'signature_secret_key' => null,
        ]
    ],
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
            'sandbox_app_id'      => null,
            'sandbox_private_key' => null,
            'sandbox_api_version' => '1.3.0',
            'sandbox_payments_os_env' => 'test'
        ],
        'prod' => [
            'app_id'      => null,
            'private_key' => null,
            'api_version' => '1.3.0',
            'payments_os_env' => 'live'
        ]
    ],
    'switch' => [
        'label' => 'Switch pay',
        'active' => true,
        'common' => [
            'env' => 'live',
            'currency' => 'USD',
            'return_url' => '/switch/return',
        ],
        'sandbox' => [
            'host' => 'https://test.oppwa.com',
            'entity_id' => null,
            'access_token' => null
        ],
        'prod' => [
            // TODO: To be completed
            'host' => 'test', // env('ENVPAY_SWITCH_iqAll2be_HOST')
            'entity_id' => null,
            'access_token' => null
        ]
    ],
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
            'accountNumber' => null,
            'serviceCode'   => null,
            'ivKey'         => null,
            'secretKey'     => null,
            'accessKey'     => null,
            'clientID'      => null,
            'clientSecret'  => null,
            'oauthHost'     => 'https://developer.tingg.africa/checkout/v2/custom/oauth/token',
            'queryStatusHost'=> 'https://developer.tingg.africa/checkout/v2/custom/requests/query-status',
        ],
        'prod'    => [
            'host'          => 'https://online.tingg.africa/v2/express/',
            'accountNumber' => null,
            'serviceCode'   => null,
            'ivKey'         => null,
            'secretKey'     => null,
            'accessKey'     => null,
            'clientID'      => null,
            'clientSecret'  => null,
            'oauthHost'     => 'https://online.tingg.africa/v2/custom/oauth/token',
            'queryStatusHost'=> 'https://online.tingg.africa/v2/custom/requests/query-status',
        ]
    ],

];