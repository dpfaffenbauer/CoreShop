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

final class SubscriptionTransitions
{
    public const IDENTIFIER = 'coreshop_subscription';

    public const TRANSITION_ACTIVATE = 'activate';

    public const TRANSITION_PAUSE = 'pause';

    public const TRANSITION_RESUME = 'resume';

    public const TRANSITION_PAYMENT_FAILED = 'payment_failed';

    public const TRANSITION_PAYMENT_RECOVERED = 'payment_recovered';

    public const TRANSITION_CANCEL = 'cancel';

    public const TRANSITION_EXPIRE = 'expire';

    public const TRANSITION_COMPLETE = 'complete';
}
