<?php
namespace App\View;

use App\Exceptions\InvalidConfigException;
use App\System\Application;
use App\View\Blocks\Block;

class BlockManager
{
    /** @var Application */
    private $app;

    private $classes = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param string $className
     * @throws InvalidConfigException
     */
    public function register($className)
    {
        $blockId = $className::BLOCK_ID;

        if ($this->exists($blockId)) {
            throw new InvalidConfigException(
                "There is a block with such an id [$blockId] already."
            );
        }

        $this->classes[$blockId] = $className;
    }

    /**
     * @param string $blockId
     * @return bool
     */
    public function exists($blockId)
    {
        return isset($this->classes[$blockId]);
    }

    /**
     * @param string $blockId
     * @return Block|null
     */
    public function get($blockId)
    {
        return $this->exists($blockId) ? $this->app->make($this->classes[$blockId]) : null;
    }
}
