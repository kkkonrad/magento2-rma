<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Test\Unit\Model;

use Kkkonrad\Rma\Model\ThemeCompatibility;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Framework\View\DesignInterface;
use PHPUnit\Framework\TestCase;

class ThemeCompatibilityTest extends TestCase
{
    public function testDetectsHyvaTheme(): void
    {
        $theme = $this->createMock(ThemeInterface::class);
        $theme->method('getThemePath')->willReturn('Hyva/default');
        $design = $this->createMock(DesignInterface::class);
        $design->method('getDesignTheme')->willReturn($theme);

        self::assertTrue((new ThemeCompatibility($design))->isHyva());
    }

    public function testDetectsHyvaParentTheme(): void
    {
        $parent = $this->createMock(ThemeInterface::class);
        $parent->method('getThemePath')->willReturn('Hyva/default');
        $child = $this->createMock(ThemeInterface::class);
        $child->method('getThemePath')->willReturn('Vendor/custom');
        $child->method('getParentTheme')->willReturn($parent);
        $design = $this->createMock(DesignInterface::class);
        $design->method('getDesignTheme')->willReturn($child);

        self::assertTrue((new ThemeCompatibility($design))->isHyva());
    }

    public function testRejectsLumaTheme(): void
    {
        $blank = $this->createMock(ThemeInterface::class);
        $blank->method('getThemePath')->willReturn('Magento/blank');
        $luma = $this->createMock(ThemeInterface::class);
        $luma->method('getThemePath')->willReturn('Magento/luma');
        $luma->method('getParentTheme')->willReturn($blank);
        $design = $this->createMock(DesignInterface::class);
        $design->method('getDesignTheme')->willReturn($luma);

        self::assertFalse((new ThemeCompatibility($design))->isHyva());
    }
}
