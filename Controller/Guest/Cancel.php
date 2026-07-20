<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Guest;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\RmaManagementInterface;
use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\Config;
use Kkkonrad\Rma\Model\GuestAccessToken;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;

class Cancel implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly RmaManagementInterface $rmaManagement,
        private readonly Config $config,
        private readonly GuestAccessToken $guestAccessToken,
        private readonly FormKeyValidator $formKeyValidator
    ) {
    }

    public function execute(): \Magento\Framework\Controller\Result\Json
    {
        $result = $this->resultJsonFactory->create();
        $rmaId = (int)$this->request->getPost('rma_id');
        $hash  = (string)$this->request->getPost('hash');

        try {
            if (!$this->formKeyValidator->validate($this->request)) {
                throw new LocalizedException(__('Invalid security token. Please refresh and try again.'));
            }
            if (!$this->config->allowGuestRma()) {
                throw new LocalizedException(__('Guest Returns are not allowed.'));
            }

            $rma = $this->rmaRepository->getById($rmaId);
            if (!$this->guestAccessToken->isValid($rma, $hash)) {
                throw new LocalizedException(__('Access denied. Invalid tracking link.'));
            }

            if (!$this->config->canCustomerCancelRma((int) $rma->getStoreId())) {
                throw new LocalizedException(__('RMA cancellation is not allowed.'));
            }

            $this->rmaManagement->changeStatus(
                $rmaId,
                RmaInterface::STATUS_CANCELLED,
                (string)__('Cancelled by guest customer.'),
                'customer',
                0
            );

            return $result->setData([
                'success' => true,
                'message' => __('Your return request has been cancelled.')
            ]);

        } catch (LocalizedException $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception) {
            return $result->setData([
                'success' => false,
                'message' => __('An error occurred. Please try again.')
            ]);
        }
    }
}
