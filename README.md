Crontab Extension for Yii 2
===========================

This extension adds [Crontab](http://en.wikipedia.org/wiki/Crontab) setup support.

For license information check the [LICENSE](LICENSE.md)-file.

[![Latest Stable Version](https://poser.pugx.org/yii2tech/crontab/v/stable.png)](https://packagist.org/packages/yii2tech/crontab)
[![Total Downloads](https://poser.pugx.org/yii2tech/crontab/downloads.png)](https://packagist.org/packages/yii2tech/crontab)
[![Build Status](https://travis-ci.org/yii2tech/crontab.svg?branch=master)](https://travis-ci.org/yii2tech/crontab)


Requirements
------------

This extension requires Linux OS. 'crontab' should be installed and cron daemon should be running.


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist yii2tech/crontab
```

or add

```json
"yii2tech/crontab": "*"
```

to the require section of your composer.json.


Usage
-----

You can setup cron tab using [[yii2tech\crontab\CronTab]], for example:

```php
use yii2tech\crontab\CronTab;

$cronTab = new CronTab();
$cronTab->setJobs([
    [
        'min' => '0',
        'hour' => '0',
        'command' => 'php /path/to/project/yii some-cron',
    ],
    [
        'line' => '0 0 * * * php /path/to/project/yii another-cron'
    ]
]);
$cronTab->apply();
```
