<?php
/**
 * CVH Africa — TEMPORARY stand-in for PHP's "fileinfo" extension.
 *
 * The live server's PHP 8.3 is missing fileinfo, and Laravel's file storage creates a `finfo` object on start-up
 * ("Class finfo not found"). This file defines a minimal replacement ONLY when the real extension is absent,
 * detecting MIME types from the first bytes of a file (magic numbers) and falling back to the file extension.
 * Once the host enables fileinfo (ea-php83-php-fileinfo), the real extension is used and this file does nothing.
 * Loaded from bootstrap/app.php.
 */

// Declarations live inside this block on purpose: top-level classes are bound at compile time, so a plain
// early return would not stop them being declared (and the guard would see our own class).
if (!extension_loaded('fileinfo') && !class_exists('finfo', false)) {

    foreach (['FILEINFO_NONE' => 0, 'FILEINFO_SYMLINK' => 2, 'FILEINFO_MIME_TYPE' => 16, 'FILEINFO_MIME_ENCODING' => 1024,
                 'FILEINFO_MIME' => 1040, 'FILEINFO_DEVICES' => 8, 'FILEINFO_CONTINUE' => 32, 'FILEINFO_PRESERVE_ATIME' => 128,
                 'FILEINFO_RAW' => 256, 'FILEINFO_EXTENSION' => 16777216] as $cvhConst => $cvhValue) {
        if (!defined($cvhConst)) {
            define($cvhConst, $cvhValue);
        }
    }

    final class CvhMimeSniffer
    {
        /** [offset, bytes, mime] — checked in order. */
        private const SIGNATURES = [
            [0, "\x89PNG\r\n\x1a\n", 'image/png'],
            [0, "\xFF\xD8\xFF", 'image/jpeg'],
            [0, 'GIF87a', 'image/gif'],
            [0, 'GIF89a', 'image/gif'],
            [0, '%PDF-', 'application/pdf'],
            [0, 'BM', 'image/bmp'],
            [0, "\x00\x00\x01\x00", 'image/x-icon'],
            [0, 'ID3', 'audio/mpeg'],
            [0, "\xFF\xFB", 'audio/mpeg'],
            [0, 'OggS', 'audio/ogg'],
            [0, 'fLaC', 'audio/flac'],
            [0, "\x1A\x45\xDF\xA3", 'video/webm'],
            [0, "\x1F\x8B", 'application/gzip'],
            [0, 'Rar!', 'application/x-rar-compressed'],
            [0, "7z\xBC\xAF\x27\x1C", 'application/x-7z-compressed'],
            [0, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1", 'application/x-ole-storage'],
            [0, 'wOFF', 'font/woff'],
            [0, 'wOF2', 'font/woff2'],
            [0, "\x00\x01\x00\x00", 'font/ttf'],
            [0, 'OTTO', 'font/otf'],
        ];

        public static function fromBuffer(string $data, ?string $filename = null): string
        {
            if ($data === '') {
                return 'application/x-empty';
            }

            foreach (self::SIGNATURES as [$offset, $bytes, $mime]) {
                if (substr($data, $offset, strlen($bytes)) === $bytes) {
                    return $mime;
                }
            }

            if (substr($data, 0, 4) === 'RIFF') {
                $kind = substr($data, 8, 4);
                if ($kind === 'WEBP') return 'image/webp';
                if ($kind === 'WAVE') return 'audio/wav';
                if ($kind === 'AVI ') return 'video/x-msvideo';
            }

            if (substr($data, 4, 4) === 'ftyp') {
                $brand = substr($data, 8, 4);
                if (in_array($brand, ['M4A ', 'M4B '], true)) return 'audio/mp4';
                if (in_array($brand, ['qt  '], true)) return 'video/quicktime';
                if (in_array($brand, ['heic', 'heix', 'mif1'], true)) return 'image/heic';
                return 'video/mp4';
            }

            if (substr($data, 0, 4) === "PK\x03\x04") {
                // ZIP container: Office/OpenDocument files are ZIPs; use the extension to tell them apart.
                $byExtension = self::fromExtension($filename);
                return ($byExtension and $byExtension !== 'application/octet-stream') ? $byExtension : 'application/zip';
            }

            $head = ltrim(substr($data, 0, 512));
            if (stripos($head, '<svg') !== false || (stripos($head, '<?xml') === 0 && stripos($data, '<svg') !== false)) {
                return 'image/svg+xml';
            }
            if (stripos($head, '<!doctype html') === 0 || stripos($head, '<html') === 0) {
                return 'text/html';
            }
            if (stripos($head, '<?xml') === 0) {
                return 'text/xml';
            }

            // Plain text if it has no NUL bytes and is valid UTF-8; otherwise trust the extension.
            $sample = substr($data, 0, 2048);
            if (strpos($sample, "\0") === false && mb_check_encoding($sample, 'UTF-8')) {
                // Match the real extension: JSON is recognised, other text (incl. CSV) is text/plain.
                $trimmed = trim($data);
                if ($trimmed !== '' && in_array($trimmed[0], ['{', '['], true) && json_decode($trimmed) !== null) {
                    return 'application/json';
                }
                return 'text/plain';
            }

            return self::fromExtension($filename) ?: 'application/octet-stream';
        }

        public static function fromFile(string $path): string
        {
            if (is_dir($path)) {
                return 'directory';
            }
            $handle = @fopen($path, 'rb');
            if (!$handle) {
                return self::fromExtension($path) ?: 'application/octet-stream';
            }
            $data = (string)fread($handle, 4096);
            fclose($handle);

            return self::fromBuffer($data, $path);
        }

        public static function fromExtension(?string $filename): ?string
        {
            $extension = strtolower(pathinfo((string)$filename, PATHINFO_EXTENSION));
            if ($extension === '') {
                return null;
            }
            if (class_exists(\Symfony\Component\Mime\MimeTypes::class)) {
                $types = (new \Symfony\Component\Mime\MimeTypes())->getMimeTypes($extension);
                if ($types) {
                    return $types[0];
                }
            }
            return null;
        }

        public static function format(string $mime, int $flags): string
        {
            if ($flags & FILEINFO_MIME_ENCODING && !($flags & FILEINFO_MIME_TYPE)) {
                return str_starts_with($mime, 'text/') ? 'us-ascii' : 'binary';
            }
            if (($flags & FILEINFO_MIME) === FILEINFO_MIME) {
                return $mime . '; charset=' . (str_starts_with($mime, 'text/') ? 'us-ascii' : 'binary');
            }
            return $mime;
        }
    }

    class finfo
    {
        private int $flags;

        public function __construct(int $flags = FILEINFO_NONE, ?string $magic_database = null)
        {
            $this->flags = $flags;
        }

        public function file(string $filename, int $flags = FILEINFO_NONE, $context = null): string|false
        {
            if (!is_readable($filename)) {
                return false;
            }
            return CvhMimeSniffer::format(CvhMimeSniffer::fromFile($filename), $flags ?: $this->flags);
        }

        public function buffer(string $string, int $flags = FILEINFO_NONE, $context = null): string|false
        {
            return CvhMimeSniffer::format(CvhMimeSniffer::fromBuffer($string), $flags ?: $this->flags);
        }

        public function set_flags(int $flags): bool
        {
            $this->flags = $flags;
            return true;
        }
    }

    if (!function_exists('finfo_open')) {
        function finfo_open(int $flags = FILEINFO_NONE, ?string $magic_database = null): finfo|false
        {
            return new finfo($flags, $magic_database);
        }
    }
    if (!function_exists('finfo_file')) {
        function finfo_file(finfo $finfo, string $filename, int $flags = FILEINFO_NONE, $context = null): string|false
        {
            return $finfo->file($filename, $flags, $context);
        }
    }
    if (!function_exists('finfo_buffer')) {
        function finfo_buffer(finfo $finfo, string $string, int $flags = FILEINFO_NONE, $context = null): string|false
        {
            return $finfo->buffer($string, $flags, $context);
        }
    }
    if (!function_exists('finfo_close')) {
        function finfo_close(finfo $finfo): bool
        {
            return true;
        }
    }
    if (!function_exists('finfo_set_flags')) {
        function finfo_set_flags(finfo $finfo, int $flags): bool
        {
            return $finfo->set_flags($flags);
        }
    }
    if (!function_exists('mime_content_type')) {
        function mime_content_type($filename): string|false
        {
            return is_readable((string)$filename) ? CvhMimeSniffer::fromFile((string)$filename) : false;
        }
    }
}
