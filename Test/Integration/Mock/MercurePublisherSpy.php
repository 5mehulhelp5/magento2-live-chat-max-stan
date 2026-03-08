<?php
declare(strict_types=1);

namespace MaxStan\LiveChat\Test\Integration\Mock;

use MaxStan\Mercure\Api\MercurePublisherInterface;
use Symfony\Component\Mercure\HubInterface;

class MercurePublisherSpy implements MercurePublisherInterface
{
    private array $published = [];

    /**
     * @inheritDoc
     */
    public function getMercureHub(): HubInterface
    {
        throw new \RuntimeException('MercurePublisherSpy does not provide a real hub');
    }

    /**
     * @inheritDoc
     */
    public function publish(array|string $topic, array $data, ?string $event = null): string
    {
        $this->published[] = [
            'topic' => $topic,
            'data' => $data,
            'event' => $event,
        ];

        return 'test-uuid';
    }

    /**
     * @return array<int, array{topic: string|array, data: array, event: ?string}>
     */
    public function getPublishedMessages(): array
    {
        return $this->published;
    }

    public function reset(): void
    {
        $this->published = [];
    }
}
