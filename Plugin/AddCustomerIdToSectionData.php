<?php

declare(strict_types=1);

namespace MaxStan\LiveChat\Plugin;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\CustomerData\Customer;
use Magento\Customer\Helper\Session\CurrentCustomer;
use MaxStan\LiveChat\Model\CustomerUid;

/**
 * Adds customer entity ID to the customer section data for LiveChat topic subscription.
 */
class AddCustomerIdToSectionData
{
    public function __construct(
        private readonly UserContextInterface $userContext,
        private readonly CustomerUid $customerUid
    ) {
    }

    public function afterGetSectionData(Customer $subject, array $result): array
    {
        $customerId = $this->userContext->getUserId();

        if ($customerId) {
            $result['uid'] = $this->customerUid->get($customerId);
        }

        return $result;
    }
}
