<?php

declare(strict_types=1);

namespace App\Extractor;

use App\Output;

abstract class AbstractExtractor implements ExtractorInterface
{
    public const FILES_PATH = __DIR__.'/../../files';

    private const REQUIRED_JSON_VERSION = '0.1';

    /**
     * Header holding the format version: "version" since 0.5.0, the others before.
     */
    private const VERSION_KEYS = ['version', '_streams.json_version_', '_streams.json_Version'];

    public function support(string $type): bool
    {
        return static::TYPE === $type;
    }

    public function extract(string $source): void
    {
        $content = @file_get_contents($source);

        if (false === $content) {
            Output::writeLn(sprintf('Could not open source "%s".', $source));

            return;
        }

        $json = json_decode($content);

        if (!$json instanceof \stdClass) {
            Output::writeLn(sprintf('Source "%s" is not a valid streams.json file [%s].', $source, json_last_error_msg()));

            return;
        }

        $version = $this->version($json);

        if (null === $version) {
            Output::writeLn(sprintf('Source "%s" has no streams.json version.', $source));

            return;
        }

        if (version_compare($version, self::REQUIRED_JSON_VERSION, '<')) {
            Output::writeLn(sprintf(
                'Version of streams.json is too low, please upgrade to version "%s" or higher.',
                self::REQUIRED_JSON_VERSION
            ));

            return;
        }

        $stations = $this->toList($json->stations ?? null);

        Output::writeLn(sprintf('%d station(s)', count($stations)));

        foreach ($stations as $station) {
            if (!$station instanceof \stdClass || !isset($station->name)) {
                Output::writeLn('Skipped a station without name.');

                continue;
            }

            $streams = $this->toList($station->streams ?? null);

            Output::writeLn(sprintf('%d stream(s)', count($streams)));
            Output::writeLn();

            foreach ($streams as $stream) {
                $urls = $stream instanceof \stdClass ? $this->urls($stream) : [];

                if ([] === $urls || !isset($stream->type)) {
                    Output::writeLn(sprintf('Skipped a stream of "%s" without type or url.', $station->name));

                    continue;
                }

                $this->extractStream($station, $stream, $urls);
            }
        }
    }

    /**
     * @param string[] $urls urls of the stream, ordered by server priority
     */
    abstract protected function extractStream(\stdClass $station, \stdClass $stream, array $urls): void;

    protected function writeFile(string $content, \stdClass $station, \stdClass $stream): void
    {
        $name = array_filter([
            (string) $station->name,
            isset($stream->bitrate) ? (string) $stream->bitrate : null,
            (string) $stream->type,
        ]);

        $filename = sprintf(
            '%s/%s/%s.%s',
            self::FILES_PATH,
            static::TYPE,
            // The name comes from a remote file: it must not be able to leave the directory.
            ltrim(str_replace(['/', '\\', "\0"], '-', implode('-', $name)), '.-'),
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

    private function version(\stdClass $json): ?string
    {
        foreach (self::VERSION_KEYS as $key) {
            if (isset($json->{$key}) && is_scalar($json->{$key})) {
                return (string) $json->{$key};
            }
        }

        return null;
    }

    /**
     * Since 0.5.0 lists are always arrays; before, a single item could be given directly.
     */
    private function toList(mixed $value): array
    {
        if (null === $value) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * @return string[]
     */
    private function urls(\stdClass $stream): array
    {
        $servers = array_filter(
            $this->toList($stream->server ?? null),
            static fn ($server) => $server instanceof \stdClass && isset($server->url)
        );

        // Servers without priority come last, in the order of the file.
        usort(
            $servers,
            static fn (\stdClass $a, \stdClass $b) => ($a->priority ?? \PHP_INT_MAX) <=> ($b->priority ?? \PHP_INT_MAX)
        );

        $urls = array_map(static fn (\stdClass $server) => $server->url, $servers);

        // Before 0.5.0 a stream could also list its urls directly.
        foreach ($this->toList($stream->url ?? null) as $url) {
            $urls[] = $url;
        }

        return array_values(array_unique(array_filter($urls, 'is_string')));
    }
}
