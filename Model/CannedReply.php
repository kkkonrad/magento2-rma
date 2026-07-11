<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Kkkonrad\Rma\Api\Data\CannedReplyInterface;
use Kkkonrad\Rma\Model\ResourceModel\CannedReply as CannedReplyResource;
use Magento\Framework\Model\AbstractModel;

class CannedReply extends AbstractModel implements CannedReplyInterface
{
    protected $_eventPrefix = 'kkkonrad_rma_canned_reply';

    protected $_idFieldName = 'reply_id';

    protected function _construct(): void
    {
        $this->_init(CannedReplyResource::class);
    }

    public function getReplyId(): ?int
    {
        $v = $this->getData(self::REPLY_ID);
        return $v !== null ? (int)$v : null;
    }

    public function setReplyId(int $replyId): self
    {
        return $this->setData(self::REPLY_ID, $replyId);
    }

    public function getTitle(): string
    {
        return (string)$this->getData(self::TITLE);
    }

    public function setTitle(string $title): self
    {
        return $this->setData(self::TITLE, $title);
    }

    public function getText(): string
    {
        return (string)$this->getData(self::TEXT);
    }

    public function setText(string $text): self
    {
        return $this->setData(self::TEXT, $text);
    }

    public function getIsActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    public function setIsActive(bool $isActive): self
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }
}
