<?php

namespace OleTrenner\HowMany\Tests;

use OleTrenner\HowMany\PathUtils;
use PHPUnit\Framework\TestCase;

class PathUtilsTest extends TestCase {
    public function testIsBelowDir()
    {
        $base = "/var/www";
        $this->assertTrue(PathUtils::isBelowDir($base, "/var/www/some/nested/path"));
        $this->assertFalse(PathUtils::isBelowDir($base, "/etc/passwd"));
    }

    public function testHasExt()
    {
        $this->assertTrue(PathUtils::hasExt('someFile.PdF', 'pdf'));
        $this->assertFalse(PathUtils::hasExt('someFile.jpg', 'pdf'));
        $this->expectException(\Exception::class);
        PathUtils::hasExt('someFile.PdF', '.jpg');
    }
}