<?php

declare(strict_types=1);

namespace App\Extractor;

class Xspf extends AbstractExtractor
{
    public const TYPE = 'xspf';

    protected function extractStream(\stdClass $station, \stdClass $stream): void
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><playlist xmlns="http://xspf.org/ns/0/" version="1"></playlist>');
        $title = $xml->addChild('title', $station->name);
        $creator = $xml->addChild('creator');
        $tracklist = $xml->addChild('trackList');
        $track = $tracklist->addChild('track');

        $urls = is_array($stream->url) ? $stream->url : [$stream->url];

        foreach ($urls as $url) {
            $location = $track->addChild('location', $url);
        }

        $annotation = $track->addChild('annotation', "Stream Title: ".$station->name.\PHP_EOL."Stream Description: ".$station->description.\PHP_EOL."Stream Genre: ".$station->genre);
        $info = $track->addChild('info', $station->url);
        $content = $xml->asXML();

        $this->writeFile($content, $station->name, $stream->bitrate, $stream->type);
    }
}
