<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Magento\Framework\View\DesignInterface;

class ThemeCompatibility
{
    public function __construct(private readonly DesignInterface $design)
    {
    }

    public function isHyva(): bool
    {
        $theme = $this->design->getDesignTheme();
        while ($theme) {
            $path = strtolower((string)$theme->getThemePath());
            if ($path === 'hyva/default' || str_starts_with($path, 'hyva/')) {
                return true;
            }
            $theme = method_exists($theme, 'getParentTheme') ? $theme->getParentTheme() : null;
        }

        return false;
    }
}
