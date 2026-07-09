<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Index;

use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;

class PrintSlip implements HttpGetActionInterface
{
    public function __construct(
        private readonly PageFactory $resultPageFactory,
        private readonly RequestInterface $request,
        private readonly CustomerSession $customerSession,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly RedirectFactory $redirectFactory
    ) {
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->redirectFactory->create();

        if (!$this->customerSession->isLoggedIn()) {
            return $resultRedirect->setPath('customer/account/login');
        }

        $rmaId = (int) $this->request->getParam('rma_id');
        $customerId = (int) $this->customerSession->getCustomerId();

        try {
            $rma = $this->rmaRepository->getById($rmaId);
            if ((int) $rma->getCustomerId() !== $customerId) {
                return $resultRedirect->setPath('rma/index/index');
            }
        } catch (NoSuchEntityException) {
            return $resultRedirect->setPath('rma/index/index');
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Return Slip - RMA #%1', $rma->getIncrementId()));

        return $resultPage;
    }
}
