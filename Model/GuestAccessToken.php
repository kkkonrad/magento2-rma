<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Magento\Framework\Math\Random;

class GuestAccessToken
{
    private const TTL_DAYS = 30;

    public function __construct(private readonly Random $random)
    {
    }

    public function issue(RmaInterface $rma): string
    {
        $token = $this->random->getRandomString(64);
        $rma->setData('guest_access_token_hash', hash('sha256', $token));
        $rma->setData('guest_access_token_expires_at', gmdate('Y-m-d H:i:s', strtotime('+' . self::TTL_DAYS . ' days')));
        return $token;
    }

    public function isValid(RmaInterface $rma, string $token): bool
    {
        $hash = (string) $rma->getData('guest_access_token_hash');
        $expiresAt = (string) $rma->getData('guest_access_token_expires_at');
        return $hash !== '' && $expiresAt !== '' && strtotime($expiresAt) >= time()
            && hash_equals($hash, hash('sha256', $token));
    }
}
