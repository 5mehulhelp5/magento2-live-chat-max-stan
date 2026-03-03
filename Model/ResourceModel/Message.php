<?php
declare(strict_types=1);

namespace MaxStan\LiveChat\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use MaxStan\LiveChat\Api\Data\MessageInterface;

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
     * Bulk-update status for the given message IDs in a single query.
     *
     * @throws LocalizedException
     */
    public function updateStatusBulk(array $messageIds, int $status): int
    {
        if (!$messageIds) {
            return 0;
        }

        return (int)$this->getConnection()->update(
            $this->getMainTable(),
            ['status' => $status],
            ['id IN (?)' => $messageIds]
        );
    }
}
