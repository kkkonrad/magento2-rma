<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Block;

use Kkkonrad\Rma\Model\Config;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class CustomHtml extends Template
{
    public function __construct(
        Context $context,
        private readonly Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getCustomCss(): string
    {
        return $this->config->getCustomCss();
    }

    public function getCustomJs(): string
    {
        return $this->config->getCustomJs();
    }
}
