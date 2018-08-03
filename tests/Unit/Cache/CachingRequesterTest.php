<?php
namespace Tests\Unit\Cache;

use App\Cache\CacheEntity;
use App\Cache\CachingRequester;
use Mockery;
use Psr\SimpleCache\CacheInterface;
use Tests\Psr4\TestCases\TestCase;

class CachingRequesterTest extends TestCase
{
    /** @test */
    public function returns_data_from_cache_if_not_outdated()
    {
        // given
        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('get')
            ->withArgs(['test'])
            ->andReturn(new CacheEntity('value'))
            ->once();

        $requester = new CachingRequester($cache);

        // when
        $response = $requester->load('test', 120, function () {
            return 'foobar';
        });

        // then
        $this->assertEquals('value', $response);
    }

    /** @test */
    public function calls_callback_and_store_result_in_cache_when_no_data_in_cache()
    {
        // given
        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('get')->withArgs(['test'])->andReturnNull()->once();
        $cache->shouldReceive('set')->withArgs(['test', 'foobar', Mockery::any()])->once();

        $requester = new CachingRequester($cache);

        // when
        $response = $requester->load('test', 120, function () {
            return 'foobar';
        });

        // then
        $this->assertEquals('foobar', $response);
    }

    /** @test */
    public function calls_callback_and_store_result_in_cache_when_data_in_cache_is_outdated()
    {
        // given
        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('get')
            ->withArgs(['test'])
            ->andReturn(new CacheEntity('value', time() - 2 * 60 * 60))
            ->once();

        $cache->shouldReceive('set')
            ->withArgs(['test', 'foobar', Mockery::any()])
            ->once();

        $requester = new CachingRequester($cache);

        // when
        $response = $requester->load('test', 100, function () {
            return 'foobar';
        });

        // then
        $this->assertEquals('foobar', $response);
    }

    /** @test */
    public function returns_from_cache_even_though_it_is_outdated_if_callback_failed()
    {
        // given
        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('get')
            ->withArgs(['test'])
            ->andReturn(new CacheEntity('value', time() - 2 * 60 * 60))
            ->once();

        $requester = new CachingRequester($cache);

        // when
        $response = $requester->load('test', 100, function () {
            return null;
        });

        // then
        $this->assertEquals('value', $response);
    }
}
