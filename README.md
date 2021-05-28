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
AWS_SECRET_ACCESS_KEY
```


## ERROR CODE
P0001: transaction does not exists
P0002: this transaction has been cancelled
P0003: this transaction has been done already
P0004: this transaction expired
P0005: payment gateway not found for postal
P0006: unknown_error
P0007: Transaction items can`t be parsed.
P0008: Transaction items not found.
P0009: API did not receive any parameters
P0010: merchantRefNumber is empty
P0011: transaction id does not exists
P0012: transaction has been cancelled
P0013: signature verification failed
P0014: payment amount is incorrect
P0015: empty charge response from fawry
P0017: transaction has been paid already
P0018: Your transaction has been finish by another gateway, please check
P0019: paygate error
P0020: Pay onsite has been choose, You can come to our office, and pay your fee.
P0021: paypal error
P0022: The deal was not completed or delay

