# magento2-redsys
Redsys Module for Magento 2

## Instalation guide
The instructions to install this module are:
```
composer require oag/module-redsys
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:flush
```
