/**
 * CoreShop SubscriptionBundle Listing Builders Module
 *
 * This module creates a custom listing builder for Subscriptions
 * by copying the standard DataObject listing builder and customizing it.
 *
 * This source file is available under the terms of the
 * CoreShop Commercial License (CCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CoreShop GmbH (https://www.coreshop.com)
 * @license    CoreShop Commercial License (CCL)
 */

import { type AbstractModule, container } from '@pimcore/studio-ui-bundle'
import type { ListingBuilder } from '@pimcore/studio-ui-bundle/modules/element'
import type { ClassDefinitionSelectionDecoratorConfig } from '@pimcore/studio-ui-bundle/modules/data-object'
import { ResourceConfigProvider } from '@coreshop/resource/src/config/ConfigProvider'
import { createPresetFilterDecorator } from '@coreshop/pimcore/src/modules/grid/decorators/PresetFilterDecorator'

export const SubscriptionListingBuildersModule: AbstractModule = {
  async onInit(): Promise<void> {
    try {
      const dataObjectListingBuilder = container.get<ListingBuilder>('DataObject/Listing/Builder')
      const configProvider = new ResourceConfigProvider()

      // Subscription Listing Builder
      container.bind('CoreShop/Subscription/Listing/Builder').toConstantValue(dataObjectListingBuilder.copy())
      const subscriptionListingBuilder = container.get<ListingBuilder>('CoreShop/Subscription/Listing/Builder')

      // Get allowed classes for coreshop.subscription from stack
      const subscriptionClasses = await configProvider.getAllowedClasses('coreshop.subscription')

      if (subscriptionClasses.length > 0) {
        const classDefinitionSelectionDecorator = subscriptionListingBuilder.getDecorator('classDefinitionSelection')

        if (classDefinitionSelectionDecorator !== undefined) {
          const classDefinitionSelectionDecoratorConfig: ClassDefinitionSelectionDecoratorConfig = {
            ...classDefinitionSelectionDecorator.config,
            classRestriction: subscriptionClasses.map(className => ({ classes: className }))
          }

          subscriptionListingBuilder.overrideDecorator({
            name: 'classDefinitionSelection',
            config: classDefinitionSelectionDecoratorConfig
          })
        }
      }

      // Add preset filter decorator
      subscriptionListingBuilder.addDecorator({
        name: 'presetFilter',
        decorator: createPresetFilterDecorator({ listType: 'coreshop_subscription' })
      })
    } catch (err) {
      console.error('[CoreShop] Failed to initialize subscription listing builders:', err)
    }
  }
}
