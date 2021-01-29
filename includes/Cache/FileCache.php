<?php
namespace App\Cache;

use App\Support\FileSystemContract;
use Exception;
use Psr\SimpleCache\CacheInterface;

class FileCache implements CacheInterface
{
    private FileSystemContract $files;
    private string $directory;

    public function __construct(FileSystemContract $files, $directory)
    {
        $this->files = $files;
        $this->directory = $directory;
    }

    public function get($key, $default = null)
    {
        $payload = $this->getPayload($key);

        if ($payload === null) {
            return $default;
        }

        return $payload["data"];
    }

    public function set($key, $value, $ttl = null)
    {
        $this->ensureCacheDirectoryExists($path = $this->path($key));

        $expiration = time() + $ttl;

        $this->files->put($path, $expiration . serialize(new CacheEntity($value)), true);
    }

    public function delete($key)
    {
        if ($this->files->exists($file = $this->path($key))) {
            return $this->files->delete($file);
        }

        return false;
    }

    public function clear()
    {
        throw new Exception("Not implemented");
    }

    public function getMultiple($keys, $default = null)
    {
        throw new Exception("Not implemented");
    }

    public function setMultiple($values, $ttl = null)
    {
        throw new Exception("Not implemented");
    }

    public function deleteMultiple($keys)
    {
        throw new Exception("Not implemented");
    }

    public function has($key)
    {
        throw new Exception("Not implemented");
    }

    /**
     * Create the file cache directory if necessary.
     *
     * @param  string $path
     * @return void
     */
    private function ensureCacheDirectoryExists($path)
    {
        if (!$this->files->exists(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    /**
     * Retrieve an item and expiry time from the cache by key.
     *
     * @param  string $key
     * @return array|null
     */
    private function getPayload($key)
    {
        $path = $this->path($key);

        // If the file doesn't exist, we obviously cannot return the cache so we will
        // just return null. Otherwise, we'll get the contents of the file and get
        // the expiration UNIX timestamps from the start of the file's contents.
        try {
            $expire = substr($contents = $this->files->get($path, true), 0, 10);
        } catch (Exception $e) {
            return null;
        }

        // If the current time is greater than expiration timestamps we will delete
        // the file and return null. This helps clean up the old files and keeps
        // this directory much cleaner for us as old files aren't hanging out.
        if (time() >= $expire) {
            $this->delete($key);
            return null;
        }

        $data = unserialize(substr($contents, 10));

        // Next, we'll extract the number of minutes that are remaining for a cache
        // so that we can properly retain the time for things like the increment
        // operation that may be performed on this cache on a later operation.
        $time = $expire - time();

        return compact("data", "time");
    }

    private function path($key)
    {
        $parts = array_slice(str_split($hash = sha1($key), 2), 0, 2);

        return $this->directory . "/" . implode("/", $parts) . "/" . $hash;
    }
}
