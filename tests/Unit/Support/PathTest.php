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
        // when, then
        $this->assertPathUnix("", $path);
    }

    /**
     * @test
     */
    public function shouldIgnoreEmptyChildren()
    {
        // given
        $path = new Path(["string", "", "", "string"]);
        // when, then
        $this->assertPathWindows("string\string", $path);
        $this->assertPathUnix("string/string", $path);
    }

    /**
     * @test
     * @dataProvider fileNames
     */
    public function shouldGetSingleFile(string $filename)
    {
        // given
        $path = new Path([$filename]);
        // when, then
        $this->assertPathWindows("file.txt", $path);
        $this->assertPathUnix("file.txt", $path);
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
        // when, then
        $this->assertPathWindows('first\second\third\file.txt', $path);
        $this->assertPathUnix("first/second/third/file.txt", $path);
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
        // when, then
        $this->assertPathWindows('one\two\three\file.txt', $path);
        $this->assertPathUnix("one/two/three/file.txt", $path);
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
        $this->assertPathWindows('uno\dos\tres', $childPath);
        $this->assertPathUnix("uno/dos/tres", $childPath);
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
    public function shouldAcceptPathWithDriveForWindows()
    {
        // given
        $path = Path::of("C:\directory");
        // when
        $child = $path->append("file.txt");
        // then
        $this->assertPathWindows('C:\directory\file.txt', $child);
        $this->assertPathUnix("C:/directory/file.txt", $child);
    }

    /**
     * @test
     */
    public function shouldRemainAbsolutePathOnUnix()
    {
        // given
        $path = Path::of("/usr/bin");
        // when
        $child = $path->append("local");
        // then
        $this->assertPathWindows("\usr\bin\local", $child);
        $this->assertPathUnix("/usr/bin/local", $child);
    }

    /**
     * @test
     */
    public function shouldBeImmutable()
    {
        // given
        $path = Path::of("one/two/three");
        // when
        $this->assertSame($path->toString("/"), $path->toString("/"));
        $this->assertSame($path->toString("\\"), $path->toString("\\"));
    }
}
