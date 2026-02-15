<?php

declare(strict_types=1);

namespace CoreShop\Component\Rma;

final class OrderReturnStates
{
    public const string STATE_NEW = 'new';

    public const string STATE_REQUESTED = 'requested';

    public const string STATE_PARTIALLY_RETURNED = 'partially_returned';

    public const string STATE_RETURNED = 'returned';

    public const string STATE_CANCELLED = 'cancelled';
}
