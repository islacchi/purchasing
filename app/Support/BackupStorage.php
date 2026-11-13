<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Owns the .sql dump files stored under the local disk's private/backups directory.
 * Filenames are server-generated and matched against a fixed pattern, so any
 * user-supplied filename is validated here before touching disk.
 */
class BackupStorage
{
    public const DIRECTORY = 'backups';

    public const KEEP = 15;

    public const FILENAME_PATTERN = '/^purchasing-\d{4}-\d{2}-\d{2}-\d{6}\.sql$/';

    /**
     * The backups directory the private disk mirrors (absolute path).
     */
    public static function directory(): string
    {
        return Storage::disk('local')->path(self::DIRECTORY);
    }

    /**
     * Server-generated filename for a fresh backup taken now.
     */
    public static function createFilename(): string
    {
        return 'purchasing-'.now()->format('Y-m-d-His').'.sql';
    }

    /**
     * Whether $file is a well-formed backup filename.
     */
    public static function isValidFilename(string $file): bool
    {
        return preg_match(self::FILENAME_PATTERN, $file) === 1;
    }

    /**
     * All backup filenames, oldest first.
     *
     * @return array<int, string>
     */
    public static function filenames(): array
    {
        $files = array_map(
            fn (string $path): string => basename($path),
            Storage::disk('local')->files(self::DIRECTORY),
        );

        $files = array_values(array_filter($files, fn (string $file): bool => self::isValidFilename($file)));
        sort($files);

        return $files;
    }

    /**
     * Backups as UI-friendly records, newest first.
     *
     * @return array<int, array{filename: string, path: string, size: int, created: string}>
     */
    public static function records(): array
    {
        return array_map(
            fn (string $file): array => [
                'filename' => $file,
                'path' => self::path($file),
                'size' => (int) filesize(self::path($file)),
                'created' => date('Y-m-d H:i:s', filemtime(self::path($file))),
            ],
            array_reverse(self::filenames()),
        );
    }

    /**
     * Absolute path of a backup file, validated against the filename pattern.
     */
    public static function path(string $file): string
    {
        if (! self::isValidFilename($file)) {
            throw new RuntimeException('Invalid backup filename.');
        }

        return Storage::disk('local')->path(self::DIRECTORY.'/'.$file);
    }

    /**
     * Delete every backup except the $keep most recent ones.
     */
    public static function prune(int $keep = self::KEEP): void
    {
        $files = self::filenames();
        $excess = array_slice($files, 0, max(0, count($files) - $keep));

        foreach ($excess as $file) {
            Storage::disk('local')->delete(self::DIRECTORY.'/'.$file);
        }
    }
}
