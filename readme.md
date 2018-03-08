## Chrome Tester

Simple wrapper over chrome web driver.

### Installation

```bash
composer require mcmatters/chrome-tester --dev
```

### Usage

```php
<?php

declare(strict_types = 1);

use Facebook\WebDriver\WebDriverBy;
use McMatters\ChromeTester\Browser;

require 'vendor/autoload.php';

$chrome = Browser::make()->getChromeDriver();

$site = $chrome->get('https://google.com');
$site->findElement(WebDriverBy::cssSelector('input[name=q]'))
    ->clear()
    ->sendKeys('Test');

$site->findElement(WebDriverBy::cssSelector('form'))->submit();

echo $site->getPageSource();
```
