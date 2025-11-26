<?php

namespace App\Helpers;

class Helper
{
    public static function setEnvValue($key, $value): void
    {
        $path = base_path('.env');

        if (!file_exists($path)) {
            throw new \Exception(".env file not found");
        }

        // Escape values with spaces
        $escapedValue = strpos($value, ' ') !== false ? "\"{$value}\"" : $value;

        if (env($key) !== null) {
            // Update existing key
            file_put_contents($path, preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$escapedValue}",
                file_get_contents($path)
            ));
        } else {
            file_put_contents($path, PHP_EOL."{$key}={$escapedValue}", FILE_APPEND);
        }
    }

}
