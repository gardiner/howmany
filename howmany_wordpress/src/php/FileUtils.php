<?php

namespace OleTrenner\HowMany;

class FileUtils {
    public static function isBelowDir(string $base, string $path): bool
    {
        return $base && $path && strncmp($path, $base . DIRECTORY_SEPARATOR, strlen($base) + 1) === 0;
    }

    public static function hasExt(string $path, string $ext): bool
    {
        //make sure $ext is only the file extension
        if (!preg_match("/^[a-z0-9]+$/i", $ext)) {
            throw new \Exception('$ext must only contain letters and numbers.');
        }
        $ext = strtolower($ext);
        return preg_match("/\." . $ext . "$/i", $path);
    }
}