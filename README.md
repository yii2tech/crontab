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

You may specify particular cron job using [[yii2tech\crontab\CronJob]] instance, for example:

```php
use yii2tech\crontab\CronJob;
use yii2tech\crontab\CronTab;

$cronJob = new CronJob();
$cronJob->min = '0';
$cronJob->hour = '0';
$cronJob->command = 'php /path/to/project/yii some-cron';

$cronTab = new CronTab();
$cronTab->setJobs([
    $cronJob
]);
$cronTab->apply();
```

> Tip: [[yii2tech\crontab\CronJob]] is a descendant of [[yii\base\Model]] and have built in validation rules for each
  parameter, thus it can be used in the web forms to create a cron setup interface.


## Parsing cron jobs <span id="parsing-cron-jobs"></span>

[[yii2tech\crontab\CronJob]] composes a cron job line, like:

```
0 0 * * * php /path/to/my/project/yii some-cron
```

However it can also parse such lines filling up own internal attributes. For example:

```php
use yii2tech\crontab\CronJob;

$cronJob = new CronJob();
$cronJob->setLine('0 0 * * * php /path/to/my/project/yii some-cron');

echo $cronJob->min; // outputs: '0'
echo $cronJob->hour; // outputs: '0'
echo $cronJob->day; // outputs: '*'
echo $cronJob->month; // outputs: '*'
echo $cronJob->command; // outputs: 'php /path/to/my/project/yii some-cron'
```


## Merging cron jobs <span id="merging-cron-jobs"></span>

Method [[yii2tech\crontab\CronTab::apply()]] adds all specified cron jobs to crontab, keeping already exiting cron jobs
intact. For example, if current crontab is following:

```
0 0 * * * php /path/to/my/project/yii daily-cron
```

running following code:

```php
use yii2tech\crontab\CronTab;

$cronTab = new CronTab();
$cronTab->setJobs([
    [
        'min' => '0',
        'hour' => '0',
        'weekDay' => '5',
        'command' => 'php /path/to/project/yii weekly-cron',
    ],
]);
$cronTab->apply();
```

will produce following crontab:

```
0 0 * * * php /path/to/my/project/yii daily-cron
0 0 * * 5 php /path/to/my/project/yii weekly-cron
```

While merging crontab lines [[yii2tech\crontab\CronTab::apply()]] avoids duplication, so same cron job will never
be added twice. However while doing this, lines are compared by *exact* match, inlcuding command and time pattern.
If same command added twice with different time pattern - 2 crontab records will be present.
For example, if current crontab is following:

```
0 0 * * * php /path/to/my/project/yii some-cron
```

running following code:

```php
use yii2tech\crontab\CronTab;

$cronTab = new CronTab();
$cronTab->setJobs([
    [
        'min' => '15',
        'hour' => '2',
        'command' => 'php /path/to/project/yii some-cron',
    ],
]);
$cronTab->apply();
```

will produce following crontab:

```
0 0 * * * php /path/to/my/project/yii some-cron
15 2 * * * php /path/to/my/project/yii some-cron
```

You may interfere in merging process using [[yii2tech\crontab\CronTab::mergeFilter]], which allows indicating
those existing cron jobs, which should be removed while merging. Its value could be a plain string - in this case
all lines, which contains this string as a substring will be removed, or a PHP callable of the following signature:
`boolean function (string $line)` - if function returns `true` the line should be removed.
For example, if current crontab is following:

```
0 0 * * * php /path/to/my/project/yii some-cron
```

running following code:

```php
use yii2tech\crontab\CronTab;

$cronTab = new CronTab();
$cronTab->mergeFilter = '/path/to/project/yii'; // filter all invocation of Yii console
$cronTab->setJobs([
    [
        'min' => '15',
        'hour' => '2',
        'command' => 'php /path/to/project/yii some-cron',
    ],
]);
$cronTab->apply();
```

will produce following crontab:

```
15 2 * * * php /path/to/my/project/yii some-cron
```


## Extra lines setup <span id="extra-lines-setup"></span>

Crontab file may content additional lines beside jobs specifications. It may contain comments or extra
shell configuration. For example:

```
# this crontab created by my application
SHELL=/bin/sh
PATH=/usr/bin:/usr/sbin

0 0 * * * php /path/to/my/project/yii some-cron
```

You may append such extra lines into the crontab using [[yii2tech\crontab\CronTab::headLines]]. For example:

```php
use yii2tech\crontab\CronTab;

$cronTab = new CronTab();
$cronTab->headLines = [
    '# this crontab created by my application',
    'SHELL=/bin/sh',
    'PATH=/usr/bin:/usr/sbin',
];
$cronTab->setJobs([
    [
        'min' => '0',
        'hour' => '0',
        'command' => 'php /path/to/project/yii some-cron',
    ],
]);
$cronTab->apply();
```

> Note: usage of the `headLines` may produce unexpected results, while merging crontab with existing one.
