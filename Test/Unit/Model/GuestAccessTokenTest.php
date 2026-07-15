<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Test\Unit\Model;

use Kkkonrad\Rma\Model\GuestAccessToken;
use Kkkonrad\Rma\Model\Rma;
use Magento\Framework\Math\Random;
use PHPUnit\Framework\TestCase;

class GuestAccessTokenTest extends TestCase
{
    public function testIssuedTokenIsValidAndStoredOnlyAsHash(): void
    {
        $random = $this->createMock(Random::class);
        $random->method('getRandomString')->with(64)->willReturn('token-for-test');
        $rma = $this->createRmaModel();

        $token = (new GuestAccessToken($random))->issue($rma);

        $this->assertSame('token-for-test', $token);
        $this->assertSame(hash('sha256', $token), $rma->getData('guest_access_token_hash'));
        $this->assertNotEmpty($rma->getData('guest_access_token_expires_at'));
    }

    public function testTokenValidationRejectsWrongAndExpiredTokens(): void
    {
        $service = new GuestAccessToken($this->createMock(Random::class));
        $rma = $this->createRmaModel();
        $rma->setData('guest_access_token_hash', hash('sha256', 'correct-token'));
        $rma->setData('guest_access_token_expires_at', gmdate('Y-m-d H:i:s', strtotime('+1 day')));

        $this->assertTrue($service->isValid($rma, 'correct-token'));
        $this->assertFalse($service->isValid($rma, 'wrong-token'));

        $rma->setData('guest_access_token_expires_at', gmdate('Y-m-d H:i:s', strtotime('-1 second')));
        $this->assertFalse($service->isValid($rma, 'correct-token'));

        $rma->setData('guest_access_token_expires_at', 'invalid-date');
        $this->assertFalse($service->isValid($rma, 'correct-token'));
    }

    private function createRmaModel(): Rma
    {
        return new class extends Rma {
            private array $testData = [];

            public function __construct()
            {
            }

            public function setData($key, $value = null)
            {
                $this->testData[$key] = $value;
                return $this;
            }

            public function getData($key = '', $index = null)
            {
                return $this->testData[$key] ?? null;
            }
        };
    }
}
