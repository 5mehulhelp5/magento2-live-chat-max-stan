<?php

declare(strict_types=1);

namespace MaxStan\LiveChat\Service;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\NoSuchEntityException;
use MaxStan\LiveChat\Api\ConversationRepositoryInterface;
use MaxStan\LiveChat\Api\Data\ConversationInterface;

readonly class Authorization
{
    public function __construct(
        private UserContextInterface $userContext,
        private ConversationRepositoryInterface $conversationRepository
    ) {
    }

    /**
     * @throws NoSuchEntityException
     * @throws AuthorizationException
     */
    public function isAllowed(int $conversationId): ConversationInterface
    {
        $customerId = $this->userContext->getUserId();
        $conversation = $this->conversationRepository->getById($conversationId);
        $userType = $this->userContext->getUserType();
        if (
            $userType === UserContextInterface::USER_TYPE_CUSTOMER
            && $customerId
            && $conversation->getUserId() === $customerId
        ) {
            return $conversation;
        }

        if ($userType === UserContextInterface::USER_TYPE_ADMIN) {
            return $conversation;
        }

        throw new AuthorizationException(
            __('You are not authorized for requested resource.')
        );
    }
}
