<?php

declare(strict_types=1);

namespace App\Extractor;

interface ExtractorInterface
{
    public function support(string $type): bool;

    public function extract(string $source): void;
}
