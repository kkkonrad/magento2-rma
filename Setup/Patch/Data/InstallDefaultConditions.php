<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Setup\Patch\Data;

use Kkkonrad\Rma\Model\ResourceModel\RmaCondition;
use Kkkonrad\Rma\Model\RmaConditionFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class InstallDefaultConditions implements DataPatchInterface, PatchRevertableInterface
{
    private const CONDITIONS = [
        ['code' => 'unopened',           'label' => 'Unopened / Sealed',     'sort_order' => 10],
        ['code' => 'open_unused',        'label' => 'Opened, Unused',        'sort_order' => 20],
        ['code' => 'used',               'label' => 'Used',                  'sort_order' => 30],
        ['code' => 'damaged',            'label' => 'Damaged',               'sort_order' => 40],
    ];

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly RmaConditionFactory $rmaConditionFactory,
        private readonly RmaCondition $rmaConditionResource
    ) {
    }

    public function apply(): self
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        foreach (self::CONDITIONS as $conditionData) {
            $condition = $this->rmaConditionFactory->create();
            $condition->setCode($conditionData['code'])
                ->setLabel($conditionData['label'])
                ->setSortOrder($conditionData['sort_order'])
                ->setIsActive(true);

            $this->rmaConditionResource->save($condition);
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    public function revert(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $codes = array_column(self::CONDITIONS, 'code');
        $this->moduleDataSetup->getConnection()->delete(
            $this->moduleDataSetup->getTable('kkkonrad_rma_condition'),
            ['code IN (?)' => $codes]
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public static function getDependencies(): array
    {
        return [InstallDefaultReasons::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}
