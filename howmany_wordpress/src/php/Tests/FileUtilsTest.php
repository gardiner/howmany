<?php

namespace OleTrenner\HowMany\Tests;

use OleTrenner\HowMany\FileUtils;
use PHPUnit\Framework\TestCase;

class FileUtilsTest extends TestCase {
    public function testIsBelowDir()
    {
        $base = "/var/www";
        $this->assertTrue(FileUtils::isBelowDir($base, "/var/www/some/nested/path"));
        $this->assertFalse(FileUtils::isBelowDir($base, "/etc/passwd"));
    }

    public function testHasExt()
    {
        $this->assertTrue(FileUtils::hasExt('someFile.PdF', 'pdf'));
        $this->assertFalse(FileUtils::hasExt('someFile.jpg', 'pdf'));
        $this->expectException(\Exception::class);
        FileUtils::hasExt('someFile.PdF', '.jpg');
    }
}