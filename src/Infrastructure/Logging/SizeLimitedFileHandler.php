<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\LogRecord;

final class SizeLimitedFileHandler extends StreamHandler
{
    public function __construct(
        string $path,
        private readonly int $maxBytes,
        private readonly int $maxBackups,
        Level $level = Level::Debug,
    ) {
        parent::__construct($path, $level, true, null, true);
    }

    protected function write(LogRecord $record): void
    {
        $this->rotateIfLimitReached();

        parent::write($record);
    }

    private function rotateIfLimitReached(): void
    {
        $path = $this->getUrl();

        if ($path === null || $path === '' || $this->maxBytes <= 0 || $this->maxBackups <= 0) {
            return;
        }

        clearstatcache(true, $path);

        if (! is_file($path)) {
            return;
        }

        $size = filesize($path);

        if ($size === false || $size < $this->maxBytes) {
            return;
        }

        $this->close();

        $oldest = $path . '.' . $this->maxBackups;

        if (is_file($oldest)) {
            unlink($oldest);
        }

        for ($index = $this->maxBackups - 1; $index >= 1; $index--) {
            $source = $path . '.' . $index;

            if (is_file($source)) {
                rename($source, $path . '.' . ($index + 1));
            }
        }

        rename($path, $path . '.1');

        if (! is_file($path)) {
            touch($path);
        }
    }
}
