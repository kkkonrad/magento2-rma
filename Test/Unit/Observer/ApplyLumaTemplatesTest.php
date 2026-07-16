<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Test\Unit\Observer;

use Kkkonrad\Rma\Model\ThemeCompatibility;
use Kkkonrad\Rma\Observer\ApplyLumaTemplates;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\TestCase;

class ApplyLumaTemplatesTest extends TestCase
{
    public function testAppliesLumaTemplates(): void
    {
        $compatibility = $this->createMock(ThemeCompatibility::class);
        $compatibility->method('isHyva')->willReturn(false);
        $block = $this->createMock(Template::class);
        $block->expects($this->exactly(7))->method('setTemplate');
        $layout = $this->createMock(LayoutInterface::class);
        $layout->expects($this->once())
            ->method('unsetElement')
            ->with('kkkonrad_rma_guest_order_actions_hyva');
        $layout->expects($this->exactly(7))->method('getBlock')->willReturn($block);
        $observer = new Observer(['layout' => $layout]);

        (new ApplyLumaTemplates($compatibility))->execute($observer);
    }

    public function testLeavesHyvaTemplatesUnchanged(): void
    {
        $compatibility = $this->createMock(ThemeCompatibility::class);
        $compatibility->method('isHyva')->willReturn(true);
        $layout = $this->createMock(LayoutInterface::class);
        $layout->expects($this->once())
            ->method('unsetElement')
            ->with('kkkonrad_rma_guest_order_actions');
        $layout->expects($this->never())->method('getBlock');
        $observer = new Observer(['layout' => $layout]);

        (new ApplyLumaTemplates($compatibility))->execute($observer);
    }
}
