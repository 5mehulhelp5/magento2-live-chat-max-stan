<?php
declare(strict_types=1);

namespace MaxStan\LiveChat\Api\Data;

interface ConversationInterface
{
    public const string ID = "id";
    public const string USER_ID = "user_id";
    public const string CREATED_AT = "created_at";
    public const string LAST_USER_READ_MESSAGE_ID = "last_user_read_message_id";
    public const string LAST_ADMIN_READ_MESSAGE_ID = "last_admin_read_message_id";

    /**
     * @return int
     */
    public function getId();

    /**
     * @param $id
     * @return \MaxStan\LiveChat\Api\Data\ConversationInterface
     */
    public function setId($id);

    /**
     * @return int
     */
    public function getUserId(): int;

    /**
     * @param int $userId
     * @return \MaxStan\LiveChat\Api\Data\ConversationInterface
     */
    public function setUserId(int $userId): ConversationInterface;

    /**
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * @param string $createdAt
     * @return \MaxStan\LiveChat\Api\Data\ConversationInterface
     */
    public function setCreatedAt(string $createdAt): ConversationInterface;

    /**
     * @return int
     */
    public function getLastUserReadMessageId(): ?int;

    /**
     * @param int $lastUserReadMessageId
     * @return \MaxStan\LiveChat\Api\Data\ConversationInterface
     */
    public function setLastUserReadMessageId(int $lastUserReadMessageId): ConversationInterface;

    /**
     * @return int
     */
    public function getLastAdminReadMessageId(): ?int;

    /**
     * @param int $lastAdminReadMessageId
     * @return \MaxStan\LiveChat\Api\Data\ConversationInterface
     */
    public function setLastAdminReadMessageId(int $lastAdminReadMessageId): ConversationInterface;
}
