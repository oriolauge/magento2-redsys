# Magento 2 Redsys module
This module adds an integration to pay with Redsys system (redirection mode).

Currently, we only accept "authorization" method (standard payment), but in the future we will implement others Redsys methods like "pre-authorization", refunds, etc.

If you want more information about Redsys system (redirection mode), you can click on the next [link](https://pagosonline.redsys.es/conexion-redireccion.html)

## Technologies
- Magento 2.4.7-p3 CE
- Stability: Stable Build

## Instalation guide
The instructions to install this module are:
```
composer require oag/module-redsys
php bin/magento module:enable OAG_Redsys # If you upload via FTP
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:flush
```
## Uninstalation guide
You can execute command ```php bin/magento module:uninstall OAG_Redsys``` to uninstall module. Also, please refresh magento cache after uninstall.

## Key Features
* Create order only when Redsys confirm the payment (no more orders with pending status)
* No redirection pages in checkout (we use new webapi methods to avoid this step tipical in Magento 1)

## Future Features
* Payments with Bizum
* Accepts SOAP integration with Redsys