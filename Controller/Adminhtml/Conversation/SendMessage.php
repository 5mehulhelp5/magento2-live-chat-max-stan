<?php

declare(strict_types=1);

namespace MaxStan\LiveChat\Controller\Adminhtml\Conversation;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use MaxStan\LiveChat\Api\MessagesManagerInterface;

/**
 * Admin controller to send a message in a conversation.
 *
 * Mirrors: POST /V1/conversation/:conversationId/message
 */
class SendMessage extends Action implements HttpPostActionInterface
{
    public const string ADMIN_RESOURCE = 'MaxStan_LiveChat::livechat';

    public function __construct(
        Context $context,
        private readonly MessagesManagerInterface $messagesManager
    ) {
        parent::__construct($context);
    }

    public function execute(): Json
    {
        $conversationId = (int)$this->getRequest()->getParam('id');
        $text = $this->getRequest()->getParam('text');

        /** @var Json $jsonResult */
        $jsonResult = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        if ($text === '') {
            $jsonResult->setHttpResponseCode(400);
            $jsonResult->setData(['error' => (string)__('Message text is required.')]);
            return $jsonResult;
        }

        try {
            $message = $this->messagesManager->send($conversationId, $text);
            $jsonResult->setData($message->getData());
        } catch (LocalizedException $e) {
            $jsonResult->setHttpResponseCode(400);
            $jsonResult->setData(['error' => $e->getMessage()]);
        }

        return $jsonResult;
    }
}
