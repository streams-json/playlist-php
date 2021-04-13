<?php

if (!file_exists('morow.json')) {
  $json = file_get_contents('http://www.morow.com/streams.json');
  file_put_contents('morow.json', $json);
}

$data = file_get_contents('morow.json');
$json = json_decode($data);

$version_required = '0.1';
if ($json->{'_streams.json_Version'} <= $version_required) {

  echo count($json->stations).' station(s)';
  echo "\n";

  foreach($json->stations as $station) {
    echo count($station->streams).' stream(s)';
    echo "\n";
    foreach ($station->streams as $stream) {
      $file_content = "#EXTM3U\n";
      $file_content.= "#EXTINF:-1,".$station->name." - ".$station->genre." - ".$stream->bitrate."kbps ".$stream->type."\n";
      if (is_array($stream->url)) {
        foreach ($stream->url as $url) {
          $file_content.= "$url\n";
        }
      } else {
        $file_content.= "$stream->url\n";
      }
      $output_filename = $station->name.'-'.$stream->bitrate.'-'.$stream->type.'.m3u';
      if (!file_exists($output_filename)) {
        file_put_contents($output_filename, $file_content);
      } else {
        echo "The destination file ".$output_filename." alread exists.\n";
      }
    }
  }
} else {
  echo "Version of streams.json is to low please upgrade to version: ".$version_required."\n";
}
