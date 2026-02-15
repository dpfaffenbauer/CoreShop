<?php

declare(strict_types=1);

/*
 * CoreShop
 *
 * This source file is available under the terms of the
 * CoreShop Commercial License (CCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CoreShop GmbH (https://www.coreshop.com)
 * @license    CoreShop Commercial License (CCL)
 *
 */

namespace CoreShop\Component\Subscription\Model;

final class SubscriptionStates
{
    public const STATE_TRIAL = 'trial';

    public const STATE_ACTIVE = 'active';

    public const STATE_PAUSED = 'paused';

    public const STATE_PAST_DUE = 'past_due';

    public const STATE_CANCELLED = 'cancelled';

    public const STATE_EXPIRED = 'expired';

    public const STATE_COMPLETED = 'completed';
}
