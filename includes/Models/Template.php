<?php
namespace App\Models;

use DateTime;

class Template
{
    private int $id;
    private string $theme;
    private string $name;
    private string $content;
    private DateTime $createdAt;
    private DateTime $updatedAt;
    // TODO Add language

    public function __construct(
        $id,
        $theme,
        $name,
        $content,
        DateTime $createdAt,
        DateTime $updatedAt
    ) {
        $this->id = $id;
        $this->theme = $theme;
        $this->name = $name;
        $this->content = $content;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }
}
