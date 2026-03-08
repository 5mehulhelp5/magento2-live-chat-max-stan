<?php
declare(strict_types=1);

namespace MaxStan\LiveChat\Test\Integration\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Test\Fixture\Customer as CustomerFixture;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use MaxStan\LiveChat\Api\Data\MessageInterfaceFactory;
use MaxStan\LiveChat\Api\MessageRepositoryInterface;
use MaxStan\LiveChat\Api\MessagesManagerInterface;
use MaxStan\LiveChat\Service\Authorization;
use MaxStan\LiveChat\Service\ConversationsManager;
use MaxStan\LiveChat\Service\MessagesManager;
use MaxStan\LiveChat\Test\Integration\Fixture\Conversation as ConversationFixture;
use MaxStan\LiveChat\Test\Integration\Fixture\Message as MessageFixture;
use MaxStan\LiveChat\Test\Integration\Mock\MercurePublisherSpy;
use MaxStan\LiveChat\Test\Integration\Mock\UserContextStub;
use MaxStan\Mercure\Api\MercurePublisherInterface;
use PHPUnit\Framework\TestCase;

class UserChatManagementTest extends TestCase
{
    private ?ConversationsManager $conversationsManager;
    private ?MessagesManager $messagesManager;
    private ?UserContextStub $userContext;
    private ?MercurePublisherSpy $mercureSpy;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $this->userContext = new UserContextStub();
        $this->mercureSpy = new MercurePublisherSpy();

        $authorization = $objectManager->create(Authorization::class, [
            'userContext' => $this->userContext,
        ]);

        $this->messagesManager = $objectManager->create(MessagesManager::class, [
            'userContext' => $this->userContext,
            'mercurePublisher' => $this->mercureSpy,
            'authorization' => $authorization,
        ]);

        $this->conversationsManager = $objectManager->create(ConversationsManager::class, [
            'userContext' => $this->userContext,
            'mercurePublisher' => $this->mercureSpy,
            'authorization' => $authorization,
            'messagesManager' => $this->messagesManager,
        ]);
    }

    protected function tearDown(): void
    {
        $this->userContext->reset();
        $this->mercureSpy->reset();
    }

    // --- createConversation ---

    #[DbIsolation(true)]
    #[DataFixture(CustomerFixture::class, as: 'customer')]
    public function testCreateConversation(): void
    {
        $fixtures = DataFixtureStorageManager::getStorage();
        $customer = $fixtures->get('customer');
        $customerId = (int)$customer->getId();

        $this->setCustomerContext($customerId);

        $conversation = $this->conversationsManager->create();

        $this->assertEquals($customerId, $conversation->getUserId());
        $this->assertNotEmpty($conversation->getCreatedAt());
        $this->assertNotNull($conversation->getId());
    }

    #[DbIsolation(true)]
    #[DataFixture(CustomerFixture::class, as: 'customer')]
    public function testCreateConversationRequiresAuth(): void
    {
        $this->setGuestContext();

        $this->expectException(LocalizedException::class);
        $this->conversationsManager->create();
    }

    // --- sendMessage ---

    #[
        DbIsolation(true),
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(ConversationFixture::class, ['customer' => '$customer$'], 'conversation'),
    ]
    public function testSendMessage(): void
    {
        $fixtures = DataFixtureStorageManager::getStorage();
        $customerId = (int)$fixtures->get('customer')->getId();
        $conversationId = (int)$fixtures->get('conversation')->getId();

        $this->setCustomerContext($customerId);

        $message = $this->messagesManager->send($conversationId, 'Hello from test');

        $this->assertNotNull($message->getId());
        $this->assertEquals($conversationId, $message->getConversationId());
        $this->assertEquals($customerId, $message->getSenderId());
        $this->assertEquals('Hello from test', $message->getText());
    }

    #[
        DbIsolation(true),
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(ConversationFixture::class, ['customer' => '$customer$'], 'conversation'),
    ]
    public function testSendMessagePublishesToMercure(): void
    {
        $fixtures = DataFixtureStorageManager::getStorage();
        $customerId = (int)$fixtures->get('customer')->getId();
        $conversationId = (int)$fixtures->get('conversation')->getId();

        $this->setCustomerContext($customerId);

        $this->messagesManager->send($conversationId, 'Mercure test');

        $published = $this->mercureSpy->getPublishedMessages();
        $this->assertCount(1, $published);
        $this->assertStringContainsString('livechat/', $published[0]['topic']);
        $this->assertEquals('message:receive', $published[0]['event']);
        $this->assertEquals('Mercure test', $published[0]['data']['text']);
    }

    #[
        DataFixture(CustomerFixture::class, ['email' => 'owner@test.com'], 'customerA'),
        DataFixture(CustomerFixture::class, ['email' => 'other@test.com'], 'customerB'),
        DataFixture(ConversationFixture::class, ['customer' => '$customerA$'], 'conversation'),
    ]
    public function testSendMessageToOtherCustomerConversationThrows(): void
    {
        $fixtures = DataFixtureStorageManager::getStorage();
        $customerBId = (int)$fixtures->get('customerB')->getId();
        $conversationId = (int)$fixtures->get('conversation')->getId();

        $this->setCustomerContext($customerBId);

        $this->expectException(AuthorizationException::class);
        $this->messagesManager->send($conversationId, 'Should fail');
    }

    #[
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(ConversationFixture::class, ['customer' => '$customer$'], 'conversation'),
    ]
    public function testSendMessageRequiresAuth(): void
    {
        $fixtures = DataFixtureStorageManager::getStorage();
        $conversationId = (int)$fixtures->get('conversation')->getId();

        $this->setGuestContext();

        $this->expectException(AuthorizationException::class);
        $this->messagesManager->send($conversationId, 'Should fail');
    }

    // --- getMessages ---

    #[
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(ConversationFixture::class, ['customer' => '$customer$'], 'conversation'),
        DataFixture(MessageFixture::class, ['conversation' => '$conversation$', 'sender' => '$customer$', 'text' => 'Msg 1'], 'm1'),
        DataFixture(MessageFixture::class, ['conversation' => '$conversation$', 'sender' => '$customer$', 'text' => 'Msg 2'], 'm2'),
        DataFixture(MessageFixture::class, ['conversation' => '$conversation$', 'sender' => '$customer$', 'text' => 'Msg 3'], 'm3'),
    ]
    public function testGetMessages(): void
    {
        $fixtures = DataFixtureStorageManager::getStorage();
        $customerId = (int)$fixtures->get('customer')->getId();
        $conversationId = (int)$fixtures->get('conversation')->getId();

        $this->setCustomerContext($customerId);

        $messages = $this->messagesManager->get($conversationId);

        $this->assertCount(3, $messages);

        foreach ($messages as $message) {
            $this->assertNotEmpty($message->getData('sender_name'));
            $this->assertEquals(
                UserContextInterface::USER_TYPE_CUSTOMER,
                $message->getData('sender_type')
            );
        }
    }

    #[
        DbIsolation(true),
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(ConversationFixture::class, ['customer' => '$customer$'], 'conversation'),
    ]
    public function testGetMessagesPagination(): void
    {
        $fixtures = DataFixtureStorageManager::getStorage();
        $customerId = (int)$fixtures->get('customer')->getId();
        $conversationId = (int)$fixtures->get('conversation')->getId();

        $objectManager = Bootstrap::getObjectManager();
        $messageFactory = $objectManager->get(MessageInterfaceFactory::class);
        $messageRepository = $objectManager->get(MessageRepositoryInterface::class);

        for ($i = 0; $i < 30; $i++) {
            $message = $messageFactory->create();
            $message->setConversationId($conversationId);
            $message->setSenderId($customerId);
            $message->setText("Message $i");
            $message->setCreatedAt(sprintf('2026-01-01 %02d:%02d:00', (int)($i / 60), $i % 60));
            $messageRepository->save($message);
        }

        $this->setCustomerContext($customerId);

        $page1 = $this->messagesManager->get($conversationId, 1);
        $this->assertCount(MessagesManagerInterface::MESSAGES_LIMIT, $page1);

        $page2 = $this->messagesManager->get($conversationId, 2);
        $this->assertCount(5, $page2);
    }

    #[
        DataFixture(CustomerFixture::class, ['email' => 'owner2@test.com'], 'customerA'),
        DataFixture(CustomerFixture::class, ['email' => 'other2@test.com'], 'customerB'),
        DataFixture(ConversationFixture::class, ['customer' => '$customerA$'], 'conversation'),
        DataFixture(MessageFixture::class, ['conversation' => '$conversation$', 'sender' => '$customerA$', 'text' => 'Private msg']),
    ]
    public function testGetMessagesFromOtherCustomerThrows(): void
    {
        $fixtures = DataFixtureStorageManager::getStorage();
        $customerBId = (int)$fixtures->get('customerB')->getId();
        $conversationId = (int)$fixtures->get('conversation')->getId();

        $this->setCustomerContext($customerBId);

        $this->expectException(AuthorizationException::class);
        $this->messagesManager->get($conversationId);
    }

    #[
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(ConversationFixture::class, ['customer' => '$customer$'], 'conversation'),
    ]
    public function testGetMessagesRequiresAuth(): void
    {
        $fixtures = DataFixtureStorageManager::getStorage();
        $conversationId = (int)$fixtures->get('conversation')->getId();

        $this->setGuestContext();

        $this->expectException(AuthorizationException::class);
        $this->messagesManager->get($conversationId);
    }

    // --- getConversations ---

    #[
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(ConversationFixture::class, ['customer' => '$customer$'], 'c1'),
        DataFixture(ConversationFixture::class, ['customer' => '$customer$'], 'c2'),
        DataFixture(MessageFixture::class, ['conversation' => '$c1$', 'sender' => '$customer$', 'text' => 'In conv 1']),
        DataFixture(MessageFixture::class, ['conversation' => '$c2$', 'sender' => '$customer$', 'text' => 'In conv 2']),
    ]
    public function testGetConversations(): void
    {
        $fixtures = DataFixtureStorageManager::getStorage();
        $customerId = (int)$fixtures->get('customer')->getId();

        $this->setCustomerContext($customerId);

        $conversations = $this->conversationsManager->get();

        $this->assertCount(2, $conversations);

        foreach ($conversations as $conversation) {
            $this->assertEquals($customerId, $conversation->getUserId());
            $messages = $conversation->getData('messages');
            $this->assertIsArray($messages);
            $this->assertNotEmpty($messages);
        }
    }

    #[
        DataFixture(CustomerFixture::class, ['email' => 'userA@test.com'], 'customerA'),
        DataFixture(CustomerFixture::class, ['email' => 'userB@test.com'], 'customerB'),
        DataFixture(ConversationFixture::class, ['customer' => '$customerA$'], 'convA'),
        DataFixture(ConversationFixture::class, ['customer' => '$customerB$'], 'convB'),
    ]
    public function testGetConversationsReturnsOnlyOwnConversations(): void
    {
        $fixtures = DataFixtureStorageManager::getStorage();
        $customerAId = (int)$fixtures->get('customerA')->getId();
        $convAId = (int)$fixtures->get('convA')->getId();

        $this->setCustomerContext($customerAId);

        $conversations = $this->conversationsManager->get();

        $this->assertCount(1, $conversations);
        $conversation = reset($conversations);
        $this->assertEquals($convAId, (int)$conversation->getId());
    }

    public function testGetConversationsRequiresAuth(): void
    {
        $this->setGuestContext();

        $conversations = $this->conversationsManager->get();

        $this->assertEmpty($conversations);
    }

    // --- Helpers ---

    private function setCustomerContext(int $customerId): void
    {
        $this->userContext->setUserId($customerId);
        $this->userContext->setUserType(UserContextInterface::USER_TYPE_CUSTOMER);
    }

    private function setGuestContext(): void
    {
        $this->userContext->setUserId(null);
        $this->userContext->setUserType(UserContextInterface::USER_TYPE_GUEST);
    }
}
