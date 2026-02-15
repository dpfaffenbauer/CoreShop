/**
 * CoreShop RmaBundle Create Return Modal
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
import { Modal, InputNumber, Table } from 'antd'
import { createStyles } from 'antd-style'
import { useMessage } from '@pimcore/studio-ui-bundle/components'
import { useTranslation } from 'react-i18next'
import { formatCurrency } from '@coreshop/pimcore/src/utils'
import type { ColumnType } from 'antd/es/table'
import { getErrorMessage } from '@coreshop/resource/src/entities'

interface ReturnItem {
  orderItemId: number
  name: string
  price: number
  quantity: number
  quantityReturned: number
  maxToReturn: number
  toReturn: number
}

export interface CreateReturnModalProps {
  open: boolean
  orderId: number
  currencyCode: string
  onSuccess: () => void
  onCancel: () => void
}

export const CreateReturnModal: React.FC<CreateReturnModalProps> = ({
  open,
  orderId,
  currencyCode,
  onSuccess,
  onCancel
}) => {
  const { t } = useTranslation()
  const messageApi = useMessage()
  const { styles } = useCreateReturnModalStyles()
  const [items, setItems] = React.useState<ReturnItem[]>([])
  const [loading, setLoading] = React.useState(false)
  const [loadingItems, setLoadingItems] = React.useState(false)

  // Load processable items
  React.useEffect(() => {
    if (!open) return

    const loadItems = async () => {
      setLoadingItems(true)
      try {
        const response = await fetch(`/pimcore-studio/api/coreshop/order-return/get-return-able-items?id=${orderId}`)
        const data = await response.json()

        if (data.success && data.items && data.items.length > 0) {
          setItems(data.items)
        } else {
          void messageApi.warning(t('coreshop_no_returnable_items', { defaultValue: 'No items available for return' }))
          onCancel()
        }
      } catch (error) {
        void messageApi.error(getErrorMessage(error, 'Failed to load items'))
        onCancel()
      } finally {
        setLoadingItems(false)
      }
    }

    void loadItems()
  }, [open, orderId, onCancel])

  // Handle quantity change
  const handleQuantityChange = (orderItemId: number, value: number | null) => {
    setItems(items.map(item =>
      item.orderItemId === orderItemId
        ? { ...item, toReturn: Math.min(Math.max(value || 0, 0), item.maxToReturn) }
        : item
    ))
  }

  // Handle save
  const handleSave = async () => {
    try {
      const itemsToReturn = items
        .filter(item => item.toReturn > 0)
        .map(item => ({
          orderItemId: item.orderItemId,
          quantity: item.toReturn
        }))

      if (itemsToReturn.length === 0) {
        void messageApi.warning(t('coreshop_select_items_to_return', { defaultValue: 'Please select items to return' }))
        return
      }

      setLoading(true)

      const payload = {
        id: orderId,
        items: itemsToReturn
      }

      const response = await fetch('/pimcore-studio/api/coreshop/order-return/create-return', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      })

      const data = await response.json()

      if (data.success) {
        void messageApi.success(t('coreshop_return_created', { defaultValue: 'Return created successfully' }))
        onSuccess()
      } else {
        void messageApi.error(data.message || t('coreshop_return_creation_failed', { defaultValue: 'Failed to create return' }))
      }
    } catch (error) {
      void messageApi.error(getErrorMessage(error, 'Failed to create return'))
    } finally {
      setLoading(false)
    }
  }

  const columns: Array<ColumnType<ReturnItem>> = [
    {
      title: t('coreshop_product', { defaultValue: 'Product' }),
      dataIndex: 'name',
      key: 'name',
      width: '30%'
    },
    {
      title: t('coreshop_price', { defaultValue: 'Price' }),
      dataIndex: 'price',
      key: 'price',
      width: '15%',
      align: 'right',
      render: (price) => formatCurrency(price, currencyCode)
    },
    {
      title: t('coreshop_quantity', { defaultValue: 'Quantity' }),
      dataIndex: 'quantity',
      key: 'quantity',
      width: '12%',
      align: 'right'
    },
    {
      title: t('coreshop_returned_quantity', { defaultValue: 'Returned' }),
      dataIndex: 'quantityReturned',
      key: 'quantityReturned',
      width: '15%',
      align: 'right'
    },
    {
      title: t('coreshop_to_return', { defaultValue: 'To Return' }),
      dataIndex: 'toReturn',
      key: 'toReturn',
      width: '18%',
      align: 'right',
      render: (value, record) => (
        <InputNumber
          value={value}
          min={0}
          max={record.maxToReturn}
          onChange={(val) => handleQuantityChange(record.orderItemId, val)}
          style={{ width: '100%' }}
        />
      )
    }
  ]

  return (
    <Modal
      open={open}
      title={t('coreshop_create_return_title', { defaultValue: `Create Return for Order (${orderId})` })}
      onCancel={onCancel}
      onOk={handleSave}
      okText={t('coreshop_save', { defaultValue: 'Save' })}
      cancelText={t('coreshop_cancel', { defaultValue: 'Cancel' })}
      width={900}
      confirmLoading={loading}
    >
      <div className={styles.section}>
        <div className={styles.sectionHeader}>
          {t('coreshop_return_items', { defaultValue: 'Return Items' })}
        </div>
      </div>

      <Table
        dataSource={items}
        columns={columns}
        rowKey="orderItemId"
        pagination={false}
        loading={loadingItems}
        size="small"
        className={styles.table}
      />
    </Modal>
  )
}

const useCreateReturnModalStyles = createStyles(({ css, token }) => ({
  section: css`
    margin-bottom: 24px;
  `,
  sectionHeader: css`
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 16px;
    color: ${token.colorText};
  `,
  table: css`
    .ant-table-thead > tr > th {
      background: ${token.colorBgContainer};
      font-weight: 600;
    }
  `
}))
