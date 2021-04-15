<?php

declare(strict_types=1);

namespace App\Extractor;

class Xspf extends AbstractExtractor
{
    public const TYPE = 'xspf';

    protected function extractStream(\stdClass $station, \stdClass $stream): void
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><playlist xmlns="http://xspf.org/ns/0/" version="1"></playlist>');
        $xml->addChild('title', $station->name);
        $xml->addChild('creator');
        $tracklist = $xml->addChild('trackList');
        $track = $tracklist->addChild('track');

        $urls = is_array($stream->url) ? $stream->url : [$stream->url];

        foreach ($urls as $url) {
            $track->addChild('location', $url);
        }

        $track->addChild('annotation', 'Stream Title: '.$station->name.\PHP_EOL.'Stream Description: '.$station->description.\PHP_EOL.'Stream Genre: '.$station->genre);
        $track->addChild('info', $station->url);

        $this->writeFile($xml->asXML(), $station->name, $stream->bitrate, $stream->type);
    }
}
