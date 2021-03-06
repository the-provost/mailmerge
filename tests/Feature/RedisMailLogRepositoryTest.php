<?php

namespace Mailmerge\Tests\Feature;

use Illuminate\Support\Facades\Redis;
use MailMerge\MailMergeServiceProvider;
use MailMerge\Repositories\MailLogsRepository;
use MailMerge\Repositories\RedisMailLogsRepository;
use Orchestra\Testbench\TestCase;

class RedisMailLogRepositoryTest extends TestCase
{
    /** @var MailLogsRepository */
    protected $logsRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logsRepository = $this->app->make(MailLogsRepository::class);
    }

    protected function tearDown(): void
    {
        Redis::flushall();
        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [MailMergeServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.redis.client', 'phpredis');
        $app['config']->set('database.redis.default', [
            'host'   => '127.0.0.1',
            'port' => '6379',
            'database' => '1',
        ]);
    }

    /** @test */
    public function it_instantiable()
    {
        $this->assertInstanceOf(RedisMailLogsRepository::class, $this->logsRepository);
    }

    /** @test */
    public function it_saves_and_get_logs()
    {
        $this->logsRepository->saveLogs('{"data": "loga1"}');

        $logs = $this->logsRepository->getLogs(0, -1);

        $this->assertCount(1, $logs);
        $this->assertSame($logs, ['{"data": "loga1"}']);
    }

    /** @test */
    public function it_saves_multiple_logs()
    {
        $this->logsRepository->saveLogs('{"data": "loga1"}');
        $this->logsRepository->saveLogs('{"data": "loga2"}');

        $logs = $this->logsRepository->getLogs(0, -1);

        $this->assertCount(2, $logs);
        $this->assertSame($logs, ['{"data": "loga2"}', '{"data": "loga1"}']);
    }

    /** @test */
    public function it_placed_latest_logs_first()
    {
        $this->logsRepository->saveLogs('log1');
        $this->logsRepository->saveLogs('log2');
        $this->logsRepository->saveLogs('log3');

        $logs = $this->logsRepository->getLogs(0, -1);

        $this->assertCount(3, $logs);
        $this->assertSame($logs, ['log3','log2', 'log1']);
    }

    /** @test */
    public function it_deletes_old_logs()
    {
        $this->logsRepository->saveLogs('log1');
        $this->logsRepository->saveLogs('log2');
        $this->logsRepository->saveLogs('log3');
        $this->logsRepository->saveLogs('log4');
        $this->logsRepository->saveLogs('log5');

        $logs = $this->logsRepository->getLogs(0, -1);

        $this->assertCount(5, $logs);
        $this->assertSame($logs, ['log5','log4','log3','log2', 'log1']);

        $this->logsRepository->deleteLogs(3);

        $logs = $this->logsRepository->getLogs(0, -1);

        $this->assertCount(3, $logs);
        $this->assertSame($logs, ['log5','log4','log3']);
    }
}