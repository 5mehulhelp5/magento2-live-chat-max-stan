<?php

declare(strict_types=1);

namespace MaxStan\LiveChat\Model\Topic;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use MaxStan\LiveChat\Model\CustomerUid;
use MaxStan\LiveChat\Model\ResourceModel\Conversation\Collection;
use MaxStan\LiveChat\Model\ResourceModel\Conversation\CollectionFactory;
use MaxStan\Mercure\Api\MercureTopicProviderInterface;
use MaxStan\Mercure\Model\Iri;

readonly class ConversationProvider implements MercureTopicProviderInterface
{
    public function __construct(
        private CustomerUid $customerUid,
        private CollectionFactory $collectionFactory,
        private Iri $iri
    ) {
    }

    /**
     * @inheritDoc
     * @throws NoSuchEntityException
     */
    public function getPrivateTopics(int $userId, int $userType): array
    {
        if ($userType === UserContextInterface::USER_TYPE_ADMIN) {
            return array_map(
                fn ($userId) => $this->iri->get("livechat/" . $this->customerUid->get($userId)),
                $this->getConversationUserIds()
            );
        }

        $uid = $this->customerUid->get($userId);
        return [$this->iri->get("livechat/$uid")];
    }

    public function getPublicTopics(): array
    {
        return [];
    }

    private function getConversationUserIds(): array
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();

        return array_unique($collection->getColumnValues('user_id'));
    }
}
