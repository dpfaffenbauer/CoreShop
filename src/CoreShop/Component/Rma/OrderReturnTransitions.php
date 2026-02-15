<?php

declare(strict_types=1);

namespace CoreShop\Component\Rma;

final class OrderReturnTransitions
{
    public const string IDENTIFIER = 'coreshop_order_return';

    public const string TRANSITION_REQUEST_RETURN = 'request_return';

    public const string TRANSITION_PARTIALLY_RETURN = 'partially_return';

    public const string TRANSITION_CANCEL = 'cancel';

    public const string TRANSITION_RETURN = 'return';
}
