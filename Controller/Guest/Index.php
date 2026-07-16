<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Guest;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;

class Index implements HttpGetActionInterface
{
    public function __construct(
        private readonly RedirectFactory $redirectFactory
    ) {
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        return $this->redirectFactory->create()->setPath('sales/guest/form');
    }
}
