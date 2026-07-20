<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Guest;

use Kkkonrad\Rma\Api\RmaManagementInterface;
use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\Config;
use Kkkonrad\Rma\Model\GuestAccessToken;
use Kkkonrad\Rma\Model\GuestRmaLocator;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Helper\Guest as GuestHelper;

class Access implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly RedirectFactory $redirectFactory,
        private readonly GuestHelper $guestHelper,
        private readonly Registry $registry,
        private readonly CustomerSession $customerSession,
        private readonly MessageManagerInterface $messageManager,
        private readonly Config $config,
        private readonly GuestRmaLocator $guestRmaLocator,
        private readonly RmaManagementInterface $rmaManagement,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly GuestAccessToken $guestAccessToken
    ) {
    }

    public function execute(): ResultInterface
    {
        $validationResult = $this->guestHelper->loadValidOrder($this->request);
        if ($validationResult instanceof ResultInterface) {
            return $validationResult;
        }

        $redirect = $this->redirectFactory->create();

        try {
            $order = $this->registry->registry('current_order');
            if (!$order instanceof OrderInterface
                || !$order->getCustomerIsGuest()
                || (int) $this->request->getParam('order_id') !== (int) $order->getEntityId()
            ) {
                throw new LocalizedException(__('The guest order could not be authorized.'));
            }

            $storeId = (int) $order->getStoreId();
            if (!$this->config->isEnabled($storeId) || !$this->config->allowGuestRma($storeId)) {
                throw new LocalizedException(__('Guest returns are currently unavailable.'));
            }

            if ((string) $this->request->getParam('rma_action') === 'create') {
                $activeRma = $this->guestRmaLocator->getActiveForOrder((int) $order->getEntityId());
                if ($activeRma) {
                    return $this->redirectToRma($activeRma);
                }
                if (!$this->rmaManagement->isOrderEligibleForRma((int) $order->getEntityId(), 0)) {
                    throw new LocalizedException(__('This order is not eligible for a return.'));
                }

                $this->customerSession->setGuestRmaOrderId((int) $order->getEntityId());
                return $redirect->setPath('rma/guest/create');
            }

            $rma = $this->guestRmaLocator->getLatestForOrder((int) $order->getEntityId());
            if (!$rma) {
                throw new LocalizedException(__('No return request was found for this order.'));
            }

            return $this->redirectToRma($rma);
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            return $redirect->setPath('sales/guest/view');
        }
    }

    private function redirectToRma(\Kkkonrad\Rma\Api\Data\RmaInterface $rma): ResultInterface
    {
        $token = $this->guestAccessToken->issue($rma);
        $this->rmaRepository->save($rma);

        return $this->redirectFactory->create()->setPath('rma/guest/view', [
            'rma_id' => $rma->getRmaId(),
            'hash' => $token,
        ]);
    }
}
