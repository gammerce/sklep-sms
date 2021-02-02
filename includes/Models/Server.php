<?php
namespace App\Models;

use App\Server\Platform;

class Server
{
    private int $id;
    private string $name;
    private string $ip;
    private string $port;
    private ?string $type;
    private ?int $smsPlatformId;

    /** @var int[] */
    private array $transferPlatformIds;

    private ?string $version;
    private ?string $lastActiveAt;
    private ?string $token;

    public function __construct(
        $id,
        $name,
        $ip,
        $port,
        $smsPlatformId,
        array $transferPlatformIds,
        $type,
        $version,
        $lastActiveAt,
        $token
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->ip = $ip;
        $this->port = $port;
        $this->smsPlatformId = $smsPlatformId;
        $this->transferPlatformIds = $transferPlatformIds;
        $this->type = $type;
        $this->version = $version;
        $this->lastActiveAt = $lastActiveAt;
        $this->token = $token;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getPort(): string
    {
        return $this->port;
    }

    public function getType(): ?Platform
    {
        return as_server_type($this->type);
    }

    public function getSmsPlatformId(): ?int
    {
        return $this->smsPlatformId;
    }

    /**
     * @return int[]
     */
    public function getTransferPlatformIds(): array
    {
        return $this->transferPlatformIds;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function getLastActiveAt(): ?string
    {
        return $this->lastActiveAt;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }
}
