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
# ALL THE SECRETS here
ENVPAY_CMI_BEmaAll2be_SANDBOX_MERCHANT_ID=$ENVPAY_CMI_BEmaAll2be_SANDBOX_MERCHANT_ID
ENVPAY_CMI_BEmaAll2be_SANDBOX_STOREKEY=$ENVPAY_CMI_BEmaAll2be_SANDBOX_STOREKEY
ENVPAY_TINGG_COMMON_SANDBOX_ACCOUNT_NUMBER=$ENVPAY_TINGG_COMMON_SANDBOX_ACCOUNT_NUMBER
ENVPAY_TINGG_COMMON_SANDBOX_SERVICE_CODE=$ENVPAY_TINGG_COMMON_SANDBOX_SERVICE_CODE
ENVPAY_TINGG_COMMON_SANDBOX_IVKEY=$ENVPAY_TINGG_COMMON_SANDBOX_IVKEY
ENVPAY_TINGG_COMMON_SANDBOX_SECRET_KEY=$ENVPAY_TINGG_COMMON_SANDBOX_SECRET_KEY
ENVPAY_TINGG_COMMON_SANDBOX_ACCESS_KEY="$ENVPAY_TINGG_COMMON_SANDBOX_ACCESS_KEY"
PAYGATE_ZAALL2BE_ENCRYPTION_SANDBOX_KEY=$PAYGATE_ZAALL2BE_ENCRYPTION_SANDBOX_KEY
PAYGATE_ZAALL2BE_SANDBOX_ID=$PAYGATE_ZAALL2BE_SANDBOX_ID
PAYGATE_ZAALL2BE_SELLER_EMAIL=$PAYGATE_ZAALL2BE_SELLER_EMAIL
FAW_COMMON_SANDBOX_MERCHANT_ID=$FAW_COMMON_SANDBOX_MERCHANT_ID
FAW_COMMON_SANDBOX_SECRET_KEY=$FAW_COMMON_SANDBOX_SECRET_KEY
ENVPAY_GLO_COMMON_SANDBOX_MERCHANT_ID=$ENVPAY_GLO_COMMON_SANDBOX_MERCHANT_ID
ENVPAY_GLO_COMMON_SANDBOX_SECRET=$ENVPAY_GLO_COMMON_SANDBOX_SECRET
ENVPAY_PAY_COMMON_SANDBOX_ACCOUNT=$ENVPAY_PAY_COMMON_SANDBOX_ACCOUNT
PAYU_NGALL2BE_SANDBOX_APP_ID=com.tlscontact.payu-kenya
PAYU_NGALL2BE_SANDBOX_PRIVATE_KEY=1924a610-2658-42af-9033-9666e3016063
PAYU_KEALL2BE_SANDBOX_APP_ID=com.tlscontact.payu-nigeria
PAYU_KEALL2BE_SANDBOX_PRIVATE_KEY=8db81936-4da0-4dfa-ad63-d162f95e5286
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

