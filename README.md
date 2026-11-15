# playlist-php

Generates one `m3u` or `xspf` playlist per stream from a [streams.json](https://github.com/streams-json/streams.json) file, in `files/m3u/` or `files/xspf/`.

Supports the 0.5.0 format and files written for earlier versions (0.4.0 and below). When a stream has several servers, they are listed by `priority`.

## Install

```bash
> composer install
```

## Usage

```bash
> php index.php https://morow.com/streams.json m3u
> php index.php https://morow.com/streams.json xspf
```

## Web version

`docs/` holds a static page that does the same job in the browser: give it a streams.json file (URL, file or pasted content), pick `m3u`, `xspf` or both, and it downloads the playlists in a zip file. Nothing is sent to a server.

It is meant to be published with GitHub Pages (Settings → Pages → branch `master`, folder `/docs`), and can also be opened locally:

```bash
> php -S localhost:8000 -t docs
```

A streams.json file can only be read from its URL if its website allows it with a CORS header (`Access-Control-Allow-Origin: *`). Otherwise use the file or paste option.

`docs/playlist.js` follows the same rules as `src/Extractor`: change both together.

## Cs-fixer

```bash
> vendor/bin/php-cs-fixer fix
```
