<?php

namespace Tests\Unit\Infrastructure\Logging;

use App\Infrastructure\Logging\SizeLimitedFileHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class SizeLimitedFileHandlerTest extends TestCase
{
    private string $directory;
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $directory = sys_get_temp_dir() . '/tabletkovbot-log-' . uniqid('', true);
        mkdir($directory, 0777, true);
        $this->directory = $directory;
        $this->path = $directory . '/app.log';
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->directory);

        parent::tearDown();
    }

    public function testKeepsFileWhileSizeIsBelowLimit(): void
    {
        $logger = $this->makeLogger(10_000, 3);

        $logger->info(str_repeat('a', 100));
        $logger->info(str_repeat('b', 100));

        $this->assertFileExists($this->path);
        $this->assertFileDoesNotExist($this->path . '.1');
    }

    public function testRotatesFileWhenSizeLimitIsReached(): void
    {
        $logger = $this->makeLogger(1, 3);

        $logger->info(str_repeat('a', 200));
        $logger->info(str_repeat('b', 200));

        $this->assertFileExists($this->path);
        $this->assertFileExists($this->path . '.1');
        $this->assertFileDoesNotExist($this->path . '.2');
        $this->assertStringContainsString('b', (string) file_get_contents($this->path));
    }

    public function testKeepsNoMoreThanConfiguredNumberOfBackups(): void
    {
        $logger = $this->makeLogger(1, 2);

        for ($index = 0; $index < 5; $index++) {
            $logger->info(str_repeat('x', 200));
        }

        $this->assertFileExists($this->path);
        $this->assertFileExists($this->path . '.1');
        $this->assertFileExists($this->path . '.2');
        $this->assertFileDoesNotExist($this->path . '.3');
    }

    public function testDoesNotRotateWhenLimitIsZero(): void
    {
        $logger = $this->makeLogger(0, 3);

        for ($index = 0; $index < 20; $index++) {
            $logger->info(str_repeat('y', 200));
        }

        $this->assertFileExists($this->path);
        $this->assertFileDoesNotExist($this->path . '.1');
    }

    private function makeLogger(int $maxBytes, int $maxBackups): Logger
    {
        $logger = new Logger('test');
        $logger->pushHandler(new SizeLimitedFileHandler($this->path, $maxBytes, $maxBackups, Level::Debug));

        return $logger;
    }
}
