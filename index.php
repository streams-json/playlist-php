<?php

use App\Playlist;

require __DIR__.'/vendor/autoload.php';

[, $url, $type] = $argv;

$playlist = new Playlist($url, $type);
$playlist->extract();
