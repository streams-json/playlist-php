<?php

declare(strict_types=1);

namespace App\Extractor;

class Xspf extends AbstractExtractor
{
    public const TYPE = 'xspf';

    protected function extractStream(\stdClass $station, \stdClass $stream): void
    {
        $content = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $content.= '<playlist xmlns="http://xspf.org/ns/0/" version="1">'."\n";
        $content.= '  <title>'.$station->name.'</title>'."\n";
        $content.= '  <creator/>'."\n";
        $content.= '  <trackList>'."\n";
        $content.= '    <track>'."\n";

        $urls = is_array($stream->url) ? $stream->url : [$stream->url];

        foreach ($urls as $url) {
            $content .= "      <location>".$url."</location>\n";
        }

        $content.= "      <annotation>Stream Title: ".$station->name."\nStream Description: ".$station->description."\nStream Genre: ".$station->genre."</annotation>\n";
        $content.= "      <info>".$station->url."</info>\n";
        $content.= '    </track>'."\n";
        $content.= '  </trackList>'."\n";
        $content.= '</playlist>'."\n";

        $this->writeFile($content, $station->name, $stream->bitrate, $stream->type);
    }
}
