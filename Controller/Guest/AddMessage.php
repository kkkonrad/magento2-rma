<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Guest;

use Kkkonrad\Rma\Api\Data\RmaMessageInterface;
use Kkkonrad\Rma\Api\RmaManagementInterface;
use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\Config;
use Kkkonrad\Rma\Model\GuestAccessToken;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;

class AddMessage implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly RmaManagementInterface $rmaManagement,
        private readonly FormKeyValidator $formKeyValidator,
        private readonly Config $config,
        private readonly GuestAccessToken $guestAccessToken
    ) {
    }

    public function execute(): \Magento\Framework\Controller\Result\Json
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->config->allowGuestRma()) {
            return $result->setData(['success' => false, 'message' => __('Guest Returns are not allowed.')]);
        }

        if (!$this->formKeyValidator->validate($this->request)) {
            return $result->setData(['success' => false, 'message' => __('Invalid security token. Please refresh and try again.')]);
        }

        try {
            $rmaId   = (int) $this->request->getPost('rma_id');
            $hash    = (string) $this->request->getPost('hash');
            $message = trim((string) $this->request->getPost('message'));

            if (!$rmaId || !$hash || !$message) {
                throw new LocalizedException(__('Invalid request parameters.'));
            }

            // Secure Hash Authentication
            $rma = $this->rmaRepository->getById($rmaId);
            if (!$this->guestAccessToken->isValid($rma, $hash)) {
                throw new LocalizedException(__('Access denied. Invalid tracking link.'));
            }

            $this->rmaManagement->addMessage(
                $rmaId,
                $message,
                RmaMessageInterface::AUTHOR_CUSTOMER,
                0, // Guest Customer ID
                $rma->getCustomerName() ?: (string)__('Guest Customer'),
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
