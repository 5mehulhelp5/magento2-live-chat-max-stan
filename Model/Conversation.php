<?php
declare(strict_types=1);

namespace MaxStan\LiveChat\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use MaxStan\LiveChat\Api\Data\ConversationInterface;
use MaxStan\LiveChat\Model\ResourceModel\Conversation as ResourceModel;

class Conversation extends AbstractModel implements ConversationInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'livechat_conversations_model';

    /**
     * @throws LocalizedException
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel::class);
    }

    public function getUserId(): int
    {
        return (int)$this->getData(self::USER_ID);
    }

    public function setUserId(int $userId): ConversationInterface
    {
        $this->setData(self::USER_ID, $userId);

        return $this;
    }

    public function getCreatedAt(): string
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): ConversationInterface
    {
        $this->setData(self::CREATED_AT, $createdAt);

        return $this;
    }

    public function getLastUserReadMessageId(): ?int
    {
        return (int)$this->getData(static::LAST_USER_READ_MESSAGE_ID) ?: null;
    }

    public function setLastUserReadMessageId(int $lastUserReadMessageId): ConversationInterface
    {
        $this->setData(static::LAST_USER_READ_MESSAGE_ID, $lastUserReadMessageId);

        return $this;
    }

    public function getLastAdminReadMessageId(): ?int
    {
        return (int)$this->getData(static::LAST_ADMIN_READ_MESSAGE_ID) ?: null;
    }

    public function setLastAdminReadMessageId(int $lastAdminReadMessageId): ConversationInterface
    {
        $this->setData(static::LAST_ADMIN_READ_MESSAGE_ID, $lastAdminReadMessageId);

        return $this;
    }
}
