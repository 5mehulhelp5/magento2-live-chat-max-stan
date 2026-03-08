<?php

declare(strict_types=1);

namespace MaxStan\LiveChat\ViewModel;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

readonly class AdminOptions implements ArgumentInterface
{
    public function __construct(
        private UrlInterface $url,
        private Json $json
    ) {
    }

    public function getEndpointJson(): string
    {
        return $this->json->serialize([
            'index' => $this->url->getUrl('livechat/conversation/index'),
            'messages' => $this->url->getUrl('livechat/conversation/messages'),
            'markAsRead' => $this->url->getUrl('livechat/conversation/markAsRead'),
            'sendMessage' => $this->url->getUrl('livechat/conversation/sendMessage'),
        ]);
    }
}
