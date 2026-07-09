<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Customer;

use Kkkonrad\Rma\Block\Customer\Rma\ListRma;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\PageFactory;

class Index implements HttpGetActionInterface
{
    public function __construct(
        private readonly PageFactory $resultPageFactory,
        private readonly CustomerSession $customerSession,
        private readonly RequestInterface $request
    ) {
    }

    public function execute(): \Magento\Framework\View\Result\Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('My Returns (RMA)'));

        return $resultPage;
    }
}
