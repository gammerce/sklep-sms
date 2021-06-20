<?php
namespace App\Theme;

class TemplateService
{
    public function listEditable(): array
    {
        return ["styles", "shop/pages/contact", "shop/pages/regulations"];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isEditable($name): bool
    {
        return in_array($name, $this->listEditable(), true);
    }

    /**
     * @param string $name
     * @return string
     */
    public function resolveName($name): string
    {
        return str_replace("-", "/", $name);
    }
}
