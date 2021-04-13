<?php

declare(strict_types=1);

namespace App;

class Output
{
    public static function write(string $message = null, bool $newLine = false): void
    {
        if ($newLine) {
            $message .= \PHP_EOL;
        }

        fwrite(STDOUT, $message);
    }

    public static function writeLn(string $message = null): void
    {
        self::write($message, true);
    }
}
