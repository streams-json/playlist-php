<?php

declare(strict_types=1);

namespace App\Extractor;

use App\Output;

abstract class AbstractExtractor implements ExtractorInterface
{
    public const FILES_PATH = __DIR__.'/../../files';

    private const REQUIRED_JSON_VERSION = 0.1;

    public function support(string $type): bool
    {
        return static::TYPE === $type;
    }

    public function extract(string $source): void
    {
        try {
            $json = json_decode(@file_get_contents($source));
        } catch (\Throwable $exception) {
            Output::writeLn(sprintf('Could not open source "%s" [%s]', $source, $exception->getMessage()));

            return;
        }

        if ($json->{'_streams.json_Version'} < self::REQUIRED_JSON_VERSION) {
            echo sprintf(
                'Version of streams.json is too low, please upgrade to version "%s" or higher.',
                self::REQUIRED_JSON_VERSION
            );

            return;
        }

        Output::writeLn(sprintf('%d station(s)', count($json->stations)));

        foreach ($json->stations as $station) {
            Output::writeLn(sprintf('%d stream(s)', count($station->streams)));
            Output::writeLn();

            foreach ($station->streams as $stream) {
                $this->extractStream($station, $stream);
            }
        }
    }

    abstract protected function extractStream(\stdClass $station, \stdClass $stream): void;

    protected function writeFile(string $content, string $station, int $bitrate, string $type): void
    {
        $filename = sprintf(
            '%s/%s/%s-%d-%s.%s',
            self::FILES_PATH,
            static::TYPE,
            $station,
            $bitrate,
            $type,
            static::TYPE
        );

        if (file_exists($filename)) {
            Output::writeLn(sprintf('The destination file "%s" already exists.', $filename));

            return;
        }

        if (@file_put_contents($filename, $content)) {
            Output::writeLn(sprintf('File "%s" created.', $filename));

            return;
        }

        Output::writeLn(sprintf('Could not create file "%s".', $filename));
    }
}
