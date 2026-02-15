/**
 * CoreShop OrderReturnManagementBundle Return Tab
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
import { Table, Button, Card, Empty } from 'antd'
import { createStyles } from 'antd-style'
import { FolderOpenOutlined, PlusOutlined } from '@ant-design/icons'
import { useTranslation } from 'react-i18next'
import { formatDateTime, formatCurrency, getCurrencyCode } from '@coreshop/pimcore/src/utils'
import type { ColumnType } from 'antd/es/table'
import type { SaleTabProps } from '@coreshop/order/src/modules/sales/registry'
import { StateChangeModal } from '@coreshop/order/src/modules/sales/components'
import { useSaleContext } from '@coreshop/order/src/modules/sales/context/SaleActionsContext'
import { CreateReturnModal, ReturnDetailModal, CreateReturnButton } from './components'

interface StateInfo {
  label: string
  state: string
  color: string
}

interface Transition {
  label: string
  transition: string
  color: string
}

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
  stateInfo: StateInfo
  transitions: Transition[]
  items: ReturnItem[]
}

export const ReturnTab: React.FC<SaleTabProps> = () => {
  const { t } = useTranslation()
  const { sale, onReload, isActionOpen, openAction, closeAction, buttonRegistry } = useSaleContext()
  const { styles } = useReturnTabStyles()
  const [stateChangeReturn, setStateChangeReturn] = React.useState<OrderReturn | null>(null)
  const [detailReturn, setDetailReturn] = React.useState<OrderReturn | null>(null)

  if (!sale) return null

  const returns = ((sale as any).returns || []) as OrderReturn[]

  // Register button in toolbar
  React.useEffect(() => {
    if ((sale as any)?.returnCreationAllowed) {
      buttonRegistry.add('createReturn', CreateReturnButton, 40)
      return () => buttonRegistry.remove('createReturn')
    }
  }, [buttonRegistry, sale])

  const currencyCode = getCurrencyCode(sale.currency)

  const columns: Array<ColumnType<OrderReturn>> = [
    {
      title: t('coreshop_date', { defaultValue: 'Date' }),
      dataIndex: 'returnDate',
      key: 'returnDate',
      width: 160,
      render: (date) => formatDateTime(date)
    },
    {
      title: t('coreshop_return_number', { defaultValue: 'Return Number' }),
      dataIndex: 'returnNumber',
      key: 'returnNumber',
      width: 140,
      render: (number) => number || '-'
    },
    {
      title: t('coreshop_total_without_tax', { defaultValue: 'Total (excl.)' }),
      dataIndex: 'totalNet',
      key: 'totalNet',
      width: 130,
      align: 'right',
      render: (amount) => formatCurrency(amount, currencyCode)
    },
    {
      title: t('coreshop_total', { defaultValue: 'Total' }),
      dataIndex: 'totalGross',
      key: 'totalGross',
      width: 130,
      align: 'right',
      render: (amount) => <strong>{formatCurrency(amount, currencyCode)}</strong>
    },
    {
      title: t('coreshop_status', { defaultValue: 'Status' }),
      key: 'state',
      width: 120,
      align: 'center',
      render: (_, record) => {
        const hasTransitions = record.transitions && record.transitions.length > 0
        return (
          <Button
            style={{
              backgroundColor: record.stateInfo.color,
              borderColor: record.stateInfo.color,
              color: '#fff',
              cursor: hasTransitions ? 'pointer' : 'default',
              minWidth: 90
            }}
            size="small"
            onClick={() => {
              if (hasTransitions) {
                setStateChangeReturn(record)
              }
            }}
            disabled={!hasTransitions}
          >
            {record.stateInfo.label}
          </Button>
        )
      }
    },
    {
      title: '',
      key: 'actions',
      width: 50,
      align: 'center',
      render: (_, record) => (
        <Button
          type="text"
          icon={<FolderOpenOutlined />}
          size="small"
          title={t('coreshop_open_return_details', { defaultValue: 'Open Return Details' })}
          onClick={() => setDetailReturn(record)}
        />
      )
    }
  ]

  return (
    <>
      <Card
        title={t('coreshop_returns', { defaultValue: 'Returns' })}
        className={styles.card}
        extra={
          (sale as any).returnCreationAllowed && (
            <Button
              type="text"
              icon={<PlusOutlined style={{ color: '#52c41a', fontSize: 20 }} />}
              title={t('coreshop_order_add_return', { defaultValue: 'Add Return' })}
              onClick={() => openAction('createReturn')}
            />
          )
        }
      >
        {returns.length === 0 ? (
          <Empty description={t('coreshop_no_returns', { defaultValue: 'No returns recorded' })} image={Empty.PRESENTED_IMAGE_SIMPLE} />
        ) : (
          <Table
            dataSource={returns}
            columns={columns}
            rowKey="id"
            pagination={false}
            className={styles.table}
            size="small"
          />
        )}
      </Card>

      {/* State Change Modal */}
      {stateChangeReturn && (
        <StateChangeModal
          open={true}
          title={t('coreshop_change_return_state', { defaultValue: 'Change Return State' })}
          description={t('coreshop_change_return_state_description', { defaultValue: 'Select a transition to apply to this return' })}
          transitions={stateChangeReturn.transitions}
          url="/pimcore-studio/api/coreshop/order-return/update-return-state"
          id={stateChangeReturn.id}
          onSuccess={() => {
            setStateChangeReturn(null)
            onReload()
          }}
          onCancel={() => setStateChangeReturn(null)}
        />
      )}

      {/* Return Detail Modal */}
      {detailReturn && (
        <ReturnDetailModal
          open={true}
          orderReturn={detailReturn}
          currencyCode={currencyCode}
          onClose={() => setDetailReturn(null)}
        />
      )}

      {/* Create Return Modal */}
      {isActionOpen('createReturn') && (
        <CreateReturnModal
          open={true}
          orderId={(sale as any).id}
          currencyCode={
            typeof sale.currency === 'object' && sale.currency?.isoCode
              ? sale.currency.isoCode
              : typeof sale.currency === 'string'
                ? sale.currency
                : 'EUR'
          }
          onSuccess={() => {
            closeAction('createReturn')
            onReload()
          }}
          onCancel={() => closeAction('createReturn')}
        />
      )}
    </>
  )
}

const useReturnTabStyles = createStyles(({ css, token }) => ({
  card: css`
    .ant-card-head {
      background: ${token.colorBgContainer};
      border-bottom: 1px solid ${token.colorBorderSecondary};
    }
  `,
  table: css`
    .ant-table-thead > tr > th {
      background: ${token.colorBgContainer};
      font-weight: 600;
    }
  `
}))
