/**
 * CoreShop OrderReturnManagementBundle - Create Return Button
 *
 * This source file is available under the terms of the
 * CoreShop Commercial License (CCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CoreShop GmbH (https://www.coreshop.com)
 * @license    CoreShop Commercial License (CCL)
 */

import React from 'react'
import { Button } from 'antd'
import { PlusOutlined } from '@ant-design/icons'
import { useTranslation } from 'react-i18next'
import { useSaleContext } from '@coreshop/order/src/modules/sales/context/SaleActionsContext'

export const CreateReturnButton: React.FC = () => {
  const { t } = useTranslation()
  const { sale, openAction } = useSaleContext()

  // Don't show button if return creation is not allowed
  const saleData = sale as any
  if (!saleData?.returnCreationAllowed) {
    return null
  }

  return (
    <Button
      type="default"
      icon={<PlusOutlined />}
      onClick={() => openAction('createReturn')}
    >
      {t('coreshop_create_return', { defaultValue: 'Create Return' })}
    </Button>
  )
}
