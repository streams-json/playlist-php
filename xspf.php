<?php

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
      $file_content = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
      $file_content.= '<playlist xmlns="http://xspf.org/ns/0/" version="1">'."\n";
      $file_content.= '  <title>'.$station->name.'</title>'."\n";
      $file_content.= '  <creator/>'."\n";
      $file_content.= '  <trackList>'."\n";
      $file_content.= '    <track>'."\n";
      if (is_array($stream->url)) {
        foreach ($stream->url as $url) {
          $file_content.= "      <location>".$url."</location>\n";
        }
      } else {
        $file_content.= "      <location>".$url."</location>\n";
      }
      $file_content.= "      <annotation>Stream Title: ".$station->name."\nStream Description: ".$station->description."\nStream Genre: ".$station->genre."</annotation>\n";
      $file_content.= "      <info>".$station->url."</info>\n";
      $file_content.= '    </track>'."\n";
      $file_content.= '  </trackList>'."\n";
      $file_content.= '</playlist>'."\n";
      $output_filename = $station->name.'-'.$stream->bitrate.'-'.$stream->type.'.xspf';
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
