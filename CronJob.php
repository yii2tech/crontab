<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\crontab;

use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\Model;

/**
 * CronJob represents single line of cron configuration file (crontab) - single cron job.
 * Each cron job consist of schedule pattern and command, which should be executed when
 * system time matches the schedule pattern.
 *
 * Cron job may be setup via attributes:
 *
 * ```php
 * use yii2tech\crontab\CronJob;
 *
 * $cronJob = new CronJob();
 * $cronJob->min = '0';
 * $cronJob->hour = '0';
 * $cronJob->command = 'my-shell-command';
 * echo $cronJob->getLine();
 * ```
 *
 * It can also parse given crontab line:
 *
 * ```php
 * use yii2tech\crontab\CronJob;
 *
 * $cronJob = new CronJob();
 * $cronTabLine = '0 0 * * * my-shell-command';
 * $cronJob->setLine($cronTabLine);
 * echo $cronJob->command;
 * ```
 *
 * @see http://en.wikipedia.org/wiki/Crontab
 *
 * @property string $line formatted crontab line.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class CronJob extends Model
{
    /**
     * @var string minute pattern.
     */
    public $min = '*';
    /**
     * @var string hour pattern.
     */
    public $hour = '*';
    /**
     * @var string day of month pattern.
     */
    public $day = '*';
    /**
     * @var string month pattern.
     */
    public $month = '*';
    /**
     * @var string day of week pattern.
     */
    public $weekDay = '*';
    /**
     * @var string year pattern.
     * This parameter is optional and may be not supported by particular crontab services.
     */
    public $year;
    /**
     * @var string command to execute.
     */
    public $command;


    /**
     * Sets internal attributes by formatted crontab line.
     * @param string $line crontab line.
     * @return $this self reference.
     */
    public function setLine($line)
    {
        $this->parseLine($line);
        return $this;
    }

    /**
     * Returns formatted cron tab line composed from internal attributes
     * @param bool $runValidation whether to run validation before composing line.
     * @throws InvalidConfigException on failure.
     * @return string cron tab line.
     */
    public function getLine($runValidation = true)
    {
        if ($runValidation && !$this->validate()) {
            throw new InvalidConfigException($this->getErrorSummary());
        }
        return $this->composeLine();
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            'min',
            'hour',
            'day',
            'month',
            'weekDay',
            'year',
            'command',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['min', 'hour', 'day', 'month', 'weekDay', 'command'], 'required'],
            [['min', 'hour', 'year'], 'match', 'pattern' => '/^[0-9\*\/\,\-]+$/s'],
            ['month', 'match', 'pattern' => '/^[A-Z0-9\*\/\,\-]+$/s'],
            ['day', 'match', 'pattern' => '/^[0-9\*\/\,\-\?LW]+$/s'],
            ['weekDay', 'match', 'pattern' => '/^[A-Z0-6\*\/\,\-L#]+$/s'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'min' => 'Minutes',
            'hour' => 'Hours',
            'day' => 'Day of month',
            'month' => 'Month',
            'weekDay' => 'Day of week',
            'year' => 'Year',
            'command' => 'Command to execute',
        ];
    }

    /**
     * Composes the cron job into cron tab line.
     * @return string cron tab line.
     */
    protected function composeLine()
    {
        $parts = [
            $this->min,
            $this->hour,
            $this->day,
            $this->month,
            $this->weekDay,
        ];
        $year = $this->year;
        if (strlen($year) > 0) {
            $parts[] = $year;
        }
        $parts[] = $this->command;
        return implode(' ', $parts);
    }

    /**
     * Parse formatted cron tab line, filling up internal attributes.
     * Note: optional 'year' parameter will not be parsed and will be considered as
     * a part of command!
     * @param string $line formatted cron tab line.
     * @throws InvalidParamException on invalid line format.
     */
    protected function parseLine($line)
    {
        $line = trim($line);
        $partsCount = 6;
        $parts = explode(' ', $line, $partsCount);
        if (count($parts) < $partsCount) {
            throw new InvalidParamException('Cron job line "' . $line . '" invalid.');
        }
        $this->command = array_pop($parts);
        $this->min = array_shift($parts);
        $this->hour = array_shift($parts);
        $this->day = array_shift($parts);
        $this->month = array_shift($parts);
        $this->weekDay = array_shift($parts);
    }

    /**
     * @return string string representation of the object.
     */
    public function __toString()
    {
        return $this->getLine();
    }

    /**
     * Composes errors single string summary.
     * @param string $delimiter errors delimiter.
     * @return string error summary
     */
    public function getErrorSummary($delimiter = "\n")
    {
        $errorSummaryParts = array();
        foreach ($this->getErrors() as $attributeErrors) {
            $errorSummaryParts = array_merge($errorSummaryParts, $attributeErrors);
        }
        return implode($delimiter, $errorSummaryParts);
    }
} 