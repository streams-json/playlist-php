<?php

declare(strict_types=1);

namespace App\Extractor;

class M3u extends AbstractExtractor
{
    public const TYPE = 'm3u';

    protected function extractStream(\stdClass $station, \stdClass $stream, array $urls): void
    {
        $title = $station->name;

        if (isset($station->genre)) {
            $title .= ' - '.$station->genre;
        }

        $title .= ' - '.(isset($stream->bitrate) ? $stream->bitrate.'kbps ' : '').$stream->type;

        $content = '#EXTM3U'.\PHP_EOL;

        foreach ($urls as $url) {
            $content .= '#EXTINF:-1,'.$title.\PHP_EOL;
            $content .= $url.\PHP_EOL;
        }

        $this->writeFile($content, $station, $stream);
    }
}
