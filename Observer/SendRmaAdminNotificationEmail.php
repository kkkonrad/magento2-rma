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
 * Fix 8: Sends notification email to admin/support when a new RMA is created.
 * Previously there was no admin notification at all — admin had to manually check the panel.
 */
class SendRmaAdminNotificationEmail implements ObserverInterface
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

        if (!$rma || !$this->config->isEnabled((int) $rma->getStoreId())) {
            return;
        }

        $adminEmail = $this->config->getAdminNotificationEmail((int) $rma->getStoreId());
        if (!$adminEmail) {
            return;
        }

        try {
            $this->inlineTranslation->suspend();

            $store = $this->storeManager->getStore((int) $rma->getStoreId());

            $transport = $this->transportBuilder
                ->setTemplateIdentifier($this->config->getAdminNotificationEmailTemplate((int) $rma->getStoreId()))
                ->setTemplateOptions([
                    'area'  => \Magento\Framework\App\Area::AREA_ADMINHTML,
                    'store' => (int) $rma->getStoreId(),
                ])
                ->setTemplateVars([
                    'rma'   => $rma,
                    'store' => $store,
                ])
                ->setFromByScope($this->config->getEmailSender((int) $rma->getStoreId()))
                ->addTo($adminEmail)
                ->getTransport();

            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->logger->error('Failed to send RMA admin notification email: ' . $e->getMessage(), [
                'rma_id' => $rma->getRmaId(),
            ]);
        } finally {
            $this->inlineTranslation->resume();
        }
    }
}
