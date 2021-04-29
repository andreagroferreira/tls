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
