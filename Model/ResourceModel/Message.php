<?php
declare(strict_types=1);

namespace MaxStan\LiveChat\Model\ResourceModel;

use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Message extends AbstractDb
{
    protected string $_eventPrefix = 'livechat_messages_resource_model';

    /**
     * Initialize resource model.
     */
    protected function _construct(): void
    {
        $this->_init('livechat_messages', 'id');
        $this->_useIsObjectNew = true;
    }

    /**
     * @throws LocalizedException
     */
    public function getLastMessageId(int $conversationId, int $userId): ?int
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(
                $this->getMainTable(),
                [new Expression('MAX(id)')]
            )
            ->where('conversation_id = ?', $conversationId)
            ->where('sender_id != ?', $userId);

        return (int)$connection->fetchOne($select) ?: null;
    }
}
