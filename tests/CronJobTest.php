<?php

namespace yii2tech\tests\unit\crontab;

use yii2tech\crontab\CronJob;

/**
 * Test case for [[CronJob]].
 * @see CronJob
 */
class CronJobTest extends TestCase
{
    /**
     * Data provider for [[testValidate]].
     * @return array test data.
     */
    public function dataProviderValidate()
    {
        return [
            [
                [
                    'min' => '*',
                    'hour' => '*',
                    'day' => '*',
                    'month' => '*',
                    'weekDay' => '*',
                    'command' => 'ls',
                ],
                true
            ],
            [
                [
                    'min' => '',
                    'hour' => '',
                    'day' => '',
                    'month' => '',
                    'weekDay' => '',
                    'command' => '',
                ],
                false
            ],
            [
                [
                    'min' => '/2',
                    'hour' => '/2',
                    'day' => '/2',
                    'month' => '/2',
                    'weekDay' => '/2',
                    'command' => 'some',
                ],
                true
            ],
            [
                [
                    'min' => '0',
                    'hour' => '0',
                    'day' => '0',
                    'month' => '0',
                    'weekDay' => '0',
                    'command' => 'some',
                ],
                true
            ],
        ];
    }

    /**
     * @dataProvider dataProviderValidate
     *
     * @param array $attributes
     * @param boolean $isValid
     */
    public function testValidate(array $attributes, $isValid)
    {
        $cronJob = new CronJob();

        $cronJob->setAttributes($attributes);
        $this->assertEquals($isValid, $cronJob->validate());
    }

    public function testComposeLine()
    {
        $cronJob = new CronJob();
        $cronJob->min = '0';
        $cronJob->hour = '1';
        $cronJob->day = '2';
        $cronJob->month = '3';
        $cronJob->weekDay = '*';
        $cronJob->command = 'some';

        $line = $cronJob->getLine();
        $expectedLine = "$cronJob->min $cronJob->hour $cronJob->day $cronJob->month $cronJob->weekDay $cronJob->command";
        $this->assertEquals($expectedLine, $line, 'Wrong line composed!');
    }

    public function testParseLine()
    {
        $attributes = [
            'min' => '0',
            'hour' => '1',
            'day' => '2',
            'month' => '3',
            'weekDay' => '*',
            'year' => null,
            'command' => 'some',
        ];
        $line = "{$attributes['min']} {$attributes['hour']} {$attributes['day']} {$attributes['month']} {$attributes['weekDay']} {$attributes['command']}";

        $cronJob = new CronJob();
        $cronJob->setLine($line);

        $this->assertEquals($attributes, $cronJob->getAttributes(), 'Unable to parse line!');
    }
} 