<?php
namespace App\Support;

use EmptyIterator;
use Iterator;

/**
 * Currently there is no notion of "relative" or "absolute"
 * path as of {@see Path} object, because there is no functionality
 * of joining paths together. Method {@see Path::toString}
 * currently returns paths as absolute paths, but {@see Path}
 * itself can be thought of as both absolute and relative.
 * The distinction will have to be made when there are methods
 * in this class that allow joining paths - those methods
 * will have to decide whether to treat other {@see Path} as
 * relative or absolute.
 */
class Path
{
    /** @var string[] */
    private array $children;

    public function __construct(array $path)
    {
        $this->children = $path;
    }

    public static function of(string $path): Path
    {
        return new Path(\preg_split("#[\\\\/]#", $path));
    }

    public function append(string $child): Path
    {
        return new Path([...$this->children, $child]);
    }

    public function toString(string $pathSeparator): string
    {
        return \join($pathSeparator, \iterator_to_array($this->children()));
    }

    private function children(): Iterator
    {
        if (empty($this->children)) {
            return new EmptyIterator();
        }
        return $this->normalizedChildren();
    }

    private function normalizedChildren(): Iterator
    {
        [$root, $children] = $this->rootAndChildren();
        yield \rTrim($root, "/\\");
        foreach ($children as $child) {
            if ($child === "") {
                continue;
            }
            yield \trim($child, "/\\");
        }
    }

    private function rootAndChildren(): array
    {
        $root = \reset($this->children);
        return [$root, \array_slice($this->children, 1)];
    }
}
