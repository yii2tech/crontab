<?php

namespace yii2tech\tests\unit\crontab;

use Yii;
use yii\helpers\FileHelper;
use yii2tech\crontab\CronTab;

/**
 * Test case for [[CronTab]].
 * @see CronTab
 */
class CronTabTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $testFilePath = $this->getTestFilePath();
        FileHelper::createDirectory($testFilePath);
        $this->createCronTabBackup();
    }

    protected function tearDown()
    {
        $testFilePath = $this->getTestFilePath();
        $this->restoreCronTabBackup();
        FileHelper::removeDirectory($testFilePath);

        parent::tearDown();
    }

    /**
     * Returns the test file path.
     * @return string file path.
     */
    protected function getTestFilePath()
    {
        $filePath = Yii::getAlias('@yii2tech/tests/unit/crontab/runtime') . DIRECTORY_SEPARATOR . getmypid();
        return $filePath;
    }

    /**
     * Returns the test file path.
     * @return string file path.
     */
    protected function getCronTabBackupFileName()
    {
        $filePath = $this->getTestFilePath() . DIRECTORY_SEPARATOR . '_crontab_backup.tmp';
        return $filePath;
    }

    /**
     * Backs up the current crontab content.
     */
    protected function createCronTabBackup()
    {
        $outputLines = [];
        exec('crontab -l 2>&1', $outputLines);
        if (!empty($outputLines[0]) && stripos($outputLines[0], 'no crontab') !== 0) {
            $fileName = $this->getCronTabBackupFileName();
            file_put_contents($fileName, implode("\n", $outputLines) . "\n");
        }
    }

    /**
     * Restore the crontab from backup.
     */
    protected function restoreCronTabBackup()
    {
        $fileName = $this->getCronTabBackupFileName();
        if (file_exists($fileName)) {
            exec('crontab ' . escapeshellarg($fileName));
            unlink($fileName);
        } else {
            exec('crontab -r 2>&1');
        }
    }

    // Tests :

    public function testSetGet()
    {
        $cronTab = new CronTab();

        $jobs = [
            [
                'min' => '*',
                'hour' => '*',
                'command' => 'ls --help',
            ],
            [
                'line' => '* * * * * ls --help',
            ],
        ];
        $cronTab->setJobs($jobs);
        $this->assertEquals($jobs, $cronTab->getJobs(), 'Unable to setup jobs!');
    }

    /**
     * @depends testSetGet
     */
    public function testGetLines()
    {
        $cronTab = new CronTab();

        $jobs = [
            [
                'command' => 'command/line/1',
            ],
            [
                'command' => 'command/line/2',
            ],
        ];
        $cronTab->setJobs($jobs);

        $lines = $cronTab->getLines();
        $this->assertNotEmpty($lines, 'Unable to get lines!');

        foreach ($lines as $number => $line) {
            $this->assertContains($jobs[$number]['command'], $line, 'Wrong line composed!');
        }
    }

    /**
     * @depends testGetLines
     */
    public function testSaveToFile()
    {
        $cronTab = new CronTab();

        $jobs = [
            [
                'command' => 'command/line/1',
            ],
            [
                'command' => 'command/line/2',
            ],
        ];
        $cronTab->setJobs($jobs);

        $filename = $this->getTestFilePath() . DIRECTORY_SEPARATOR . 'testfile.tmp';

        $cronTab->saveToFile($filename);

        $this->assertFileExists($filename, 'Unable to save file!');

        $fileContent = file_get_contents($filename);
        foreach ($jobs as $job) {
            $this->assertContains($job['command'], $fileContent, 'Job is missing!');
        }
    }

    /**
     * @depends testSaveToFile
     */
    public function testApply()
    {
        $cronTab = new CronTab();

        $jobs = [
            [
                'min' => '0',
                'hour' => '0',
                'command' => 'pwd',
            ],
        ];
        $cronTab->setJobs($jobs);

        $cronTab->apply();

        $currentLines = $cronTab->getCurrentLines();
        $this->assertNotEmpty($currentLines, 'Unable to setup crontab.');

        $cronTabContent = implode("\n", $currentLines);
        foreach ($jobs as $job) {
            $this->assertContains($job['command'], $cronTabContent, 'Job not present!');
        }
    }

    /**
     * @depends testApply
     */
    public function testMerge()
    {
        $cronTab = new CronTab();

        $firstJob = [
            'min' => '0',
            'hour' => '0',
            'command' => 'pwd',
        ];
        $cronTab->setJobs([$firstJob]);
        $cronTab->apply();

        $beforeMergeCronJobCount = count($cronTab->getCurrentLines());

        $secondJob = [
            'min' => '0',
            'hour' => '0',
            'command' => 'ls',
        ];
        $cronTab->setJobs([$secondJob]);
        $cronTab->apply();

        $currentLines = $cronTab->getCurrentLines();
        $this->assertNotEmpty($currentLines, 'Unable to merge crontab.');

        $afterMergeCronJobCount = count($currentLines);
        $this->assertEquals($afterMergeCronJobCount, $beforeMergeCronJobCount + 1, 'Wrong cron jobs count!');

        $cronTabContent = implode("\n", $currentLines);
        $this->assertContains($firstJob['command'], $cronTabContent, 'First job not present!');
        $this->assertContains($secondJob['command'], $cronTabContent, 'Second job not present!');
    }

    /**
     * @depends testMerge
     */
    public function testApplyTwice()
    {
        $cronTab = new CronTab();
        $firstJob = [
            'min' => '0',
            'hour' => '0',
            'command' => 'pwd',
        ];
        $cronTab->setJobs([$firstJob]);

        $cronTab->apply();
        $beforeMergeCronJobCount = count($cronTab->getCurrentLines());

        $cronTab->apply();
        $afterMergeCronJobCount = count($cronTab->getCurrentLines());

        $this->assertEquals($afterMergeCronJobCount, $beforeMergeCronJobCount, 'Wrong cron jobs count!');
    }

    /**
     * @depends testApply
     */
    public function testRemoveAll()
    {
        $cronTab = new CronTab();

        $firstJob = [
            'min' => '0',
            'hour' => '0',
            'command' => 'pwd',
        ];
        $cronTab->setJobs([$firstJob]);
        $cronTab->apply();

        $cronTab->removeAll();

        $currentLines = $cronTab->getCurrentLines();
        $this->assertEmpty($currentLines, 'Unable to remove cron jobs!');
    }

    /**
     * @depends testApply
     */
    public function testRemove()
    {
        $cronTab = new CronTab();

        $firstJob = [
            'min' => '0',
            'hour' => '0',
            'command' => 'pwd',
        ];
        $secondJob = [
            'min' => '0',
            'hour' => '0',
            'command' => 'ls',
        ];
        $cronTab->setJobs([$firstJob, $secondJob]);
        $cronTab->apply();

        $cronTab->setJobs([$firstJob]);
        $cronTab->remove();

        $currentLines = $cronTab->getCurrentLines();
        $cronTabContent = implode("\n", $currentLines);

        $this->assertNotContains($firstJob['command'], $cronTabContent, 'Removed job present!');
        $this->assertContains($secondJob['command'], $cronTabContent, 'Remaining job not present!');
    }
} 