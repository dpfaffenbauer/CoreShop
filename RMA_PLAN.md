# RMA Feature - Implementierungsplan

## Übersicht

Eigenständiges Return Management (RMA) als **separates Bundle + Component**, analog zu ShippingBundle/Component.
Das RMA-System erlaubt es, Retouren für Bestellungen zu erstellen, mit eigenem State Machine und Pimcore Data Objects.

## Architektur

```
src/CoreShop/
├── Component/Rma/                    # Domain-Logik (framework-unabhängig)
│   ├── Model/
│   │   ├── OrderReturnInterface.php      # extends OrderDocumentInterface
│   │   ├── OrderReturn.php               # Abstract Pimcore Model
│   │   ├── OrderReturnItemInterface.php  # extends OrderDocumentItemInterface
│   │   └── OrderReturnItem.php           # Abstract Pimcore Model
│   ├── ReturnStates.php                  # Einzelne Return-Dokument States (new, confirmed, cancelled, received)
│   ├── ReturnTransitions.php             # Einzelne Return-Dokument Transitions
│   ├── OrderReturnStates.php             # Aggregierter Return-State auf der Order (new, requested, partially_returned, returned, cancelled)
│   ├── OrderReturnTransitions.php        # Aggregierte Transitions
│   ├── Transformer/
│   │   ├── OrderToReturnTransformer.php
│   │   └── OrderItemToReturnItemTransformer.php
│   ├── Processable/
│   │   └── (nutzt ProcessableOrderItems aus dem Order-Component)
│   └── composer.json                     # depends on: coreshop/order, coreshop/resource, coreshop/pimcore
│
├── Component/Core/Model/                 # Core-Layer (Erweiterungspunkt)
│   ├── OrderReturnInterface.php
│   ├── OrderReturn.php
│   ├── OrderReturnItemInterface.php
│   └── OrderReturnItem.php
│
└── Bundle/RmaBundle/                     # Symfony/Pimcore Integration
    ├── CoreShopRmaBundle.php             # extends AbstractResourceBundle
    ├── DependencyInjection/
    │   ├── Configuration.php             # Pimcore-Models + Permissions
    │   └── CoreShopRmaExtension.php      # extends AbstractModelExtension
    ├── Controller/
    │   └── OrderReturnController.php
    ├── Form/Type/
    │   ├── OrderReturnCreationType.php
    │   └── OrderReturnCreationItemsType.php
    ├── Pimcore/Repository/
    │   ├── OrderReturnRepository.php
    │   └── OrderReturnItemRepository.php
    ├── StateResolver/
    │   └── OrderReturnStateResolver.php
    ├── Resources/
    │   ├── config/
    │   │   ├── services.yml              # Hauptdatei, importiert sub-files
    │   │   ├── services/
    │   │   │   ├── order_return.yml      # Transformer, Number Generator, Processable
    │   │   │   ├── workflow.yml          # Marking Stores
    │   │   │   └── forms.yml             # Form Type Services
    │   │   ├── pimcore/
    │   │   │   ├── workflow/
    │   │   │   │   ├── coreshop_return.yml         # State Machine für einzelne Returns
    │   │   │   │   └── coreshop_order_return.yml   # Aggregierter State auf Order
    │   │   │   └── config.yml
    │   │   └── serializer/               # JMS Serializer (falls Doctrine-Entities)
    │   ├── install/pimcore/classes/
    │   │   ├── CoreShopOrderReturn.json
    │   │   └── CoreShopOrderReturnItem.json
    │   └── translations/
    │       ├── studio.en.yml
    │       └── studio.de.yml
    └── composer.json                     # depends on: coreshop/rma, coreshop/order-bundle, coreshop/resource-bundle, coreshop/workflow-bundle
```

## State Machine Design

### 1. Einzelnes Return-Dokument (`coreshop_return`)

```
[new] --create--> [confirmed] --receive--> [received]
                  [confirmed] --cancel--> [cancelled]
```

- `new`: Retoure angelegt
- `confirmed`: Retoure bestätigt/genehmigt
- `received`: Ware zurückerhalten
- `cancelled`: Retoure storniert

### 2. Aggregierter Order-State (`coreshop_order_return`)

```
[new] --request_return--> [requested]
[requested] --partially_return--> [partially_returned]
[requested] --return--> [returned]
[partially_returned] --return--> [returned]
[requested] --cancel--> [cancelled]
```

- `new`: Keine Retoure angefragt
- `requested`: Mindestens eine Retoure existiert
- `partially_returned`: Teilweise retourniert
- `returned`: Vollständig retourniert
- `cancelled`: Alle Retouren storniert

## Pimcore Data Objects

### CoreShopOrderReturn
- `order` (coreShopRelation → coreshop.order)
- `returnDate` (date)
- `returnNumber` (input, nicht editierbar)
- `state` (input, nicht editierbar)
- `items` (coreShopRelations → coreshop.order_return_item)

### CoreShopOrderReturnItem
- `orderItem` (coreShopRelation → coreshop.order_item)
- `quantity` (numeric)
- `totalNet` (coreShopMoney)
- `totalGross` (coreShopMoney)
- `convertedTotalNet` (coreShopMoney)
- `convertedTotalGross` (coreShopMoney)

## OrderInterface Erweiterung

Das `OrderInterface` im Order-Component benötigt `returnState` getter/setter.
Die `CoreShopOrder.json` Pimcore-Klasse muss um ein `returnState` Feld erweitert werden.

## Aufräumarbeiten

Die 12 bereits erstellten Dateien im `Component/Order/` und `Component/Core/Model/` werden gelöscht und im neuen `Component/Rma/` bzw. angepasst neu erstellt.

## Implementierungsreihenfolge

1. Bestehende Dateien aufräumen (löschen)
2. Component/Rma erstellen (Model, States, Transformers, composer.json)
3. Component/Core/Model Erweiterungen
4. Bundle/RmaBundle Grundstruktur (Bundle-Klasse, DI, Configuration)
5. Pimcore Class Definitions (JSON)
6. Service Definitions (YAML)
7. Workflow Definitions (YAML)
8. Form Types
9. Controller
10. State Resolver
11. OrderInterface + CoreShopOrder.json Update (returnState)
12. Root composer.json Update (replace-Sektion)
13. Translations
14. Dokumentation
