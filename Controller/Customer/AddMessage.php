<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Customer;

use Kkkonrad\Rma\Api\Data\RmaMessageInterface;
use Kkkonrad\Rma\Api\RmaManagementInterface;
use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;

class AddMessage implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly CustomerSession $customerSession,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly RmaManagementInterface $rmaManagement
    ) {
    }

    public function execute(): \Magento\Framework\Controller\Result\Json
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->customerSession->isLoggedIn()) {
            return $result->setData(['success' => false, 'message' => __('Please log in.')]);
        }

        try {
            $rmaId      = (int) $this->request->getPost('rma_id');
            $message    = trim((string) $this->request->getPost('message'));
            $customerId = (int) $this->customerSession->getCustomerId();

            if (!$rmaId || !$message) {
                throw new LocalizedException(__('Invalid request.'));
            }

            // Security: verify customer owns this RMA
            $rma = $this->rmaRepository->getById($rmaId);
            if ((int) $rma->getCustomerId() !== $customerId) {
                throw new LocalizedException(__('Access denied.'));
            }

            $this->rmaManagement->addMessage(
                $rmaId,
                $message,
                RmaMessageInterface::AUTHOR_CUSTOMER,
                $customerId,
                $this->customerSession->getCustomer()->getName(),
                false
            );

            return $result->setData(['success' => true, 'message' => __('Message sent.')]);

        } catch (LocalizedException $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => __('An error occurred.')]);
        }
    }
}
