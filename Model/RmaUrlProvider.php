<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Magento\Framework\UrlInterface;

class RmaUrlProvider
{
    public function __construct(private readonly UrlInterface $url)
    {
    }

    public function getCustomerUrl(RmaInterface $rma): string
    {
        if ((int) $rma->getCustomerId() > 0) {
            return $this->url->getUrl('rma/index/view', ['rma_id' => $rma->getRmaId()]);
        }

        $guestToken = (string) $rma->getData('guest_access_token');
        if ($guestToken !== '') {
            return $this->url->getUrl('rma/guest/view', [
                'rma_id' => $rma->getRmaId(),
                'hash' => $guestToken,
            ]);
        }

        return $this->url->getUrl('sales/guest/form');
    }
}
