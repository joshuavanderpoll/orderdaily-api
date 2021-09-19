# Orderdaily API Client for PHP

<p align="center">
    <a href="https://github.com/joshuavanderpoll/orderdaily-api"><img src="https://img.shields.io/badge/orderdaily%2Fphp--api-Source%20code-blue?style=flat-square" alt="Source Code"></a>
    <a href="https://github.com/joshuavanderpoll/orderdaily-api/blob/master/LICENSE"><img src="https://img.shields.io/badge/License-Apache-darkcyan.svg?style=flat-square" alt="Read License"></a>
</p>

The Orderdaily API is used for communicating and synchronising your system with Orderdaily. This can be simply done with this library.

## Requirements:
* [PHP 7.0.0 or higher](https://www.php.net/)

## Installation:

### Composer:

We recommend using [composer](https://getcomposer.org/).
When composer is installed, execute the following command in your project folder:

```sh
composer require orderdaily/php-api^1.1.0
````

And be sure to include the composer autoload in your project.

### Example usage:
```php
$client = new \Orderdaily\Client();
$client->set_application_name("ApplicationName");
$client->set_main_api_key("Enter Orderdaily Main API Key");
$client->set_partner_api_key("Orderdaily Partner API Key");

$orders = $client->get_shop_orders(5);
```

### API Authentication:

There are 2 API endpoints build into this library.
1. [Orderdaily API](https://orderdaily.nl) - Used by Orderdaily developers for synchronisation development. An API key is sent to you by Orderdaily.
2. [Orderdaily Partner API](http://partner.orderdaily.nl) - Used by Partners for forwarding orders to the Orderdaily bikers. An API key is sent to you by Orderdaily.
