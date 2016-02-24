<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\crontab;

use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;

/**
 * CronTab allows management of the cron jobs.
 *
 * Note: Each system user has his own crontab, make sure you always run this class
 * for the same user. For the web application it is usually 'apache', for the console
 * application - current local user or root.
 *
 * Example usage:
 *
 * ```php
 * use yii2tech\crontab\CronTab;
 *
 * $cronTab = new CronTab();
 * $cronTab->setJobs([
 *     [
 *         'min' => '0',
 *         'hour' => '0',
 *         'command' => 'php /path/to/project/yii some-cron',
 *     ],
 *     [
 *         'line' => '0 0 * * * php /path/to/project/yii another-cron'
 *     ]
 * ]);
 * $cronTab->apply();
 * ```
 *
 * @see CronJob
 * @see http://en.wikipedia.org/wiki/Crontab
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class CronTab extends Component
{
    /**
     * @var string path to the 'crontab' command, for example: '/usr/bin/crontab'.
     * Default is 'crontab' assuming 'crontab' command is available in OS shell.
     */
    public $binPath = 'crontab';
    /**
     * @var array default configuration for the cron job objects.
     */
    public $defaultJobConfig = [
        'class' => 'yii2tech\crontab\CronJob'
    ];
    /**
     * @var string|callable filter, which indicates whether existing cron job should be removed on cron tab merging.
     * Value could be a plain string, which presence in the cron job line indicates it should be removed, or a callable
     * of following signature: `boolean function (string $line)`, which shoult return `true` if line should be removed.
     */
    public $mergeFilter;
    /**
     * @var CronJob[]|array[] list of [[CronJob]] instances or their array configurations.
     */
    private $_jobs = [];


    /**
     * @param array $jobs
     * @return static self reference
     */
    public function setJobs($jobs)
    {
        $this->_jobs = $jobs;
        return $this;
    }

    /**
     * @return array
     */
    public function getJobs()
    {
        return $this->_jobs;
    }

    /**
     * Composes cron job line from configuration.
     * @param CronJob|array $job cron job line or configuration.
     * @return string cron job line.
     * @throws InvalidConfigException on invalid job format
     */
    protected function composeJobLine($job)
    {
        if (is_array($job)) {
            $job = $this->createJob($job);
        }
        if (!($job instanceof CronJob)) {
            throw new InvalidConfigException('Cron job should be an instance of "' . CronJob::className() . '" or its array configuration - "' . gettype($job) . '" given.');
        }
        return $job->getLine();
    }

    /**
     * Creates cron job instance from its array configuration.
     * @param array $config cron job configuration.
     * @return CronJob cron job instance.
     */
    protected function createJob(array $config)
    {
        return Yii::createObject(array_merge($this->defaultJobConfig, $config));
    }

    /**
     * Returns the crontab lines composed from [[jobs]].
     * @return array crontab lines.
     */
    public function getLines()
    {
        $lines = [];
        foreach ($this->getJobs() as $job) {
            $lines[] = $this->composeJobLine($job);
        }
        return $lines;
    }

    /**
     * Returns current cron jobs setup in the system fro current user.
     * @return array cron job lines.
     */
    public function getCurrentLines()
    {
        $command = $this->binPath . ' -l 2>&1';
        $outputLines = [];
        exec($command, $outputLines);
        $lines = [];
        foreach ($outputLines as $outputLine) {
            if (stripos($outputLine, 'no crontab') !== 0) {
                $lines[] = trim($outputLine);
            }
        }
        return $lines;
    }

    /**
     * Setup the cron jobs from given file.
     * @param string $filename file name.
     * @return static self reference.
     * @throws InvalidParamException on failure.
     * @throws Exception on failure to setup crontab.
     */
    public function applyFile($filename)
    {
        if (!file_exists($filename)) {
            throw new InvalidParamException("File '{$filename}' does not exist.");
        }
        $command = $this->binPath . ' < ' . escapeshellarg($filename) . ' 2>&1';
        exec($command, $outputLines, $exitCode);
        if ($exitCode !== 0) {
            throw new Exception("Failure to setup crontab from file '{$filename}': " . implode("\n", $outputLines));
        }
        return $this;
    }

    /**
     * Saves the current jobs into the text file.
     * @param string $fileName output file name.
     * @return integer number of written bytes.
     */
    public function saveToFile($fileName)
    {
        $lines = $this->getLines();
        $content = $this->composeFileContent($lines);
        return file_put_contents($fileName, $content);
    }

    /**
     * Composes the crontab file content from given lines.
     * @param array $lines crontab lines.
     * @return string crontab file content.
     */
    protected function composeFileContent(array $lines)
    {
        if (empty($lines)) {
            return '';
        }
        return implode("\n", $lines) . "\n";
    }

    /**
     * Applies the current {@link jobs} to the current user crontab.
     * This method will merge new jobs with the ones already set in the system.
     * @return static self reference.
     */
    public function apply()
    {
        $lines = $this->mergeLines($this->getCurrentLines(), $this->getLines());
        $this->applyLines($lines);
        return $this;
    }

    /**
     * Applies given lines to current user crontab.
     * @param array $lines crontab lines.
     * @throws Exception on failure.
     */
    protected function applyLines(array $lines)
    {
        $content = $this->composeFileContent($lines);
        $fileName = tempnam(Yii::getAlias('@runtime'), str_replace('\\', '_', get_class($this)));
        if ($fileName === false) {
            throw new Exception('Unable to create temporary file.');
        }
        file_put_contents($fileName, $content);
        $this->applyFile($fileName);
        unlink($fileName);
    }

    /**
     * Removes current [[jobs]] from the current user crontab.
     * @return static self reference.
     */
    public function remove()
    {
        $currentLines = $this->getCurrentLines();
        $lines = $this->getLines();
        $remainingLines = array_diff($currentLines, $lines);
        if (empty($remainingLines)) {
            $this->removeAll();
        } else {
            $this->applyLines($remainingLines);
        }
        return $this;
    }

    /**
     * Removes all cron jobs for the current user.
     * @return static self reference.
     */
    public function removeAll()
    {
        $command = $this->binPath . ' -r 2>&1';
        exec($command);
        return $this;
    }

    /**
     * Merges existing crontab lines with new ones, applying [[mergeFilter]].
     * @param array $currentLines lines to be merged to
     * @param array $newLines lines to be merged from.
     * @return array merged lines
     */
    protected function mergeLines($currentLines, $newLines)
    {
        if ($this->mergeFilter === null) {
            $result = $currentLines;
        } else {
            $result = [];
            foreach ($currentLines as $line) {
                if (is_string($this->mergeFilter)) {
                    if (strpos($line, $this->mergeFilter) !== false) {
                        continue;
                    }
                } else {
                    if (call_user_func($this->mergeFilter, $line)) {
                        continue;
                    }
                }
                $result[] = $line;
            }
        }

        foreach ($newLines as $line) {
            if (!in_array($line, $result)) {
                $result[] = $line;
            }
        }
        return $result;
    }
}