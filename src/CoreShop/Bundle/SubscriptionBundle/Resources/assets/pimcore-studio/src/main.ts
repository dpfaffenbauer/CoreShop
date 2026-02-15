/**
 * CoreShop SubscriptionBundle Studio Plugin
 *
 * This source file is available under the terms of the
 * CoreShop Commercial License (CCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CoreShop GmbH (https://www.coreshop.com)
 * @license    CoreShop Commercial License (CCL)
 */

import { IAbstractPlugin, container } from '@pimcore/studio-ui-bundle'
import { serviceIds } from '@pimcore/studio-ui-bundle/app'
import type { WidgetRegistry } from '@pimcore/studio-ui-bundle/modules/widget-manager'
import { SubscriptionListingBuildersModule } from './modules/listing-builders'
import { SubscriptionList } from './modules/subscriptions/SubscriptionList'

const plugin: IAbstractPlugin = {
    name: 'coreshop-subscription',

    onInit() {
        // No dynamic types or registries needed for subscriptions
    },

    onStartup({ moduleSystem }) {
        moduleSystem.registerModule(SubscriptionListingBuildersModule)

        // Register widgets
        const widgets = container.get<WidgetRegistry>(serviceIds.widgetManager)

        widgets.registerWidget({
            name: 'coreshop-subscription-manager',
            component: SubscriptionList
        })
    }
}

export default plugin
