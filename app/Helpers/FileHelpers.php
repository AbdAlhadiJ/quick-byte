<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Class FileHelpers
 *
 * A collection of file‐related helper functions.
 */
class FileHelpers
{

    public static function createFile(string  $prefix,
                                      string  $extension,
                                      ?string $subdir,
                                      string  $baseDir,
                                      bool    $useLocalStorageInstance = false
    ): string
    {
        if ($subdir) {
            $baseDir .= '/' . trim($subdir, '/');
        }

        Storage::disk('local')->makeDirectory($baseDir);

        $filename = $prefix . Str::random(10) . '.' . ltrim($extension, '.');

        $path = $baseDir . '/' . $filename;

        return $useLocalStorageInstance
            ? Storage::disk('local')->path($path)
            : $path;
    }

    /**
     * Creates a unique temporary file path.
     *
     * @param string $prefix
     * @param string $extension
     * @param string|null $subdir
     * @param bool $useStorageInstance
     * @return string
     */
    public static function createTempFilePath(
        string  $prefix = 'tmp_',
        string  $extension = 'tmp',
        ?string $subdir = null,
        bool    $useStorageInstance = false,
    ): string
    {

        return self::createFile(
            prefix: $prefix,
            extension: $extension,
            subdir: $subdir,
            baseDir: 'temp',
            useLocalStorageInstance: $useStorageInstance);

    }

    /**
     * Creates a unique temporary file path.
     *
     * @param string $prefix
     * @param string $extension
     * @param string|null $subdir
     * @param bool $useStorageInstance
     * @return string
     */
    public static function createScriptAssetFilePath(
        string  $prefix,
        string  $extension,
        ?string $subdir = 'assets',
        bool    $useStorageInstance = false,
    ): string
    {
        return self::createFile(
            prefix: $prefix,
            extension: $extension,
            subdir: $subdir,
            baseDir: 'scripts',
            useLocalStorageInstance: $useStorageInstance);

    }
}
