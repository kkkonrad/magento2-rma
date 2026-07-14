<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class InitializeRmaIncrementIds implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $rmaTable = $this->moduleDataSetup->getTable('kkkonrad_rma');
        $sequenceTable = $this->moduleDataSetup->getTable('kkkonrad_rma_sequence');
        $connection->startSetup();

        try {
            $rows = $connection->fetchAll(
                $connection->select()
                    ->from($rmaTable, ['rma_id'])
                    ->where('increment_id IS NULL OR increment_id = ?', '')
            );

            foreach ($rows as $row) {
                $rmaId = (int)$row['rma_id'];
                $connection->update(
                    $rmaTable,
                    ['increment_id' => $this->formatIncrementId($rmaId)],
                    ['rma_id = ?' => $rmaId]
                );
            }

            $maxRmaId = (int)$connection->fetchOne(
                $connection->select()->from($rmaTable, ['MAX(rma_id)'])
            );
            $maxSequence = (int)$connection->fetchOne(
                $connection->select()->from($sequenceTable, ['MAX(sequence_value)'])
            );

            if ($maxRmaId > $maxSequence) {
                $connection->insert($sequenceTable, ['sequence_value' => $maxRmaId]);
            }
        } finally {
            $connection->endSetup();
        }

        return $this;
    }

    private function formatIncrementId(int $sequenceValue): string
    {
        return sprintf('RMA-%06d', $sequenceValue);
    }

    public static function getDependencies(): array
    {
        return [RestoreMissingDefaultDictionaries::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}
