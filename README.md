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

## Configuration

### Notification status

**No:** No notification will be sent by the module. The standard Adobe Commerce notification flow will handle all
communications.

**On every transaction:** An email with relevant transfer information will be sent every time a webhook is triggered —
even multiple times for the same order.

**When total is paid:** A notification will be sent only once, when the order reaches the status configured under
"Payment has been accepted". It informs that the full payment has been received.

## Uninstall

```
bin/magento module:uninstall TaloPay_Transfer
```

If you used Composer for installation Magento will remove the files and database information.

## License

[OSL-3.0](http://opensource.org/licenses/osl-3.0.php)
