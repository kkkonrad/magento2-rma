<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\Resolver\Cache;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;

/**
 * Fix R4: Provides cache tags for the customerRmas GraphQL query.
 *
 * These tags allow proper cache invalidation when any of the returned RMAs change.
 * When an RMA is updated (status change, new message, etc.), the full-page cache
 * entries tagged with the corresponding ID will be flushed.
 */
class CustomerRmaListIdentity implements IdentityInterface
{
    private const CACHE_TAG = 'kkkonrad_rma';

    /**
     * Get cache identities for the resolved RMA list.
     *
     * @param array $resolvedData
     * @return string[]
     */
    public function getIdentities(array $resolvedData): array
    {
        // Base tag — invalidates all RMA list caches
        $ids = [self::CACHE_TAG . '_list'];

        // Per-entity tags — allows targeted invalidation when a specific RMA changes
        foreach ($resolvedData['items'] ?? [] as $item) {
            if (!empty($item['rma_id'])) {
                $ids[] = self::CACHE_TAG . '_' . (int) $item['rma_id'];
            }
        }

        return $ids;
    }
}
