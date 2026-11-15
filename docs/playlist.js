/*
 * streams.json playlist generator, browser version.
 *
 * Same rules as the PHP extractors in src/Extractor: keep both in sync.
 * Works in a browser (window.StreamsPlaylist) and in Node (require).
 */
(function (root, factory) {
  const api = factory();

  if (typeof module === 'object' && module.exports) {
    module.exports = api;
  } else {
    root.StreamsPlaylist = api;
  }
})(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  const REQUIRED_VERSION = '0.1';
  // "version" since 0.5.0, the others before.
  const VERSION_KEYS = ['version', '_streams.json_version_', '_streams.json_Version'];
  const FORMATS = ['m3u', 'xspf'];

  const isObject = (value) => value !== null && typeof value === 'object' && !Array.isArray(value);

  // Since 0.5.0 lists are always arrays; before, a single item could be given directly.
  const toList = (value) => (value === undefined || value === null ? [] : Array.isArray(value) ? value : [value]);

  function readVersion(json) {
    for (const key of VERSION_KEYS) {
      if (['string', 'number'].includes(typeof json[key])) {
        return String(json[key]);
      }
    }

    return null;
  }

  function compareVersions(a, b) {
    const left = a.split('.').map((part) => parseInt(part, 10) || 0);
    const right = b.split('.').map((part) => parseInt(part, 10) || 0);

    for (let i = 0; i < Math.max(left.length, right.length); i++) {
      const diff = (left[i] || 0) - (right[i] || 0);

      if (diff !== 0) {
        return diff;
      }
    }

    return 0;
  }

  // Urls of a stream, ordered by server priority. Servers without priority come last.
  function streamUrls(stream) {
    const servers = toList(stream.server)
      .filter((server) => isObject(server) && server.url !== undefined && server.url !== null)
      .map((server, index) => ({ server, index }))
      .sort((a, b) => {
        const left = typeof a.server.priority === 'number' ? a.server.priority : Infinity;
        const right = typeof b.server.priority === 'number' ? b.server.priority : Infinity;

        return left === right ? a.index - b.index : left < right ? -1 : 1;
      });

    const urls = servers.map(({ server }) => server.url);

    // Before 0.5.0 a stream could also list its urls directly.
    urls.push(...toList(stream.url));

    return [...new Set(urls.filter((url) => typeof url === 'string' && url !== ''))];
  }

  function m3u(station, stream, urls) {
    let title = String(station.name);

    if (station.genre !== undefined && station.genre !== null) {
      title += ' - ' + station.genre;
    }

    title += ' - ' + (stream.bitrate !== undefined && stream.bitrate !== null ? stream.bitrate + 'kbps ' : '') + stream.type;

    let content = '#EXTM3U\n';

    for (const url of urls) {
      content += '#EXTINF:-1,' + title + '\n' + url + '\n';
    }

    return content;
  }

  const escapeXml = (value) => String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

  function xspf(station, stream, urls) {
    const annotation = ['Stream Title: ' + station.name];

    if (station.description !== undefined && station.description !== null) {
      annotation.push('Stream Description: ' + station.description);
    }

    if (station.genre !== undefined && station.genre !== null) {
      annotation.push('Stream Genre: ' + station.genre);
    }

    let track = urls.map((url) => '<location>' + escapeXml(url) + '</location>').join('');
    track += '<annotation>' + escapeXml(annotation.join('\n')) + '</annotation>';

    if (station.url !== undefined && station.url !== null) {
      track += '<info>' + escapeXml(station.url) + '</info>';
    }

    return (
      '<?xml version="1.0" encoding="UTF-8"?>\n' +
      '<playlist xmlns="http://xspf.org/ns/0/" version="1">' +
      '<title>' + escapeXml(station.name) + '</title><creator/>' +
      '<trackList><track>' + track + '</track></trackList></playlist>\n'
    );
  }

  const WRITERS = { m3u, xspf };

  // The name comes from a remote file: it must stay a plain file name.
  function fileName(station, stream, format) {
    const parts = [String(station.name)];

    if (stream.bitrate !== undefined && stream.bitrate !== null) {
      parts.push(String(stream.bitrate));
    }

    parts.push(String(stream.type));

    const name = parts.join('-').replace(/[\/\\\0]/g, '-').replace(/^[.-]+/, '');

    return (name || 'playlist') + '.' + format;
  }

  /**
   * Builds the playlists of a parsed streams.json document.
   *
   * @param {object}   json    parsed streams.json
   * @param {string[]} formats any of "m3u", "xspf"
   * @returns {{version: string, stations: number, files: {path: string, content: string}[], messages: string[]}}
   */
  function generate(json, formats) {
    if (!isObject(json)) {
      throw new Error('This is not a valid streams.json file.');
    }

    const wanted = FORMATS.filter((format) => formats.includes(format));

    if (wanted.length === 0) {
      throw new Error('Choose at least one export format.');
    }

    const version = readVersion(json);

    if (version === null) {
      throw new Error('This file has no streams.json version.');
    }

    if (compareVersions(version, REQUIRED_VERSION) < 0) {
      throw new Error('Version of streams.json is too low, please upgrade to version "' + REQUIRED_VERSION + '" or higher.');
    }

    const stations = toList(json.stations);
    const files = [];
    const messages = [];
    const used = new Set();

    for (const station of stations) {
      if (!isObject(station) || station.name === undefined || station.name === null) {
        messages.push('Skipped a station without name.');
        continue;
      }

      for (const stream of toList(station.streams)) {
        const urls = isObject(stream) ? streamUrls(stream) : [];

        if (urls.length === 0 || stream.type === undefined || stream.type === null) {
          messages.push('Skipped a stream of "' + station.name + '" without type or url.');
          continue;
        }

        for (const format of wanted) {
          const path = format + '/' + fileName(station, stream, format);

          if (used.has(path)) {
            messages.push('Skipped "' + path + '": a playlist with this name already exists.');
            continue;
          }

          used.add(path);
          files.push({ path, content: WRITERS[format](station, stream, urls) });
        }
      }
    }

    return { version, stations: stations.length, files, messages };
  }

  let crcTable = null;

  function crc32(bytes) {
    if (crcTable === null) {
      crcTable = new Uint32Array(256);

      for (let n = 0; n < 256; n++) {
        let c = n;

        for (let k = 0; k < 8; k++) {
          c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
        }

        crcTable[n] = c >>> 0;
      }
    }

    let crc = 0xffffffff;

    for (let i = 0; i < bytes.length; i++) {
      crc = crcTable[(crc ^ bytes[i]) & 0xff] ^ (crc >>> 8);
    }

    return (crc ^ 0xffffffff) >>> 0;
  }

  /**
   * Packs text files in a zip archive, without compression.
   *
   * @param {{path: string, content: string}[]} files
   * @param {Date} [date] modification date of the entries
   * @returns {Uint8Array}
   */
  function zip(files, date) {
    const encoder = new TextEncoder();
    const when = date || new Date();
    const time = (when.getHours() << 11) | (when.getMinutes() << 5) | (when.getSeconds() >> 1);
    const day = ((Math.max(when.getFullYear(), 1980) - 1980) << 9) | ((when.getMonth() + 1) << 5) | when.getDate();

    const chunks = [];
    const directory = [];
    let offset = 0;

    for (const file of files) {
      const name = encoder.encode(file.path);
      const data = encoder.encode(file.content);
      const crc = crc32(data);

      const local = new DataView(new ArrayBuffer(30));
      local.setUint32(0, 0x04034b50, true);
      local.setUint16(4, 20, true); // version needed
      local.setUint16(6, 0x0800, true); // names are utf-8
      local.setUint16(8, 0, true); // stored
      local.setUint16(10, time, true);
      local.setUint16(12, day, true);
      local.setUint32(14, crc, true);
      local.setUint32(18, data.length, true);
      local.setUint32(22, data.length, true);
      local.setUint16(26, name.length, true);
      local.setUint16(28, 0, true);

      const central = new DataView(new ArrayBuffer(46));
      central.setUint32(0, 0x02014b50, true);
      central.setUint16(4, 20, true); // version made by
      central.setUint16(6, 20, true); // version needed
      central.setUint16(8, 0x0800, true);
      central.setUint16(10, 0, true);
      central.setUint16(12, time, true);
      central.setUint16(14, day, true);
      central.setUint32(16, crc, true);
      central.setUint32(20, data.length, true);
      central.setUint32(24, data.length, true);
      central.setUint16(28, name.length, true);
      central.setUint32(42, offset, true);

      chunks.push(new Uint8Array(local.buffer), name, data);
      directory.push(new Uint8Array(central.buffer), name);
      offset += 30 + name.length + data.length;
    }

    const directorySize = directory.reduce((size, chunk) => size + chunk.length, 0);

    const end = new DataView(new ArrayBuffer(22));
    end.setUint32(0, 0x06054b50, true);
    end.setUint16(8, files.length, true);
    end.setUint16(10, files.length, true);
    end.setUint32(12, directorySize, true);
    end.setUint32(16, offset, true);

    const all = [...chunks, ...directory, new Uint8Array(end.buffer)];
    const archive = new Uint8Array(offset + directorySize + 22);
    let position = 0;

    for (const chunk of all) {
      archive.set(chunk, position);
      position += chunk.length;
    }

    return archive;
  }

  return { FORMATS, generate, zip };
});
