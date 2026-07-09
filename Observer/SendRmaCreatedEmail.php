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

class SendRmaCreatedEmail implements ObserverInterface
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

        try {
            $this->inlineTranslation->suspend();

            $transport = $this->transportBuilder
                ->setTemplateIdentifier($this->config->getCreatedEmailTemplate((int) $rma->getStoreId()))
                ->setTemplateOptions([
                    'area'     => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store'    => (int) $rma->getStoreId(),
                ])
                ->setTemplateVars([
                    'rma'   => $rma,
                    'store' => $this->storeManager->getStore((int) $rma->getStoreId()),
                ])
                ->setFromByScope($this->config->getEmailSender((int) $rma->getStoreId()))
                ->addTo($rma->getCustomerEmail(), $rma->getCustomerName())
                ->getTransport();

            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->logger->error('Failed to send RMA created email: ' . $e->getMessage(), [
                'rma_id' => $rma->getRmaId(),
            ]);
        } finally {
            $this->inlineTranslation->resume();
        }
    }
}
