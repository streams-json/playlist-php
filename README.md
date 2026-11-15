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

## Cs-fixer

```bash
> vendor/bin/php-cs-fixer fix
```
