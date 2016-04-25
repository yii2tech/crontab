Yii 2 Crontab extension Change Log
==================================

1.0.2, April 25, 2016
---------------------

- Bug #10: Fixed `CronTab::getCurrentLines()` unable to recognize empty crontab at some operation systems (klimov-paul)
- Enh #7: Added `CronTab::headLines`, allowing setup shell configuration at the crontab beginning (klimov-paul)
- Enh #6: In case there is not cron jobs to be saved `CronTab` no longer puts new line separator in result file (klimov-paul)


1.0.1, February 10, 2016
------------------------

- Enh #3: `CronTab::applyFile()` now throws an exception on `crontab` command failure (PaVeL-Ekt)


1.0.0, December 26, 2015
------------------------

- Initial release.
