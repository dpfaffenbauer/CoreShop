/**
 * CoreShop RmaBundle Studio Plugin
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
import { serviceIds as saleServiceIds } from '@coreshop/order/src/modules/sales/service-ids'
import type { SaleTabRegistry } from '@coreshop/order/src/modules/sales/registry'
import { ReturnTab } from './modules/returns/ReturnTab'
import i18n from 'i18next'

const plugin: IAbstractPlugin = {
    name: 'coreshop-rma',

    onInit() {
        // Get the SaleTabRegistry from OrderBundle
        const tabRegistry = container.get<SaleTabRegistry>(saleServiceIds.saleTabRegistry)

        const t = i18n.t

        // Register the Returns tab in the left column, after Invoices (priority 40)
        tabRegistry.register('return', {
            key: 'return',
            label: t('coreshop_returns', { defaultValue: 'Returns' }),
            priority: 50,
            position: 'left',
            types: ['order'],
            component: ReturnTab
        })
    },

    onStartup({ moduleSystem }) {
        // No additional modules needed at startup
    }
}

export default plugin
