<?php

declare(strict_types=1);

namespace MaxStan\LiveChat\Controller\Adminhtml\Conversation;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\NoSuchEntityException;
use MaxStan\LiveChat\Api\ConversationRepositoryInterface;
use MaxStan\LiveChat\Api\ConversationsManagerInterface;
use MaxStan\LiveChat\Api\MessagesManagerInterface;

class Index extends Action implements HttpGetActionInterface
{
    public const string ADMIN_RESOURCE = 'MaxStan_LiveChat::livechat';

    public function __construct(
        Context $context,
        private readonly ConversationRepositoryInterface $conversationRepository,
        private readonly MessagesManagerInterface $messagesManager,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
    ) {
        parent::__construct($context);
    }

    /**
     * @throws NoSuchEntityException
     * @throws AuthorizationException
     */
    public function execute(): Json
    {
        $page = (int)$this->_request->getParam('page', 1);
        $searchCriteria = $this->searchCriteriaBuilder
            ->setPageSize(ConversationsManagerInterface::CONVERSATIONS_LIMIT)
            ->setCurrentPage($page)
            ->create();
        $conversations = $this->conversationRepository->getList($searchCriteria)
            ->getItems();

        $result = [];
        foreach ($conversations as $conversation) {
            $conversationId = (int)$conversation->getId();
            $data = $conversation->getData();
            $data['messages'] = array_map(
                fn ($item) => $item->getData(),
                $this->messagesManager->get($conversationId)
            );
            $data['total_unread'] = 0;
            $result[] = $data;
        }

        /** @var Json $jsonResult */
        $jsonResult = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $jsonResult->setData($result);

        return $jsonResult;
    }
}
