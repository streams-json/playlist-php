<?php

declare(strict_types=1);

namespace App\Extractor;

class Xspf extends AbstractExtractor
{
    public const TYPE = 'xspf';

    protected function extractStream(\stdClass $station, \stdClass $stream): void
    {
        $content = '<?xml version="1.0" encoding="UTF-8"?>'.\PHP_EOL;
        $content.= '<playlist xmlns="http://xspf.org/ns/0/" version="1">'.\PHP_EOL;
        $content.= '  <title>'.$station->name.'</title>'.\PHP_EOL;
        $content.= '  <creator/>'.\PHP_EOL;
        $content.= '  <trackList>'.\PHP_EOL;
        $content.= '    <track>'.\PHP_EOL;

        $urls = is_array($stream->url) ? $stream->url : [$stream->url];

        foreach ($urls as $url) {
            $content .= "      <location>".$url."</location>".\PHP_EOL;
        }

        $content.= "      <annotation>Stream Title: ".$station->name.\PHP_EOL."Stream Description: ".$station->description.\PHP_EOL."Stream Genre: ".$station->genre."</annotation>".\PHP_EOL;
        $content.= "      <info>".$station->url."</info>".\PHP_EOL;
        $content.= '    </track>'.\PHP_EOL;
        $content.= '  </trackList>'.\PHP_EOL;
        $content.= '</playlist>'.\PHP_EOL;

        $this->writeFile($content, $station->name, $stream->bitrate, $stream->type);
    }
}
