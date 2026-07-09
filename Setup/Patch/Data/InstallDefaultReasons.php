<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Setup\Patch\Data;

use Kkkonrad\Rma\Model\ResourceModel\RmaReason;
use Kkkonrad\Rma\Model\RmaReasonFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class InstallDefaultReasons implements DataPatchInterface, PatchRevertableInterface
{
    private const REASONS = [
        ['code' => 'defective',         'label' => 'Defective / Damaged',          'sort_order' => 10],
        ['code' => 'not_as_described',  'label' => 'Not as Described',              'sort_order' => 20],
        ['code' => 'wrong_size',        'label' => 'Wrong Size / Fit',              'sort_order' => 30],
        ['code' => 'changed_mind',      'label' => 'Changed Mind / No Longer Needed', 'sort_order' => 40],
        ['code' => 'wrong_item',        'label' => 'Wrong Item Shipped',            'sort_order' => 50],
        ['code' => 'missing_parts',     'label' => 'Missing Parts / Accessories',   'sort_order' => 60],
        ['code' => 'arrived_late',      'label' => 'Arrived Too Late',              'sort_order' => 70],
        ['code' => 'other',             'label' => 'Other',                         'sort_order' => 99],
    ];

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly RmaReasonFactory $rmaReasonFactory,
        private readonly RmaReason $rmaReasonResource
    ) {
    }

    public function apply(): self
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        foreach (self::REASONS as $reasonData) {
            $reason = $this->rmaReasonFactory->create();
            $reason->setCode($reasonData['code'])
                ->setLabel($reasonData['label'])
                ->setSortOrder($reasonData['sort_order'])
                ->setIsActive(true);

            $this->rmaReasonResource->save($reason);
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    public function revert(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $codes = array_column(self::REASONS, 'code');
        $this->moduleDataSetup->getConnection()->delete(
            $this->moduleDataSetup->getTable('kkkonrad_rma_reason'),
            ['code IN (?)' => $codes]
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
