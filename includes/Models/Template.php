<?php
namespace App\Models;

use DateTime;

class Template
{
    private int $id;
    private string $name;
    private ?string $theme;
    private ?string $lang;
    private string $content;
    private DateTime $createdAt;
    private DateTime $updatedAt;

    public function __construct(
        $id,
        $name,
        $theme,
        $lang,
        $content,
        DateTime $createdAt,
        DateTime $updatedAt
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->theme = $theme;
        $this->lang = $lang;
        $this->content = $content;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function getLang(): ?string
    {
        return $this->lang;
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
