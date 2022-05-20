TLScontact PAYMENT SERVICE API
=====================

# Introduction
This is a simple PAYMENT SERVICE API framework made by Laravel Lumen framework.

# Installation

`git clone git@gitlab.com:dev_tls/tlspay/payment-api.git`

## Configurations in env
Lumen framework inject these configurations to php configuration file like config/database.php.

## Using Composer
This project use `composer` to install dependencies using the below command.

```
cd ~/tls-payment-api
composer install -o
```

# Deployment

## ATLAS DEPLOYMENT
When you launch a pipeline you have to set variables:

- `ENV`: which environment you want to deploy.
- `PROJECT`: name of the Openshift project you want to deploy. Must be existing project!
- `DATABASE`: name of database. For example `schengen-be`.
- `AUTODEPLOY`: set to `true`.
- `TLSCONTACT_API`: tlscontact api url. For example `test3.api.app.tlscontact.com`.

## CI/CD Variables

The AWS configuration for tlspay to download invoice

```
AWS_ACCESS_KEY_ID
AWS_BUCKET
AWS_DEFAULT_REGION
AWS_SECRET_ACCESS_KEY // download invoice
PAYMENT_SERVICE_DOMAIN="https://$PAYMENT_SERVICE_URL" // API need this variable because it need to tell gateway to send the callback to this url
TLSCONTACT_API="$TLSCONTACT_API" // sync actions data
DIRECTUS_DOMAIN="$DIRECTUS_DOMAIN" // fetch recommend avs
# ALL THE SECRETS here
ENVPAY_CMI_BEmaAll2be_SANDBOX_MERCHANT_ID=$ENVPAY_CMI_BEmaAll2be_SANDBOX_MERCHANT_ID
ENVPAY_CMI_BEmaAll2be_SANDBOX_STOREKEY=$ENVPAY_CMI_BEmaAll2be_SANDBOX_STOREKEY
ENVPAY_TINGG_COMMON_SANDBOX_ACCOUNT_NUMBER=$ENVPAY_TINGG_COMMON_SANDBOX_ACCOUNT_NUMBER
ENVPAY_TINGG_COMMON_SANDBOX_SERVICE_CODE=$ENVPAY_TINGG_COMMON_SANDBOX_SERVICE_CODE
ENVPAY_TINGG_COMMON_SANDBOX_IVKEY=$ENVPAY_TINGG_COMMON_SANDBOX_IVKEY
ENVPAY_TINGG_COMMON_SANDBOX_SECRET_KEY=$ENVPAY_TINGG_COMMON_SANDBOX_SECRET_KEY
ENVPAY_TINGG_COMMON_SANDBOX_ACCESS_KEY="$ENVPAY_TINGG_COMMON_SANDBOX_ACCESS_KEY"
ENVPAY_TINGG_COMMON_SANDBOX_CLIENT_ID=$ENVPAY_TINGG_COMMON_SANDBOX_CLIENT_ID
ENVPAY_TINGG_COMMON_SANDBOX_CLIENT_SECRET=$ENVPAY_TINGG_COMMON_SANDBOX_CLIENT_SECRET
PAYGATE_ZAALL2BE_ENCRYPTION_SANDBOX_KEY=$PAYGATE_ZAALL2BE_ENCRYPTION_SANDBOX_KEY
PAYGATE_ZAALL2BE_SANDBOX_ID=$PAYGATE_ZAALL2BE_SANDBOX_ID
PAYGATE_ZAALL2BE_SELLER_EMAIL=$PAYGATE_ZAALL2BE_SELLER_EMAIL
FAW_COMMON_SANDBOX_MERCHANT_ID=$FAW_COMMON_SANDBOX_MERCHANT_ID
FAW_COMMON_SANDBOX_SECRET_KEY=$FAW_COMMON_SANDBOX_SECRET_KEY
ENVPAY_GLO_COMMON_SANDBOX_MERCHANT_ID=$ENVPAY_GLO_COMMON_SANDBOX_MERCHANT_ID
ENVPAY_GLO_COMMON_SANDBOX_SECRET=$ENVPAY_GLO_COMMON_SANDBOX_SECRET
ENVPAY_PAY_COMMON_SANDBOX_ACCOUNT=$ENVPAY_PAY_COMMON_SANDBOX_ACCOUNT
PAYU_NGALL2BE_SANDBOX_APP_ID=com.tlscontact.payu-nigeria
PAYU_NGALL2BE_SANDBOX_PRIVATE_KEY=1924a610-2658-42af-9033-9666e3016063
PAYU_KENYA_SANDBOX_APP_ID=com.tlscontact.payu-kenya
PAYU_KENYA_SANDBOX_PRIVATE_KEY=8db81936-4da0-4dfa-ad63-d162f95e5286
```

PayU test accounts

```
ngABV2be, ngLGV2be, keNBO2be  
visa:
4000015372250142      02/2022       CVV : 123
MasterCard:
5100018609086541      02/2022       CVV : 123

```

## Code structure

The payment gateway configuration is in `config/payment_gateway.php`, configuration is separated by issuer and environment. 

Each payment gateway has own Controller, this controller call the service in `PaymentGateway` dir. each payment gateway service implement 
`PaymentGatewayInterface`.

## ERROR CODE
| CODE | DESC |
|------|-------|
| P0001 | transaction does not exists |
| P0002 | this transaction has been cancelled |
| P0003 | this transaction has been done already| 
| P0004 | this transaction expired |
| P0005 | payment gateway not found for postal |
| P0006 | unknown_error |
| P0007 | Transaction items can`t be parsed. |
| P0008 |  Transaction items not found. |
| P0009 | API did not receive any parameters |
| P0010 | merchantRefNumber is empty |
| P0011 | transaction id does not exists |
| P0012 | transaction has been cancelled |
| P0013 | signature verification failed |
| P0014 | payment amount is incorrect |
| P0015 | empty charge response from fawry |
| P0017 | transaction has been paid already |
| P0018 | Your transaction has been finish by another gateway, please check |
| P0019 | paygate error |
| P0020 | Pay onsite has been choose, You can come to our office, and pay your fee. |
| P0021 | paypal error |
| P0022 | The deal was not completed or delay |
| P0023 | payu error |
| P0024 | Bank Payment has been chosen, You can come to the bank, and pay your fee. |
| P0025 | Bank payment error |

## Payment Gateway Documentation

| Payment methods supported | Coutries supported                                                                                                                                | Currency supported                                                                                                                                                                 | Languages supported | Technical Requirements | Payment flow diagram                                                                                                                                                                                         | Dynamic callback URL capability    | Type of payment (online/offline) | Transaction expiry time | Refund capability                                                    | Reconciliation capability                  | Developer documentation                                                                                          | User documentation URL                         | Sandbox account Testing card numbers/accounts                                                | HelpDesk contacts                                          | TLS employee responsible   |
|---------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------------------|------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|----------------------------------|-------------------------|----------------------------------------------------------------------|--------------------------------------------|------------------------------------------------------------------------------------------------------------------|------------------------------------------------|----------------------------------------------------------------------------------------------|------------------------------------------------------------|----------------------------|
| alipay| cn                                                                                                                                                | GBP,HKD,USD,SGD,JPY,CAD,AUD,EUR,NZD,KRW,THB,CHF,SEK,DKK,NOK,MYR,IDR,PHP,MUR,ILS,LKR,RUB,AED,CZK,ZAR,CNY,TWD | zh |  | ![Payment flow](https://gw.alipayobjects.com/os/skylark-tools/public/files/0ba3e82ad37ecf8649ee4219cfe9d16b.png%26originHeight%3D2023%26originWidth%3D2815%26size%3D526149%26status%3Ddone%26width%3D2815)   | support                            | online                           |                         | support                                                              | https://opendocs.alipay.com/open/028woc    | https://opendocs.alipay.com/home                                                                                 | https://opendocs.alipay.com/open/270/01didh    | Login account:ucvdaj3619@sandbox.com, Login password:111111,Payment Password:111111          | 4007585858                                                 | clement.lin@tlscontact.com |
| clictopay| tnTUN2be                                                                                                                                          | TND                                                                                                                                                                                | en,fr | |                                                                                                                                                                                                              | support                            | online                           |                         | support                                                              |                                            | https://gitlab.com/dev_tls/tlspay/backlog/uploads/5c701c07ceccd0f5eec4e5060c544aa6/Integration-ManualV2.2-EN.pdf | http://www.clictopay.com.tn/espace-integration | card number: 4557691111111113  Expiry: 12/24  Cvv: 375                                       | webmaster@clictopay.com/71 155 800                         | clement.lin@tlscontact.com |
| cmi| maCAS2be,maRBA2de,maTNG2de,maRAK2de                                                                                                               | TND                                                                                                                                                                                | fr,en,ar |  |                                                                                                                                                                                                              | support                            | online                           |                         |                                                                      |                                            |                                                                                                                  |                                                | card number: 4000000000000010  Expiry: 12/22  Cvv: 000                                       | support.ecom@cmi.co.ma/+212 (0) 8 02 00 50 50              | clement.lin@tlscontact.com |
| fawry| egCAI2be                                                                                                                                          | EGP                                                                                                                                                                                | en,ar | |                                                                                                                                                                                                              | support                            | online                           |                         | https://developer.fawrystaging.com/docs/server-apis/refund-issue-api |                                            | https://developer.fawrystaging.com/docs-home                                                                     | https://fawrydigital.com/                      | card number: 4987654321098769  Expiry: 12/22  Cvv: 123                                       |                                                            | clement.lin@tlscontact.com |
| globaliris| gbEDI2be,gbLON2be,gbMNC2be,gbMNC2ch,gbEDI2ch,gbLON2ch,gbLON2de,gbEDI2de,gbMNC2de,allAll2all,itAll2uk,gbAll2uk,byMSQ2uk,ruEKA2uk,ruLED2uk,kzALA2uk | GBP                                                                                                                                                                                | en |  |                                                                                                                                                                                                              | support                            | online                           |                         |                                                                      |                                            | https://developer.globalpay.com                                                                                  | https://www.globalpayments.com/en-gb           | card number: 4263971921001307  Expiry: 12/22  Cvv: 000                                       | https://help.globalpay.com/en-gb                           | clement.lin@tlscontact.com |
| k-bank| thBKK2be                                                                                                                                          | THB                                                                                                                                                                                | en,th | |                                                                                                                                                                                                              | no                                 | online                           |                         |                                                                      |                                            |                                                                                                                  |                                                | card number: 4417706600005830  Expiry: 12/22  Cvv: 123  OTP code: 123456                     | https://www.kasikornbank.com/en/contact/Pages/contact.aspx | clement.lin@tlscontact.com |
| paygate| zaCPT2be,zaJNB2be,zaDUR2be,zaBFN2de,zaCPT2de,zaPLZ2de,zaZAY2de,zaDUR2de                                                                           | ZAR                                                                                                                                                                                | en |  | ![Payment flow diagram](https://docs.paygate.co.za/images/payweb3/process_flow.png)                                                                                                                          | support                            | online                           |                         | https://docs.paygate.co.za/#refund                                   |                                            | https://docs.paygate.co.za                                                                                       | https://www.paygate.co.za/                     | card number: 4000000000000002 Expiry: 12/22  Cvv: 000                                        | infosa@dpogroup.com/+27 (0)87 820 2020                     | clement.lin@tlscontact.com |
| paypal| ruMOW2be,zaPRY2ch,zaCPT2ch,zaDUR2ch,ruMOW2ch,uaKBP2ch,phMNL2ch                                                                                    | EUR,RUB,PHP                                                                                                                                                                        | en,ru |  |                                                                                                                                                                                                              | support                            | online                           |                         |                                                                      | https://developer.paypal.com/docs/reports/ | https://developer.paypal.com/home                                                                                | https://www.paypal.com/lu/home                 | Login account: qa.buyer@tlscontact.com   Login password: *UHBgvfr4                           | https://www.paypal.com/lu/smarthelp/contact-us             | clement.lin@tlscontact.com |
| paysoft| uaKBP2pl                                                                                                                                          | UAH                                                                                                                                                                                | ru,uk,en |  |                                                                                                                                                                                                              | support                            | online                           |                         |                                                                      |                                            | https://docs.paysoft.solutions/en/2_merchant_interface/                                                          | https://paysoft.co.za/card-solutions/          | https://docs.paysoft.solutions/en/test-params.html                                           | (021) 551 0891/contactus@paysoft.co.za                     | clement.lin@tlscontact.com |
| payu| ngABV2be,ngLGV2be,keNBO2be,keNBO2de                                                                                                               | USD                                                                                                                                                                                | |  | ![Payment flow diagram](https://devguide.payu.in/wordpress/index.php/wp-json/getobject?keyname=uploads/2021/05/word-image-4.png)                                                                             | support                            | online                           |                         |                                                                      |                                            | https://developers.paymentsos.com/docs/apis/payments/1.3.0/#operation/suspend-a-network-token                    | https://payu.in/payment-gateway                | card number: 4000015372250142(visa) or 5100018609086541(MasterCard)  Expiry: 12/22  Cvv: 123 | https://help.payu.in/                                      | clement.lin@tlscontact.com |
| switch | iqBGW2be                                                                                                                                          | IQD                                                                                                                                                                                | en | |                                                                                                                                                                                                              | support                            | online                           |                         | support                                                              |                                            | https://hyperpay.docs.oppwa.com/                                                                                 |                                                | 5285 7800 1058 5166 07/24 736 test                                                           |                                                            | clement.lin@tlscontact.com |
| tingg| snDKR2be,ugKLA2be,tzDAR2de,ugKLA2de                                                                                                               | Currency supported                                                                                                                                                                 | en |  |                                                                                                                                                                                                              | support                            | online                           |                         |                                                                      |                                            | https://dev-portal.tingg.africa/                                                                                 | https://www.cellulant.io/                      | https://cellulant.gitbook.io/checkout/appendix/test-details                                  | support@tingg.com.ng/+234(0)-18883432                      | clement.lin@tlscontact.com |
