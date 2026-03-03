<?php

declare(strict_types=1);

namespace MaxStan\LiveChat\Model;

readonly class CustomerUid
{
    public function get(int|string $userId): string
    {
        return base64_encode((string)$userId);
    }
}
