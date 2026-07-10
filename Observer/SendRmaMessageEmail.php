<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Observer;

use Kkkonrad\Rma\Api\Data\RmaMessageInterface;
use Kkkonrad\Rma\Model\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Fix 9: Sends notification to customer when admin replies to their RMA message.
 * Previously kkkonrad_rma_message_added event was dispatched but no Observer listened to it —
 * customers never received notifications about admin replies.
 */
class SendRmaMessageEmail implements ObserverInterface
{
    public function __construct(
        private readonly TransportBuilder $transportBuilder,
        private readonly StateInterface $inlineTranslation,
        private readonly StoreManagerInterface $storeManager,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var \Kkkonrad\Rma\Model\Rma $rma */
        $rma = $observer->getData('rma');
        /** @var \Kkkonrad\Rma\Model\RmaMessage $message */
        $message = $observer->getData('message');

        if (!$rma || !$message || !$this->config->isEnabled((int) $rma->getStoreId())) {
            return;
        }

        // Only notify customer when admin sends a non-internal message
        if ($message->getAuthorType() !== RmaMessageInterface::AUTHOR_ADMIN) {
            return;
        }
        if ($message->getIsInternal()) {
            return;
        }

        try {
            $this->inlineTranslation->suspend();

            $transport = $this->transportBuilder
                ->setTemplateIdentifier($this->config->getMessageAddedEmailTemplate((int) $rma->getStoreId()))
                ->setTemplateOptions([
                    'area'  => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => (int) $rma->getStoreId(),
                ])
                ->setTemplateVars([
                    'rma'     => $rma,
                    'message' => $message,
                    'store'   => $this->storeManager->getStore((int) $rma->getStoreId()),
                ])
                ->setFromByScope($this->config->getEmailSender((int) $rma->getStoreId()))
                ->addTo($rma->getCustomerEmail(), $rma->getCustomerName())
                ->getTransport();

            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->logger->error('Failed to send RMA message notification email: ' . $e->getMessage(), [
                'rma_id'     => $rma->getRmaId(),
                'message_id' => $message->getMessageId(),
            ]);
        } finally {
            $this->inlineTranslation->resume();
        }
    }
}
