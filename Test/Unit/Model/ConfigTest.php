<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Test\Unit\Model;

use Kkkonrad\Rma\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testEmptyAllowedStatusesFallsBackToComplete(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')
            ->with(Config::XML_PATH_ALLOWED_ORDER_STATUSES, 'store', null)
            ->willReturn('');

        $this->assertSame(['complete'], (new Config($scopeConfig))->getAllowedOrderStatuses());
    }

    public function testConfiguredStatusesAreNormalized(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn(' complete, processing ,');

        $this->assertSame(['complete', 'processing'], (new Config($scopeConfig))->getAllowedOrderStatuses());
    }
}
