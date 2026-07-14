<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class RestoreMissingDefaultDictionaries implements DataPatchInterface
{
    private const REASONS = [
        ['code' => 'defective', 'label' => 'Defective / Damaged', 'sort_order' => 10],
        ['code' => 'not_as_described', 'label' => 'Not as Described', 'sort_order' => 20],
        ['code' => 'wrong_size', 'label' => 'Wrong Size / Fit', 'sort_order' => 30],
        ['code' => 'changed_mind', 'label' => 'Changed Mind / No Longer Needed', 'sort_order' => 40],
        ['code' => 'wrong_item', 'label' => 'Wrong Item Shipped', 'sort_order' => 50],
        ['code' => 'missing_parts', 'label' => 'Missing Parts / Accessories', 'sort_order' => 60],
        ['code' => 'arrived_late', 'label' => 'Arrived Too Late', 'sort_order' => 70],
        ['code' => 'other', 'label' => 'Other', 'sort_order' => 99],
    ];

    private const CONDITIONS = [
        ['code' => 'unopened', 'label' => 'Unopened / Sealed', 'sort_order' => 10],
        ['code' => 'open_unused', 'label' => 'Opened, Unused', 'sort_order' => 20],
        ['code' => 'used', 'label' => 'Used', 'sort_order' => 30],
        ['code' => 'damaged', 'label' => 'Damaged', 'sort_order' => 40],
    ];

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        try {
            $this->insertMissingRows('kkkonrad_rma_reason', self::REASONS);
            $this->insertMissingRows('kkkonrad_rma_condition', self::CONDITIONS);
        } finally {
            $connection->endSetup();
        }

        return $this;
    }

    /**
     * @param array<int, array{code: string, label: string, sort_order: int}> $rows
     */
    private function insertMissingRows(string $tableName, array $rows): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable($tableName);
        $codes = array_column($rows, 'code');
        $existingCodes = $connection->fetchCol(
            $connection->select()->from($table, ['code'])->where('code IN (?)', $codes)
        );

        $missingRows = array_values(array_filter(
            $rows,
            static fn (array $row): bool => !in_array($row['code'], $existingCodes, true)
        ));

        if ($missingRows !== []) {
            $connection->insertMultiple(
                $table,
                array_map(
                    static fn (array $row): array => $row + ['is_active' => 1],
                    $missingRows
                )
            );
        }
    }

    public static function getDependencies(): array
    {
        return [InstallDefaultConditions::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}
