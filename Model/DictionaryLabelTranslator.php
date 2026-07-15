<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Magento\Framework\Phrase;

class DictionaryLabelTranslator
{
    public function getReasonLabel(string $code, string $fallbackLabel): Phrase|string
    {
        return match ($code) {
            'defective' => __('Kkkonrad RMA reason: defective'),
            'not_as_described' => __('Kkkonrad RMA reason: not as described'),
            'wrong_size' => __('Kkkonrad RMA reason: wrong size'),
            'changed_mind' => __('Kkkonrad RMA reason: changed mind'),
            'wrong_item' => __('Kkkonrad RMA reason: wrong item'),
            'missing_parts' => __('Kkkonrad RMA reason: missing parts'),
            'arrived_late' => __('Kkkonrad RMA reason: arrived late'),
            'other' => __('Kkkonrad RMA reason: other'),
            default => $fallbackLabel,
        };
    }

    public function getConditionLabel(string $code, string $fallbackLabel): Phrase|string
    {
        return match ($code) {
            'unopened' => __('Kkkonrad RMA condition: unopened'),
            'open_unused' => __('Kkkonrad RMA condition: opened unused'),
            'used' => __('Kkkonrad RMA condition: used'),
            'damaged' => __('Kkkonrad RMA condition: damaged'),
            default => $fallbackLabel,
        };
    }

    public function getResolutionLabel(string $code, string $fallbackLabel): Phrase|string
    {
        return match ($code) {
            RmaInterface::RESOLUTION_REFUND => __('Kkkonrad RMA resolution: refund'),
            RmaInterface::RESOLUTION_EXCHANGE => __('Kkkonrad RMA resolution: exchange'),
            RmaInterface::RESOLUTION_REPAIR => __('Kkkonrad RMA resolution: repair'),
            RmaInterface::RESOLUTION_VOUCHER => __('Kkkonrad RMA resolution: voucher'),
            default => $fallbackLabel,
        };
    }
}
