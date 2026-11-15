<?php

declare(strict_types=1);

namespace App\Extractor;

class Xspf extends AbstractExtractor
{
    public const TYPE = 'xspf';

    protected function extractStream(\stdClass $station, \stdClass $stream, array $urls): void
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><playlist xmlns="http://xspf.org/ns/0/" version="1"></playlist>');
        $xml->addChild('title', $this->escape($station->name));
        $xml->addChild('creator');
        $tracklist = $xml->addChild('trackList');
        $track = $tracklist->addChild('track');

        foreach ($urls as $url) {
            $track->addChild('location', $this->escape($url));
        }

        $annotation = ['Stream Title: '.$station->name];

        if (isset($station->description)) {
            $annotation[] = 'Stream Description: '.$station->description;
        }

        if (isset($station->genre)) {
            $annotation[] = 'Stream Genre: '.$station->genre;
        }

        $track->addChild('annotation', $this->escape(implode(\PHP_EOL, $annotation)));

        if (isset($station->url)) {
            $track->addChild('info', $this->escape($station->url));
        }

        $this->writeFile($xml->asXML(), $station, $stream);
    }

    /**
     * SimpleXMLElement::addChild() does not escape "&", common in stream urls.
     */
    private function escape(string $value): string
    {
        return htmlspecialchars($value, \ENT_XML1 | \ENT_QUOTES, 'UTF-8');
    }
}
