# TaloPay_Transfer

## Description

TaloPay allows you to process bank transfers securely and efficiently.

## Installation

Use [composer](https://getcomposer.org/) to install TaloPay_Transfer.

```
composer require talopay/module-transfer
```

Then you'll need to activate the module.

```
bin/magento module:enable TaloPay_Transfer
bin/magento setup:upgrade
```

## Uninstall

```
bin/magento module:uninstall TaloPay_Transfer
```

If you used Composer for installation Magento will remove the files and database information.

## License

[OSL-3.0](http://opensource.org/licenses/osl-3.0.php)
