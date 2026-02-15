/**
 * CoreShop RmaBundle Return Detail Modal
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
import { Modal, Button, Table } from 'antd'
import { createStyles } from 'antd-style'
import { useTranslation } from 'react-i18next'
import { formatDateTime, formatCurrency } from '@coreshop/pimcore/src/utils'
import { useDataObjectHelper } from '@pimcore/studio-ui-bundle/modules/data-object'

interface ReturnItem {
  _itemName: string
  quantity: number
}

interface OrderReturn {
  id: number
  returnDate: number
  returnNumber: string
  totalNet: number
  totalGross: number
  stateInfo: {
    label: string
    state: string
    color: string
  }
  items: ReturnItem[]
}

interface ReturnDetailModalProps {
  open: boolean
  orderReturn: OrderReturn
  currencyCode: string
  onClose: () => void
}

export const ReturnDetailModal: React.FC<ReturnDetailModalProps> = ({
  open,
  orderReturn,
  currencyCode,
  onClose
}) => {
  const { t } = useTranslation()
  const { styles } = useReturnDetailModalStyles()
  const { openDataObject } = useDataObjectHelper()

  // Open return DataObject
  const handleOpenReturn = () => {
    void openDataObject({ config: { id: orderReturn.id } })
    onClose()
  }

  return (
    <Modal
      open={open}
      title={null}
      onCancel={onClose}
      footer={[
        <Button key="ok" type="primary" onClick={onClose}>
          OK
        </Button>
      ]}
      width={600}
    >
      <div className={styles.content}>
        <div className={styles.field}>
          <div className={styles.label}>{t('coreshop_date', { defaultValue: 'Date' })}:</div>
          <div className={styles.value}>{formatDateTime(orderReturn.returnDate)}</div>
        </div>

        <div className={styles.field}>
          <div className={styles.label}>{t('coreshop_return_number', { defaultValue: 'Return Number' })}:</div>
          <div className={styles.value}>{orderReturn.returnNumber}</div>
        </div>

        <div className={styles.field}>
          <div className={styles.label}>{t('coreshop_total_without_tax', { defaultValue: 'Total (excl.)' })}:</div>
          <div className={styles.value}>{formatCurrency(orderReturn.totalNet, currencyCode)}</div>
        </div>

        <div className={styles.field}>
          <div className={styles.label}>{t('coreshop_total', { defaultValue: 'Total' })}:</div>
          <div className={styles.value}><strong>{formatCurrency(orderReturn.totalGross, currencyCode)}</strong></div>
        </div>

        <div className={styles.field}>
          <div className={styles.label}>{t('coreshop_status', { defaultValue: 'Status' })}:</div>
          <div className={styles.value}>
            <span
              style={{
                backgroundColor: orderReturn.stateInfo.color,
                color: '#fff',
                padding: '2px 8px',
                borderRadius: 4,
                fontSize: 12
              }}
            >
              {orderReturn.stateInfo.label}
            </span>
          </div>
        </div>

        <div className={styles.buttonContainer}>
          <Button
            type="default"
            onClick={handleOpenReturn}
          >
            {t('coreshop_open_return', { defaultValue: 'Open Return' })} ({orderReturn.returnNumber})
          </Button>
        </div>

        {/* Products Table */}
        <div className={styles.productsSection}>
          <div className={styles.productsHeader}>{t('coreshop_products', { defaultValue: 'Products' })}</div>
          <Table
            dataSource={orderReturn.items}
            columns={[
              {
                title: t('coreshop_item', { defaultValue: 'Item' }),
                dataIndex: '_itemName',
                key: '_itemName',
                width: '70%'
              },
              {
                title: t('coreshop_quantity', { defaultValue: 'Quantity' }),
                dataIndex: 'quantity',
                key: 'quantity',
                width: '30%'
              }
            ]}
            pagination={false}
            size="small"
            rowKey={(record, index) => index?.toString() || '0'}
          />
        </div>
      </div>
    </Modal>
  )
}

const useReturnDetailModalStyles = createStyles(({ css, token }) => ({
  content: css`
    padding: 16px 0;
  `,
  field: css`
    display: flex;
    padding: 12px 0;
    border-bottom: 1px solid ${token.colorBorder};
  `,
  label: css`
    width: 180px;
    font-weight: 500;
    color: ${token.colorTextSecondary};
    flex-shrink: 0;
  `,
  value: css`
    flex: 1;
    color: ${token.colorText};
  `,
  buttonContainer: css`
    margin: 16px 0;
    padding: 4px 0 16px 0;
    border-bottom: 1px solid ${token.colorBorder};
  `,
  productsSection: css`
    margin-top: 24px;
  `,
  productsHeader: css`
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 12px;
    color: ${token.colorText};
  `
}))
