<?php
namespace Tests\Unit\System;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

class EnvLoaderTest extends TestCase
{
    private string $envPath;
    private string $testKey;
    private string $testValue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testKey = "TEST_ENV_VAR_" . bin2hex(random_bytes(8));
        $this->testValue = "test_value_" . bin2hex(random_bytes(4));
        $this->envPath = sys_get_temp_dir() . "/env-loader-test-" . bin2hex(random_bytes(6));
        mkdir($this->envPath, 0777, true);
        file_put_contents($this->envPath . "/.env", $this->testKey . "=" . $this->testValue . "\n");
    }

    protected function tearDown(): void
    {
        putenv($this->testKey);
        @unlink($this->envPath . "/.env");
        @rmdir($this->envPath);
        parent::tearDown();
    }

    /** @test */
    public function unsafe_immutable_sets_env_vars_via_getenv()
    {
        $dotenv = Dotenv::createUnsafeImmutable($this->envPath);
        $dotenv->safeLoad();

        $this->assertSame($this->testValue, getenv($this->testKey));
    }

    /** @test */
    public function unsafe_immutable_sets_env_vars_via_server()
    {
        $dotenv = Dotenv::createUnsafeImmutable($this->envPath);
        $dotenv->safeLoad();

        $this->assertSame($this->testValue, $_SERVER[$this->testKey] ?? null);
    }

    /** @test */
    public function unsafe_immutable_sets_env_vars_via_env()
    {
        $dotenv = Dotenv::createUnsafeImmutable($this->envPath);
        $dotenv->safeLoad();

        $this->assertSame($this->testValue, $_ENV[$this->testKey] ?? null);
    }

    /** @test */
    public function immutable_does_not_set_env_vars_via_getenv()
    {
        $dotenv = Dotenv::createImmutable($this->envPath);
        $dotenv->safeLoad();

        $this->assertFalse(getenv($this->testKey));
    }

    /** @test */
    public function immutable_still_sets_env_vars_via_server()
    {
        $dotenv = Dotenv::createImmutable($this->envPath);
        $dotenv->safeLoad();

        $this->assertSame($this->testValue, $_SERVER[$this->testKey] ?? null);
    }

    /** @test */
    public function bootstrap_uses_unsafe_immutable()
    {
        $autoloadPath = __DIR__ . "/../../../bootstrap/autoload.php";
        $content = file_get_contents($autoloadPath);

        $this->assertStringContainsString(
            "createUnsafeImmutable",
            $content,
            "bootstrap/autoload.php should use createUnsafeImmutable"
        );
        $this->assertStringNotContainsString(
            "createImmutable",
            $content,
            "bootstrap/autoload.php should not use createImmutable"
        );
    }
}
