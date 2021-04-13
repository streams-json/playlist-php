<?php

declare(strict_types=1);

namespace App;

use App\Extractor\ExtractorInterface;
use App\Extractor\M3u;
use App\Extractor\Xspf;

class Playlist
{
    private \ArrayIterator $extractors;

    public function __construct(
        private string $source,
        private string $type,
    ) {
        $this->extractors = new \ArrayIterator([
           new M3u(),
           new Xspf(),
        ]);
    }

    public function extract(): void
    {
        /** @var ExtractorInterface $extractor */
        foreach ($this->extractors as $extractor) {
            if ($extractor->support($this->type)) {
                $extractor->extract($this->source);

                return;
            }
        }

        Output::writeLn('No extractor was able to handle the request.');
    }
}
