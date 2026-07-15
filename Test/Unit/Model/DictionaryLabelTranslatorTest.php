<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Test\Unit\Model;

use Kkkonrad\Rma\Model\DictionaryLabelTranslator;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;

class DictionaryLabelTranslatorTest extends TestCase
{
    public function testDefaultReasonUsesTranslatablePhrase(): void
    {
        $label = (new DictionaryLabelTranslator())->getReasonLabel('defective', 'Fallback');

        self::assertInstanceOf(Phrase::class, $label);
        self::assertSame('Kkkonrad RMA reason: defective', (string)$label);
    }

    public function testDefaultConditionUsesTranslatablePhrase(): void
    {
        $label = (new DictionaryLabelTranslator())->getConditionLabel('unopened', 'Fallback');

        self::assertInstanceOf(Phrase::class, $label);
        self::assertSame('Kkkonrad RMA condition: unopened', (string)$label);
    }

    public function testCustomLabelsRemainUnchanged(): void
    {
        $translator = new DictionaryLabelTranslator();

        self::assertSame('My custom reason', $translator->getReasonLabel('custom', 'My custom reason'));
        self::assertSame('My custom condition', $translator->getConditionLabel('custom', 'My custom condition'));
        self::assertSame('My custom resolution', $translator->getResolutionLabel('custom', 'My custom resolution'));
    }

    public function testDefaultResolutionUsesTranslatablePhrase(): void
    {
        $label = (new DictionaryLabelTranslator())->getResolutionLabel('exchange', 'Fallback');

        self::assertInstanceOf(Phrase::class, $label);
        self::assertSame('Kkkonrad RMA resolution: exchange', (string) $label);
    }
}
