# RMA (Return Merchandise Authorization)

CoreShop provides a built-in RMA system for managing product returns. The RMA feature is implemented as a standalone
bundle (`RmaBundle`) with its own component (`Component/Rma`), following the same architectural patterns as other
CoreShop features like shipments and invoices.

## Architecture

The RMA system consists of two packages:

- **`coreshop/rma`** (Component): Contains domain logic, models, interfaces, state constants, and transformers
- **`coreshop/rma-bundle`** (Bundle): Provides Symfony/Pimcore integration including DI configuration, controllers,
  form types, workflow definitions, and Pimcore repository implementations

## Data Model

### CoreShopOrderReturn
A Pimcore Data Object representing a return document, linked to an order. Contains:
- `order` - Reference to the original order
- `returnDate` - Date the return was created
- `returnNumber` - Auto-generated unique return number
- `state` - Current state of the return document
- `items` - Collection of return items

### CoreShopOrderReturnItem
A Pimcore Data Object representing a single item within a return. Contains:
- `orderItem` - Reference to the original order item
- `quantity` - Number of items being returned
- `totalNet` / `totalGross` - Return value
- `convertedTotalNet` / `convertedTotalGross` - Return value in converted currency

## State Machines

The RMA system uses two state machines:

### 1. Individual Return Document (`coreshop_return`)
Tracks the state of each return document:

```
[new] --create--> [confirmed] --receive--> [received]
                  [confirmed] --cancel-->  [cancelled]
```

| State | Description |
|-------|------------|
| `new` | Return document has been created |
| `confirmed` | Return has been confirmed/approved |
| `received` | Returned items have been received |
| `cancelled` | Return has been cancelled |

### 2. Aggregate Order Return State (`coreshop_order_return`)
Tracks the overall return state on the order:

```
[new] --request_return--> [requested] --partially_return--> [partially_returned]
                          [requested] --return-->           [returned]
                          [partially_returned] --return-->   [returned]
```

| State | Description |
|-------|------------|
| `new` | No returns requested |
| `requested` | At least one return exists |
| `partially_returned` | Some items have been returned |
| `returned` | All returnable items have been returned |
| `cancelled` | All returns have been cancelled |

## Usage

### Creating a Return

Returns are created through the `OrderReturnController` API:

1. **Get returnable items**: `GET /admin/coreshop/order-return/get-return-able-items?id={orderId}`
2. **Create return**: `POST /admin/coreshop/order-return/create-return` with items and quantities

### Programmatic Usage

```php
// Get the transformer service
$transformer = $container->get(OrderToReturnTransformer::class);

// Create a new return document
$return = $returnFactory->createNew();
$return->setState(ReturnStates::STATE_NEW);

// Transform order to return with specific items
$itemsToReturn = [
    ['orderItemId' => 123, 'quantity' => 2],
    ['orderItemId' => 456, 'quantity' => 1],
];

$return = $transformer->transform($order, $return, $itemsToReturn);
```

### State Transitions

```php
// Apply state transitions
$stateMachineManager = $container->get(StateMachineManagerInterface::class);

// Transition individual return document
$workflow = $stateMachineManager->get($return, 'coreshop_return');
$workflow->apply($return, ReturnTransitions::TRANSITION_CONFIRM);
$workflow->apply($return, ReturnTransitions::TRANSITION_RECEIVE);

// The aggregate order state is automatically resolved by OrderReturnStateResolver
```

## Dependencies

The RMA Bundle depends on:
- `coreshop/order-bundle` - For order document interfaces and base classes
- `coreshop/resource-bundle` - For generic CRUD and Pimcore model support
- `coreshop/workflow-bundle` - For state machine management
- `coreshop/money-bundle` - For money field types
