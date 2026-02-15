<?php

declare(strict_types=1);

namespace CoreShop\Component\OrderReturnManagement;

final class ReturnStates
{
    public const string IDENTIFIER = 'coreshop_return';

    public const string STATE_NEW = 'new';

    public const string STATE_CONFIRMED = 'confirmed';

    public const string STATE_CANCELLED = 'cancelled';

    public const string STATE_RECEIVED = 'received';
}
