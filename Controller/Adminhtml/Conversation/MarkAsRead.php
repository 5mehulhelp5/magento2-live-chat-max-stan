<?php

declare(strict_types=1);

namespace MaxStan\LiveChat\Controller\Adminhtml\Conversation;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use MaxStan\LiveChat\Api\ConversationsManagerInterface;

class MarkAsRead extends Action implements HttpPostActionInterface
{
    public const string ADMIN_RESOURCE = 'MaxStan_LiveChat::livechat';

    public function __construct(
        Context $context,
        private readonly ConversationsManagerInterface $conversationsManager,
    ) {
        parent::__construct($context);
    }

    public function execute(): Json
    {
        $conversationId = (int)$this->getRequest()->getParam('id');
        /** @var Json $jsonResult */
        $jsonResult = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $this->conversationsManager->markAsRead($conversationId);
            $jsonResult->setData(['success' => true]);
        } catch (LocalizedException $e) {
            $jsonResult->setHttpResponseCode(400);
            $jsonResult->setData(['error' => $e->getMessage()]);
        }

        return $jsonResult;
    }
}
