<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Guest;

use Kkkonrad\Rma\Model\Config;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;

class Index implements HttpGetActionInterface
{
    public function __construct(
        private readonly PageFactory $resultPageFactory,
        private readonly RedirectFactory $redirectFactory,
        private readonly Config $config
    ) {
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        if (!$this->config->allowGuestRma()) {
            return $this->redirectFactory->create()->setPath('/');
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Guest Return Request (RMA)'));

        return $resultPage;
    }
}
