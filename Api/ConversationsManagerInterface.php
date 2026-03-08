<?php
declare(strict_types=1);

namespace MaxStan\LiveChat\Api;

use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use MaxStan\LiveChat\Model\Conversation;

interface ConversationsManagerInterface
{
    public const int CONVERSATIONS_LIMIT = 10;

    /**
     * @return \MaxStan\LiveChat\Api\Data\PublicConversationInterface[]
     *
     * @throws NoSuchEntityException
     * @throws AuthorizationException
     * @throws CookieSizeLimitReachedException
     * @throws FailureToSendException
     * @throws InputException
     */
    public function get(): array;

    /**
     * @return \MaxStan\LiveChat\Api\Data\PublicConversationInterface
     *
     * @throws LocalizedException
     */
    public function create(): Conversation;

    /**
     * Mark unread messages from the other party as read.
     *
     *
     * @param int $conversationId
     * @return bool
     *
     * @throws LocalizedException
     */
    public function markAsRead(int $conversationId): bool;
}
