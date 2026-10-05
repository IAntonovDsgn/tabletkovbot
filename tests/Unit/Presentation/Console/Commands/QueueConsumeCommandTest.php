<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Console\Commands;

use App\Infrastructure\RabbitMq\QueueConsumerInterface;
use App\Presentation\Console\Commands\QueueConsumeCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class QueueConsumeCommandTest extends TestCase
{
    private MockObject $consumer;
    private CommandTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = $this->createMock(QueueConsumerInterface::class);
        $this->tester = new CommandTester(new QueueConsumeCommand($this->consumer));
    }

    public function testExecuteRunsTheConsumer(): void
    {
        $this->consumer->expects($this->once())->method('run');

        self::assertSame(0, $this->tester->execute([]));
    }

    public function testCommandNameIsStable(): void
    {
        self::assertSame('app:queue-consume', new QueueConsumeCommand($this->consumer)->getName());
    }
}
