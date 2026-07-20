<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Observer;

use Kkkonrad\Rma\Model\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Fix 7: Sends shipping label uploaded notification to customer.
 * Previously this logic was inside UploadShippingLabel controller — moved here to follow
 * the same Observer pattern used by all other RMA email notifications.
 */
class SendRmaLabelUploadedEmail implements ObserverInterface
{
    public function __construct(
        private readonly TransportBuilder $transportBuilder,
        private readonly StateInterface $inlineTranslation,
        private readonly StoreManagerInterface $storeManager,
        private readonly Config $config,
        private readonly LoggerInterface $logger,
        private readonly \Kkkonrad\Rma\Model\RmaUrlProvider $rmaUrlProvider
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var \Kkkonrad\Rma\Model\Rma $rma */
        $rma = $observer->getData('rma');

        if (!$rma || !$this->config->isEnabled((int) $rma->getStoreId())) {
            return;
        }

        try {
            $this->inlineTranslation->suspend();

            $transport = $this->transportBuilder
                ->setTemplateIdentifier($this->config->getLabelUploadedEmailTemplate((int) $rma->getStoreId()))
                ->setTemplateOptions([
                    'area'  => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => (int) $rma->getStoreId(),
                ])
                ->setTemplateVars([
                    'rma'   => $rma,
                    'store' => $this->storeManager->getStore((int) $rma->getStoreId()),
                    'rma_url' => $this->rmaUrlProvider->getCustomerUrl($rma),
                ])
                ->setFromByScope($this->config->getEmailSender((int) $rma->getStoreId()))
                ->addTo($rma->getCustomerEmail(), $rma->getCustomerName())
                ->getTransport();

            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->logger->error('Failed to send RMA shipping label uploaded email: ' . $e->getMessage(), [
                'rma_id' => $rma->getRmaId(),
            ]);
        } finally {
            $this->inlineTranslation->resume();
        }
    }
}
