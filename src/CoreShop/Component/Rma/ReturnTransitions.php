<?php

declare(strict_types=1);

namespace CoreShop\Component\Rma;

final class ReturnTransitions
{
    public const string IDENTIFIER = 'coreshop_return';

    public const string TRANSITION_CREATE = 'create';

    public const string TRANSITION_CONFIRM = 'confirm';

    public const string TRANSITION_RECEIVE = 'receive';

    public const string TRANSITION_CANCEL = 'cancel';
}
