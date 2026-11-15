# playlist-php

Generates one `m3u` or `xspf` playlist per stream from a [streams.json](https://github.com/streams-json/streams.json) file.

- **In the browser**: https://streams-json.github.io/playlist-php/ downloads the playlists in a zip file, with nothing to install.
- **On the command line**: `index.php` writes them in `files/m3u/` or `files/xspf/`.

Supports the 0.5.0 format and files written for earlier versions (0.4.0 and below). When a stream has several servers, they are listed by `priority`.

## Web version

Open https://streams-json.github.io/playlist-php/, then:

1. Give it a streams.json file: its URL, a local file, or pasted content.
2. Pick `m3u`, `xspf` or both.
3. Click "Generate zip file". The zip holds one folder per format.

Everything runs in the browser: nothing is sent to a server.

A streams.json file can only be read from its URL if its website allows it with a CORS header (`Access-Control-Allow-Origin: *`). Otherwise download the file and use the file or paste option.

## Command line

### Install

```bash
> composer install
```

### Usage

```bash
> php index.php https://morow.com/streams.json m3u
> php index.php https://morow.com/streams.json xspf
```

## Development

The web version lives in `docs/` and is published with GitHub Pages from the `master` branch: merging a change to `docs/` updates the site. To try it locally:

```bash
> php -S localhost:8000 -t docs
```

`docs/playlist.js` follows the same rules as `src/Extractor`: change both together.

### Cs-fixer

```bash
> vendor/bin/php-cs-fixer fix
```
