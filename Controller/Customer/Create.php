<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Customer;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * GET controller — renders the "Create Return Request" form page.
 * The actual form submission is handled by Customer\Save (POST).
 */
class Create implements HttpGetActionInterface
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
        $resultPage->getConfig()->getTitle()->set(__('New Return Request'));

        return $resultPage;
    }
}
