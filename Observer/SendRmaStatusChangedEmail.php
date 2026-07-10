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

class SendRmaStatusChangedEmail implements ObserverInterface
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
        $rma        = $observer->getData('rma');
        $statusFrom = $observer->getData('status_from');
        $statusTo   = $observer->getData('status_to');

        $storeId = (int) $rma->getStoreId();

        if (!$rma || !$this->config->isEnabled($storeId)) {
            return;
        }

        // Don't email on system-internal transitions (no customer visibility)
        if ($statusTo === \Kkkonrad\Rma\Api\Data\RmaInterface::STATUS_NEW) {
            return;
        }

        try {
            $this->inlineTranslation->suspend();

            $transport = $this->transportBuilder
                ->setTemplateIdentifier($this->config->getStatusChangedEmailTemplate($storeId))
                ->setTemplateOptions([
                    'area'  => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $storeId,
                ])
                ->setTemplateVars([
                    'rma'         => $rma,
                    'status_from' => $statusFrom,
                    'status_to'   => $statusTo,
                    'store'       => $this->storeManager->getStore($storeId),
                ])
                ->setFromByScope($this->config->getEmailSender($storeId))
                ->addTo($rma->getCustomerEmail(), $rma->getCustomerName())
                ->getTransport();

            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->logger->error('Failed to send RMA status changed email: ' . $e->getMessage(), [
                'rma_id'      => $rma->getRmaId(),
                'status_from' => $statusFrom,
                'status_to'   => $statusTo,
            ]);
        } finally {
            $this->inlineTranslation->resume();
        }
    }
}
