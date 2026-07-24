<?php

namespace OleTrenner\HowMany;

class PathUtils {
    /**
     * Checks if $path is in or below $base.
     */
    public static function isBelowDir(string $base, string $path): bool
    {
        return $base && $path && str_starts_with($path, $base);
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