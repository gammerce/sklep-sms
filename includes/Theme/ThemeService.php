<?php
namespace App\Theme;

class ThemeService
{
    public function getEditableTemplates(): array
    {
        return ["styles", "shop/pages/contact", "shop/pages/regulations"];
    }
}
