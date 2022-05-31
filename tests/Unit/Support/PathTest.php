<?php
namespace Support;

use App\Support\Path;
use PHPUnit\Framework\TestCase;
use Tests\Psr4\Concerns\PhpunitConcern;
use Tests\Psr4\Concerns\SystemConcern;

class PathTest extends TestCase
{
    use SystemConcern, PhpunitConcern;

    /**
     * @test
     */
    public function shouldGetEmptyPath()
    {
        // given
        $path = new Path([]);
        // when
        $asString = $path->toString();
        // then
        $this->assertSame("", $asString);
    }

    /**
     * @test
     */
    public function shouldIgnoreEmptyChildren()
    {
        // given
        $path = new Path(["string", "", "", "string"]);
        // when
        $asString = $path->toString();
        // then
        $this->assertSameWindows("string\string", $asString);
        $this->assertSameUnix("string/string", $asString);
    }

    /**
     * @test
     * @dataProvider fileNames
     */
    public function shouldGetSingleFile(string $filename)
    {
        // given
        $path = new Path([$filename]);
        // when
        $asString = $path->toString();
        // then
        $this->assertSame("file.txt", $asString);
    }

    public function fileNames(): array
    {
        return [["file.txt"], ["file.txt/"], ["file.txt\\"]];
    }

    /**
     * @test
     * @dataProvider pathPieces
     */
    public function shouldGetManyFiles(array $pathPieces)
    {
        // given
        $path = new Path($pathPieces);
        // when
        $asString = $path->toString();
        // then
        $this->assertSameWindows('first\second\third\file.txt', $asString);
        $this->assertSameUnix("first/second/third/file.txt", $asString);
    }

    public function pathPieces(): array
    {
        return [
            [["first", "second", "third", "file.txt"]],

            [["first", "/second", "/third", "/file.txt"]],
            [["first", "\second", '\third', '\file.txt']],

            [["first/", "second/", "third/", "file.txt"]],
            [["first\\", "second\\", "third\\", "file.txt"]],

            [["first/", "/second/", "/third/", "/file.txt"]],
            [["first\\", "\\second\\", '\\third\\', '\\file.txt']],
        ];
    }

    /**
     * @test
     * @dataProvider paths
     */
    public function shouldRepresentPath(string $stringPath)
    {
        // given
        $path = Path::of($stringPath);
        // when
        $asString = $path->toString();
        // then
        $this->assertSameWindows('one\two\three\file.txt', $asString);
        $this->assertSameUnix("one/two/three/file.txt", $asString);
    }

    public function paths(): array
    {
        return [['one\two\three\file.txt'], ["one/two/three/file.txt"]];
    }

    /**
     * @test
     * @dataProvider children
     */
    public function shouldAppendPath(Path $path, string $appendant)
    {
        // when
        $childPath = $path->append($appendant);
        // then
        $this->assertSameWindows('uno\dos\tres', $childPath->toString());
        $this->assertSameUnix("uno/dos/tres", $childPath->toString());
    }

    public function children(): array
    {
        return [
            [Path::of("uno/dos"), "tres"],
            [Path::of("uno/dos"), '\tres'],
            [Path::of("uno/dos"), "/tres"],
            [Path::of("uno/dos"), "tres/"],
            [Path::of("uno/dos"), "tres\\"],

            [Path::of("uno/dos/"), "tres"],
            [Path::of("uno/dos/"), '\tres'],
            [Path::of("uno/dos/"), "/tres"],

            [Path::of("uno/dos\\"), "tres"],
            [Path::of("uno/dos\\"), '\tres'],
            [Path::of("uno/dos\\"), "/tres"],
        ];
    }

    /**
     * @test
     */
    public function shouldAcceptPathWithDriveOnWindows()
    {
        if ($this->isUnix()) {
            $this->markTestUnnecessary("There are no drives on Unix");
        }
        // given
        $path = Path::of("C:\directory");
        // when
        $child = $path->append("file.txt");
        // then
        $this->assertSame('C:\directory\file.txt', $child->toString());
    }

    /**
     * @test
     */
    public function shouldRemainAbsolutePathOnUnix()
    {
        if (!$this->isUnix()) {
            $this->markTestUnnecessary("There are no leading separators on Windows");
        }
        // given
        $path = Path::of("/usr/bin");
        // when
        $child = $path->append("local");
        // then
        $this->assertSame("/usr/bin/local", $child->toString());
    }

    /**
     * @test
     */
    public function shouldBeImmutable()
    {
        // given
        $path = Path::of("one/two/three");
        // when
        $this->assertSame($path->toString(), $path->toString());
    }
}
