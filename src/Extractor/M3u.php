<?php

declare(strict_types=1);

namespace App\Extractor;

class M3u extends AbstractExtractor
{
    public const TYPE = 'm3u';

    protected function extractStream(\stdClass $station, \stdClass $stream): void
    {
        $content = '#EXTM3U'.\PHP_EOL;
        $content .= sprintf(
            '#EXTINF:-1,%s - %s - %skbps %s',
            $station->name,
            $station->genre,
            $stream->bitrate,
            $stream->type,
        ).\PHP_EOL;

        $urls = is_array($stream->url) ? $stream->url : [$stream->url];

        foreach ($urls as $url) {
            $content .= $url.\PHP_EOL;
        }

        $this->writeFile($content, $station->name, $stream->bitrate, $stream->type);
    }
}
