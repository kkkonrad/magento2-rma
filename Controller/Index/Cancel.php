<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Index;

use Kkkonrad\Rma\Api\RmaManagementInterface;
use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\Config;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;

/**
 * Allows a logged-in customer to cancel their own RMA request.
 * Only available when "Allow Customers to Cancel Their RMA" is enabled in config.
 */
class Cancel implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly CustomerSession $customerSession,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly RmaManagementInterface $rmaManagement,
        private readonly FormKeyValidator $formKeyValidator,
        private readonly Config $config
    ) {
    }

    public function execute(): \Magento\Framework\Controller\Result\Json
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->customerSession->isLoggedIn()) {
            return $result->setData(['success' => false, 'message' => __('Please log in.')]);
        }

        if (!$this->formKeyValidator->validate($this->request)) {
            return $result->setData(['success' => false, 'message' => __('Invalid security token. Please refresh and try again.')]);
        }

        try {
            $rmaId      = (int) $this->request->getPost('rma_id');
            $customerId = (int) $this->customerSession->getCustomerId();

            if (!$rmaId) {
                throw new LocalizedException(__('Invalid RMA ID.'));
            }

            // Security: verify customer owns this RMA
            $rma = $this->rmaRepository->getById($rmaId);
            if ((int) $rma->getCustomerId() !== $customerId) {
                throw new LocalizedException(__('Access denied.'));
            }

            // Feature gate: check if cancellation is allowed by config
            if (!$this->config->canCustomerCancelRma((int) $rma->getStoreId())) {
                throw new LocalizedException(__('Cancellation of return requests is not allowed.'));
            }

            $this->rmaManagement->cancel(
                $rmaId,
                (string) __('Cancelled by customer.')
            );

            return $result->setData([
                'success' => true,
                'message' => __('Your return request has been cancelled.'),
            ]);

        } catch (LocalizedException $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => __('An error occurred.')]);
        }
    }
}
