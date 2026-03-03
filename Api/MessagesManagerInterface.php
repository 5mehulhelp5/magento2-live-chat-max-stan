<?php
declare(strict_types=1);

namespace MaxStan\LiveChat\Api;

use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use MaxStan\LiveChat\Model\Message;

interface MessagesManagerInterface
{
    public const int MESSAGES_LIMIT = 25;

    /**
     * @param int $conversationId
     * @param string $text
     * @return \MaxStan\LiveChat\Api\Data\PublicMessageInterface
     *
     * @throws AuthorizationException
     * @throws LocalizedException
     */
    public function send(int $conversationId, string $text): Message;

    /**
     * @param int $conversationId
     * @param int $currentPage
     * @return \MaxStan\LiveChat\Api\Data\PublicMessageInterface[]
     *
     * @throws NoSuchEntityException
     * @throws AuthorizationException
     */
    public function get(int $conversationId, int $currentPage = 1): array;

    /**
     * Mark unread messages from the other party as read.
     *
     *
     * @param int $conversationId
     * @param int[] $messageIds
     * @return bool
     *
     * @throws LocalizedException
     */
    public function markAsRead(int $conversationId, array $messageIds = []): bool;
}
