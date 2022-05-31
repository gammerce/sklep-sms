<?php

namespace Tests\Unit\Support;

use App\Support\FileSystemContract;
use App\Support\MetaParser;
use App\Support\BasePath;
use Tests\Psr4\MemoryFileSystem;
use Tests\Psr4\TestCases\UnitTestCase;

class MetaParserTest extends UnitTestCase
{
    private MetaParser $parser;
    private FileSystemContract $fileSystem;
    private $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fileSystem = new MemoryFileSystem();
        $this->parser = new MetaParser($this->fileSystem);
        $this->path = BasePath::temporary()->to("meta.txt");
    }

    /** @test */
    public function shouldParseVersion_withLineFeed()
    {
        // given
        $this->fileSystem->put($this->path, "VERSION=1.1.1" . "\n" . "BUILD=test");
        // when
        $meta = $this->parser->parse($this->path);
        // then
        $expected = [
            "VERSION" => "1.1.1",
            "BUILD" => "test",
        ];
        $this->assertSame($expected, $meta);
    }

    /** @test */
    public function shouldParseVersion_withCarriageReturn()
    {
        // given
        $this->fileSystem->put($this->path, "VERSION=1.1.1" . "\r" . "BUILD=test");
        // when
        $meta = $this->parser->parse($this->path);
        // then
        $expected = [
            "VERSION" => "1.1.1",
            "BUILD" => "test",
        ];
        $this->assertSame($expected, $meta);
    }

    /** @test */
    public function shouldParseVersion_withWindowsConvention()
    {
        // given
        $this->fileSystem->put($this->path, "VERSION=1.1.1" . "\r\n" . "BUILD=test");
        // when
        $meta = $this->parser->parse($this->path);
        // then
        $expected = [
            "VERSION" => "1.1.1",
            "BUILD" => "test",
        ];
        $this->assertSame($expected, $meta);
    }
}
