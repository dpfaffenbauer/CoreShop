# SubscriptionBundle - Architekturplan (v2)

## 1. Konzept-Ubersicht

Das SubscriptionBundle ermoglicht wiederkehrende Bestellungen (Abonnements) in CoreShop.

**Kernidee:** Ein SubscriptionPlan ist ein **Purchasable** - er wird uber den normalen
Cart/Checkout-Flow gekauft. Payments laufen komplett uber das bestehende CoreShop Payment-System.
Bei jeder Verlangerung wird eine neue Order mit dem SubscriptionPlan als Purchasable erstellt.

**Kernfunktionen:**
- SubscriptionPlan als Pimcore DataObject mit `PurchasableInterface`
- Normaler Cart → Checkout → Payment Flow fur Erst-Kauf und Verlangerungen
- Flexible Abrechnungszyklen (wochentlich, monatlich, quartal, jahrlich)
- Testphasen (Trial Periods)
- Automatische Verlangerung: Cron erstellt neue Order + Payment
- Pausieren / Fortsetzen / Kundigen von Abos
- Rule Engine fur Abo-spezifische Rabatte

---

## 2. Architektur (Component + Bundle Pattern)

### 2.1 Component: `src/CoreShop/Component/Subscription/`

```
Component/Subscription/
├── Model/
│   ├── SubscriptionPlanInterface.php        # Extends PurchasableInterface
│   ├── SubscriptionPlan.php                 # Abstract Pimcore Model
│   ├── SubscriptionInterface.php            # Aktives Abo (Pimcore DataObject)
│   ├── Subscription.php                     # Abstract Pimcore Model
│   ├── BillingCycle.php                     # Enum: weekly, monthly, quarterly, yearly
│   └── SubscriptionStates.php               # State-Konstanten
├── Repository/
│   ├── SubscriptionRepositoryInterface.php
│   └── SubscriptionPlanRepositoryInterface.php
├── Factory/
│   └── SubscriptionFactoryInterface.php
├── Calculator/
│   ├── SubscriptionPlanPriceCalculatorInterface.php
│   ├── SubscriptionPlanRetailPriceCalculatorInterface.php
│   ├── SubscriptionPlanDiscountPriceCalculatorInterface.php
│   └── SubscriptionPlanDiscountCalculatorInterface.php
├── Processor/
│   ├── RenewalProcessorInterface.php        # Verlangerungslogik
│   └── SubscriptionOrderCreatorInterface.php # Order aus Abo erstellen
├── Resolver/
│   └── NextBillingDateResolverInterface.php
└── Checker/
    └── EligibilityCheckerInterface.php      # Pruft ob Abo verlangert werden kann
```

### 2.2 Bundle: `src/CoreShop/Bundle/SubscriptionBundle/`

```
Bundle/SubscriptionBundle/
├── CoreShopSubscriptionBundle.php
├── DependencyInjection/
│   ├── Configuration.php
│   ├── CoreShopSubscriptionExtension.php
│   └── Compiler/
│       ├── SubscriptionRuleConditionPass.php
│       └── SubscriptionRuleActionPass.php
├── Controller/
│   ├── SubscriptionPlanController.php       # Admin CRUD
│   └── SubscriptionController.php           # Admin: State-Aktionen
├── Command/
│   ├── ProcessRenewalsCommand.php           # coreshop:subscription:process-renewals
│   └── ExpireSubscriptionsCommand.php       # coreshop:subscription:expire
├── Calculator/
│   ├── SubscriptionPlanPriceCalculator.php
│   ├── SubscriptionPlanRetailPriceCalculator.php
│   ├── SubscriptionPlanDiscountPriceCalculator.php
│   └── SubscriptionPlanDiscountCalculator.php
├── Processor/
│   ├── RenewalProcessor.php
│   └── SubscriptionOrderCreator.php
├── Resolver/
│   └── NextBillingDateResolver.php
├── Checker/
│   └── EligibilityChecker.php
├── EventListener/
│   └── OrderCompleteListener.php            # Erstellt Subscription nach Checkout
├── Pimcore/
│   └── Repository/
│       ├── SubscriptionPlanRepository.php
│       └── SubscriptionRepository.php
├── Form/
│   └── Type/
│       └── SubscriptionRuleType.php
├── Resources/
│   ├── config/
│   │   ├── services.yml
│   │   ├── services/
│   │   │   ├── purchasable.yml              # Price-Calculator Registrierung
│   │   │   ├── commands.yml
│   │   │   ├── processors.yml
│   │   │   ├── listeners.yml
│   │   │   └── subscription-rules.yml
│   │   └── pimcore/
│   │       ├── config.yml
│   │       ├── routing.yml
│   │       └── workflow/
│   │           └── coreshop_subscription.yml
│   ├── translations/
│   │   ├── studio.en.yaml
│   │   └── studio.de.yaml
│   └── assets/pimcore-studio/
│       └── src/                             # React UI (siehe Abschnitt 6)
└── composer.json
```

---

## 3. Datenmodell

### 3.1 SubscriptionPlan (Pimcore DataObject, implements PurchasableInterface)

Der SubscriptionPlan ist ein **Pimcore DataObject** und implementiert `PurchasableInterface`.
Dadurch kann er wie ein Produkt in den Cart gelegt und uber den normalen Checkout gekauft werden.

```php
interface SubscriptionPlanInterface extends
    PurchasableInterface,          // getName(), getId(), getWholesaleBuyingPrice()
    PimcoreModelInterface,         // Pimcore DataObject
    ToggleableInterface,           // active/inactive
    TimestampableInterface         // creationDate, modificationDate
{
    // Billing Configuration
    public function getBillingCycle(): ?string;         // weekly|monthly|quarterly|yearly
    public function getBillingInterval(): ?int;         // alle X Zyklen
    public function getTrialPeriodDays(): ?int;         // Trial in Tagen (nullable)
    public function getMaxCycles(): ?int;               // Max Zyklen (null = unbegrenzt)
    public function getAutoRenew(): bool;               // Automatische Verlangerung

    // Pricing (fur PurchasableInterface - eigene Price Calculators)
    public function getPrice(): ?int;                   // Preis pro Zyklus in Cent
    public function getCurrency(): ?CurrencyInterface;
    public function getStore(): ?StoreInterface;

    // Display
    public function getName(?string $language = null): ?string;
    public function getDescription(?string $language = null): ?string;
}
```

**Pimcore Class Definition Felder:**

| Feld | Typ | Beschreibung |
|------|-----|-------------|
| name | Localizedfields (Input) | Plan-Name ("Monatsabo Premium") |
| description | Localizedfields (Textarea) | Beschreibung |
| billingCycle | Select | weekly / monthly / quarterly / yearly |
| billingInterval | Numeric (int) | Alle X Zyklen (Default: 1) |
| price | Numeric (int) | Preis pro Zyklus in Cent |
| currency | CoreShopCurrency | Wahrung |
| store | CoreShopStore | Store-Zuordnung |
| trialPeriodDays | Numeric (int, nullable) | Testphase in Tagen |
| maxCycles | Numeric (int, nullable) | Max Zyklen (null = unbegrenzt) |
| autoRenew | Checkbox | Automatische Verlangerung |
| active | Checkbox | Aktiv/Inaktiv |
| wholesaleBuyingPrice | CoreShopMoney (nullable) | Einkaufspreis |

### 3.2 Subscription (Pimcore DataObject)

Die aktive Abo-Instanz eines Kunden. Wird nach erfolgreichem Checkout erstellt.

```php
interface SubscriptionInterface extends
    PimcoreModelInterface,
    StoreAwareInterface,
    CurrencyAwareInterface,
    TimestampableInterface
{
    public function getCustomer(): ?CustomerInterface;
    public function getSubscriptionPlan(): ?SubscriptionPlanInterface;
    public function getPaymentProvider(): ?PaymentProviderInterface;

    // State
    public function getState(): ?string;
    public function setState(?string $state);

    // Billing Tracking
    public function getStartDate(): ?\DateTimeInterface;
    public function getNextBillingDate(): ?\DateTimeInterface;
    public function getEndDate(): ?\DateTimeInterface;
    public function getTrialEndDate(): ?\DateTimeInterface;
    public function getLastPaymentDate(): ?\DateTimeInterface;
    public function getCompletedCycles(): int;

    // Order-Referenzen (alle Orders die aus diesem Abo entstanden sind)
    public function getOrders(): array;                 // Relation to Many → Order

    // Cancellation
    public function getCancellationDate(): ?\DateTimeInterface;
    public function getCancellationReason(): ?string;
}
```

**Pimcore Class Definition Felder:**

| Feld | Typ | Beschreibung |
|------|-----|-------------|
| customer | ManyToOneRelation → Customer | Abonnent |
| subscriptionPlan | ManyToOneRelation → SubscriptionPlan | Verknupfter Plan |
| paymentProvider | CoreShopPaymentProvider | Zahlungsart |
| store | CoreShopStore | Shop |
| currency | CoreShopCurrency | Wahrung |
| state | Input (managed by workflow) | Workflow-State |
| startDate | Datetime | Abo-Beginn |
| nextBillingDate | Datetime | Nachste Abrechnung |
| endDate | Datetime (nullable) | Abo-Ende |
| trialEndDate | Datetime (nullable) | Trial-Ende |
| lastPaymentDate | Datetime (nullable) | Letzte erfolgreiche Zahlung |
| completedCycles | Numeric (int) | Abgerechnete Zyklen |
| orders | ManyToManyRelation → Order | Alle erzeugten Orders |
| initialOrder | ManyToOneRelation → Order | Die erste Order (Kauf) |
| cancellationDate | Datetime (nullable) | Kundigungsdatum |
| cancellationReason | Textarea (nullable) | Kundigungsgrund |

### 3.3 Kein separates SubscriptionPayment-Entity

Da Payments komplett uber das CoreShop Order/Payment-System laufen, brauchen wir
**kein eigenes SubscriptionPayment-Entity**. Stattdessen:

- Jede Verlangerung erzeugt eine **Order** (mit dem SubscriptionPlan als Purchasable)
- Jede Order hat ihre eigenen **Payments** (uber `coreshop_payment` / `coreshop_order_payment`)
- Die Subscription speichert eine **Relation zu allen Orders** (`orders`-Feld)
- Zahlungshistorie = Liste aller Orders der Subscription + deren Payment-Status

---

## 4. Purchasable Integration

### 4.1 Price Calculators

Der SubscriptionPlan braucht eigene Price Calculators, registriert im
Purchasable-Calculator-Registry:

```php
// SubscriptionPlanPriceCalculator
class SubscriptionPlanPriceCalculator implements PurchasablePriceCalculatorInterface
{
    public function getPrice(PurchasableInterface $purchasable, array $context, bool $includingDiscounts = false): int
    {
        if (!$purchasable instanceof SubscriptionPlanInterface) {
            throw new NoPurchasablePriceFoundException(__CLASS__);
        }

        $price = $purchasable->getPrice();

        if ($includingDiscounts) {
            // Subscription-Rule-basierte Rabatte anwenden
            // (z.B. basierend auf Cycle-Count im Context)
        }

        return $price;
    }
}
```

**Service Registration (purchasable.yml):**

```yaml
services:
  CoreShop\Bundle\SubscriptionBundle\Calculator\SubscriptionPlanPriceCalculator:
    tags:
      - { name: coreshop.order.purchasable.price_calculator, type: coreshop_subscription_plan }

  CoreShop\Bundle\SubscriptionBundle\Calculator\SubscriptionPlanRetailPriceCalculator:
    tags:
      - { name: coreshop.order.purchasable.retail_price_calculator, type: coreshop_subscription_plan }

  CoreShop\Bundle\SubscriptionBundle\Calculator\SubscriptionPlanDiscountPriceCalculator:
    tags:
      - { name: coreshop.order.purchasable.discount_price_calculator, type: coreshop_subscription_plan }

  CoreShop\Bundle\SubscriptionBundle\Calculator\SubscriptionPlanDiscountCalculator:
    tags:
      - { name: coreshop.order.purchasable.discount_calculator, type: coreshop_subscription_plan }
```

### 4.2 Checkout Flow (Erst-Kauf)

```
Kunde:
  1. Legt SubscriptionPlan in den Cart
     → CartModifier::addToList() (normaler Flow)
     → OrderItemFactory::createWithPurchasable(subscriptionPlan)

  2. Checkout: Adresse, Versand, Zahlung
     → Normaler CoreShop Checkout

  3. Payment
     → Normaler CoreShop Payment Flow (Payum)
     → coreshop_payment State Machine

  4. Order Complete
     → OrderCompleteListener erkennt: OrderItem hat SubscriptionPlan als Product
     → Erstellt Subscription DataObject:
        - customer = Order.customer
        - subscriptionPlan = OrderItem.product (SubscriptionPlan)
        - paymentProvider = Order.paymentProvider
        - state = "trial" (wenn trialPeriodDays > 0) oder "active"
        - startDate = now()
        - nextBillingDate = berechnetes Datum
        - trialEndDate = now() + trialPeriodDays (wenn Trial)
        - initialOrder = Order
        - orders = [Order]
```

### 4.3 Renewal Flow (Verlangerung)

```
Cron (ProcessRenewalsCommand):
  1. Finde Subscriptions: nextBillingDate <= now() AND state = active

  2. Fur jede Subscription:
     a) EligibilityChecker pruft:
        - maxCycles nicht erreicht?
        - Kunde aktiv?
        - SubscriptionPlan noch aktiv?

     b) SubscriptionOrderCreator:
        - Erstellt neuen Cart
        - Fugt SubscriptionPlan als Purchasable hinzu (Menge: 1)
        - Setzt Customer, Store, Currency, PaymentProvider
        - Konvertiert Cart → Order (normaler Proposal-to-Order Flow)
        → Die neue Order hat eigene Payments
        → Payment kann direkt als "completed" markiert werden
          (bei gespeichertem Payment-Token) oder wartet auf manuelle Zahlung

     c) Aktualisiert Subscription:
        - completedCycles++
        - lastPaymentDate = now()
        - nextBillingDate = NextBillingDateResolver.resolve()
        - orders[] += neue Order

     d) Wenn maxCycles erreicht: Transition → "completed"
```

---

## 5. State Machine (Workflow)

### 5.1 Subscription Workflow (`coreshop_subscription`)

```
                    ┌──────────┐
                    │  trial   │
                    └────┬─────┘
                         │ activate
                         ▼
    ┌───resume──── ┌──────────┐ ──payment_failed──→ ┌──────────┐
    │              │  active   │                      │ past_due │
    │              └────┬──┬──┘ ←─payment_recovered─ └────┬─────┘
    │                   │  │                               │
    │              pause│  │complete                  cancel│ / expire
    │                   ▼  │                               │
    │              ┌──────────┐                             │
    └───────────── │  paused  │                             │
                   └────┬─────┘                             │
                        │                                   │
                   cancel│                                  │
                        ▼                                   ▼
                   ┌──────────┐                        ┌──────────┐
                   │cancelled │                        │ expired  │
                   └──────────┘                        └──────────┘

                                                       ┌──────────┐
                                                       │completed │
                                                       └──────────┘
```

**Places:** trial, active, paused, past_due, cancelled, expired, completed

**Transitions:**

| Transition | Von | Nach | Beschreibung |
|-----------|-----|------|-------------|
| activate | trial | active | Trial beendet, erste Zahlung erfolgreich |
| pause | active | paused | Kunde pausiert Abo |
| resume | paused | active | Kunde setzt Abo fort |
| payment_failed | active | past_due | Renewal-Order Zahlung fehlgeschlagen |
| payment_recovered | past_due | active | Zahlung nachgeholt |
| cancel | active, paused, past_due, trial | cancelled | Kundigung |
| expire | past_due | expired | Zu viele fehlgeschlagene Zahlungen |
| complete | active | completed | Alle maxCycles abgeschlossen |

**Workflow Callbacks:**

```yaml
# coreshop_subscription.yml
callbacks:
  after:
    log_state_history:
      on: ['activate', 'pause', 'resume', 'cancel', 'expire', 'complete',
           'payment_failed', 'payment_recovered']
      do: ['@CoreShop\...\StateHistoryLogger', 'log']

    set_cancellation_date:
      on: ['cancel']
      do: ['@CoreShop\...\CancellationHandler', 'handleCancellation']

    update_next_billing_on_resume:
      on: ['resume']
      do: ['@CoreShop\...\ResumeHandler', 'recalculateNextBillingDate']

    dispatch_notification_event:
      on: ['activate', 'cancel', 'expire', 'complete', 'payment_failed']
      do: ['@CoreShop\...\NotificationDispatcher', 'dispatch']
```

---

## 6. Studio UI (React/TypeScript)

### 6.1 Dateistruktur

```
SubscriptionBundle/Resources/assets/pimcore-studio/src/
├── modules/
│   ├── subscription-plans/
│   │   ├── api.ts                           # EntityApi fur SubscriptionPlan CRUD
│   │   ├── index.ts                         # Manager-Modul
│   │   └── components/
│   │       ├── SubscriptionPlanManager.tsx   # Haupt-Manager
│   │       ├── SubscriptionPlanList.tsx      # Tabellarische Liste
│   │       └── SubscriptionPlanDetail.tsx    # Detail/Edit (via SchemaForm)
│   ├── subscriptions/
│   │   ├── api.ts                           # API fur Subscriptions
│   │   ├── index.ts
│   │   └── components/
│   │       ├── SubscriptionList.tsx          # Admin-Ubersicht aller Abos
│   │       ├── SubscriptionDetail.tsx        # Detail mit Orders-Tab
│   │       ├── SubscriptionStateActions.tsx  # Pause/Resume/Cancel Buttons
│   │       └── SubscriptionOrderHistory.tsx  # Alle Orders des Abos
│   ├── subscription-rules/
│   │   ├── conditions/
│   │   │   ├── CycleCountCondition.tsx      # Nach X Zyklen
│   │   │   ├── SubscriptionAgeCondition.tsx # Abo-Alter
│   │   │   └── index.ts
│   │   ├── actions/
│   │   │   ├── DiscountPercentAction.tsx    # Prozent-Rabatt
│   │   │   ├── DiscountAmountAction.tsx     # Betrag-Rabatt
│   │   │   └── index.ts
│   │   └── index.ts
│   ├── dynamic-types/
│   │   ├── DynamicTypeObjectDataCoreShopSubscriptionPlan.tsx
│   │   └── index.ts
│   └── icon-library/
│       └── index.ts
├── selects/
│   └── SubscriptionPlanSelect.tsx           # Select mit Module-Level Caching
└── main.ts                                  # Plugin-Registrierung + Registry-Setup
```

### 6.2 Navigation

```
CoreShop Menu
  └── Subscriptions
      ├── Subscription Plans       (Pimcore DataObject Listing/CRUD)
      └── Active Subscriptions     (Pimcore DataObject Listing mit State-Filter)
```

### 6.3 Subscription Detail View

```
┌─────────────────────────────────────────────────────┐
│ Subscription #42 - Max Mustermann                   │
│ Plan: Monatsabo Premium | State: ● active           │
│                                                     │
│ [Pause] [Cancel]                     (State Actions)│
├─────────────────────────────────────────────────────┤
│ [Details] [Orders] [State History]          (Tabs)  │
├─────────────────────────────────────────────────────┤
│ Details Tab:                                        │
│   Plan:            Monatsabo Premium                │
│   Customer:        Max Mustermann                   │
│   Start:           2026-01-15                       │
│   Next Billing:    2026-03-15                       │
│   Completed Cycles: 2                               │
│   Payment Provider: Stripe                          │
│                                                     │
│ Orders Tab:                                         │
│   ┌───────┬────────────┬──────────┬────────┐       │
│   │ Order │ Date       │ Total    │ Status │       │
│   ├───────┼────────────┼──────────┼────────┤       │
│   │ #1001 │ 2026-01-15 │ 29.99 € │ ✓ paid │       │
│   │ #1042 │ 2026-02-15 │ 29.99 € │ ✓ paid │       │
│   └───────┴────────────┴──────────┴────────┘       │
└─────────────────────────────────────────────────────┘
```

---

## 7. Rule Engine (Subscription Rules)

### 7.1 Wie Regeln angewendet werden

Subscription Rules werden beim **Preis-Kalkulator** angewendet - nicht separat.
Der `SubscriptionPlanDiscountPriceCalculator` erhalt uber den Cart-Context
Zugang zur Subscription (wenn vorhanden) und kann Cycle-basierte Rabatte berechnen.

```php
class SubscriptionPlanDiscountPriceCalculator implements PurchasableDiscountPriceCalculatorInterface
{
    public function getDiscountPrice(PurchasableInterface $purchasable, array $context): int
    {
        if (!$purchasable instanceof SubscriptionPlanInterface) {
            throw new NoPurchasableDiscountPriceFoundException(__CLASS__);
        }

        // Context enthalt 'subscription' bei Renewal-Orders
        $subscription = $context['subscription'] ?? null;

        // Wende passende Subscription Rules an
        foreach ($this->ruleChecker->findValidRules($purchasable, $subscription, $context)) {
            // Actions ausfuhren (Rabatte berechnen)
        }
    }
}
```

### 7.2 Conditions & Actions

**SubscriptionBundle registriert:**

| Conditions | Beschreibung |
|-----------|-------------|
| cycleCount | Zyklus-Nummer (z.B. >= 6) |
| subscriptionAge | Abo-Alter in Tagen |
| nested | Verschachtelte Bedingungen |

| Actions | Beschreibung |
|--------|-------------|
| discountPercent | Prozentualer Rabatt auf Abo-Preis |
| discountAmount | Fester Betrag Rabatt |

**CoreBundle registriert zusatzlich (Cross-Bundle):**

| Conditions | Beschreibung |
|-----------|-------------|
| customers | Bestimmte Kundengruppen |
| stores | Bestimmte Stores |
| countries | Bestimmte Lander |
| categories | Produktkategorien |

### 7.3 Beispiele

- "Ab dem 6. Monat: 10% Treue-Rabatt"
  → cycleCount >= 6 → discountPercent(10)

- "Jahresabo nach 1 Jahr: 5€ Rabatt"
  → subscriptionAge >= 365 → discountAmount(500)

- "VIP-Kunden: 15% auf alle Abos"
  → customers(VIP-Gruppe) → discountPercent(15)

---

## 8. Dependencies (composer.json)

### Component (`coreshop/subscription`)

```json
{
  "name": "coreshop/subscription",
  "require": {
    "coreshop/order": "self.version",
    "coreshop/resource": "self.version",
    "coreshop/rule": "self.version",
    "coreshop/store": "self.version",
    "coreshop/currency": "self.version"
  }
}
```

### Bundle (`coreshop/subscription-bundle`)

```json
{
  "name": "coreshop/subscription-bundle",
  "require": {
    "coreshop/subscription": "self.version",
    "coreshop/order-bundle": "self.version",
    "coreshop/resource-bundle": "self.version",
    "coreshop/rule-bundle": "self.version",
    "coreshop/store-bundle": "self.version",
    "coreshop/currency-bundle": "self.version",
    "coreshop/workflow-bundle": "self.version",
    "pimcore/pimcore": "^12.0"
  }
}
```

**Bewusst NICHT als Dependency:**
- `coreshop/payment-bundle` → Payment lauft uber Order, nicht direkt
- `coreshop/customer-bundle` → Subscription kennt Customer nur als Interface
- `coreshop/core-bundle` → CoreBundle erweitert SubscriptionBundle, nicht umgekehrt

---

## 9. CoreBundle Integration

CoreBundle verbindet SubscriptionBundle mit dem Rest des Systems:

### 9.1 Was CoreBundle tut

```php
// 1. Registriert Cross-Bundle Rule Conditions
//    (customers, countries, stores, categories → in SubscriptionRule Registry)

// 2. OrderCompleteListener
//    → Erkennt SubscriptionPlan in OrderItems
//    → Erstellt Subscription DataObject

// 3. Renewal Order Context
//    → Setzt 'subscription' in Cart-Context fur Renewal-Orders
//    → Ermoglicht Cycle-basierte Rabatte

// 4. Notification Rules
//    → Registriert Subscription-Events als Notification-Trigger

// 5. Tax Integration
//    → Registriert TaxCalculator fur SubscriptionPlan
//    → Nutzt bestehende TaxRule-Gruppen

// 6. Studio UI Extensions
//    → Customer-Tab: "Subscriptions" zeigt alle Abos des Kunden
//    → Order-Detail: Zeigt Abo-Verknupfung wenn Order aus Subscription stammt
```

### 9.2 CoreBundle Studio UI

```
CoreBundle/Resources/assets/pimcore-studio/src/modules/
├── subscription-extensions/
│   ├── conditions/
│   │   ├── CustomersCondition.tsx     # Registriert in Subscription Rule Registry
│   │   ├── CountriesCondition.tsx
│   │   └── CategoriesCondition.tsx
│   └── index.ts
```

---

## 10. Events

| Event | Wann | Daten |
|-------|------|-------|
| coreshop.subscription.pre_create | Vor Abo-Erstellung | Subscription |
| coreshop.subscription.post_create | Nach Abo-Erstellung | Subscription, InitialOrder |
| coreshop.subscription.pre_renewal | Vor Verlangerung | Subscription |
| coreshop.subscription.post_renewal | Nach Verlangerung | Subscription, RenewalOrder |
| coreshop.subscription.renewal_failed | Order/Payment fehlgeschlagen | Subscription |
| coreshop.subscription.cancelled | Abo gekundigt | Subscription |
| coreshop.subscription.paused | Abo pausiert | Subscription |
| coreshop.subscription.resumed | Abo fortgesetzt | Subscription |
| coreshop.subscription.expired | Abo abgelaufen | Subscription |
| coreshop.subscription.completed | Alle Zyklen durch | Subscription |
| coreshop.subscription.trial_ending | Trial endet in 3 Tagen | Subscription |

---

## 11. Implementierungsreihenfolge

### Phase 1: Foundation
1. Component erstellen (Interfaces, Models, States, BillingCycle Enum)
2. Bundle-Grundstruktur (DI, Configuration, composer.json)
3. Pimcore DataObject Definitionen (SubscriptionPlan + Subscription)
4. Repositories und Factories

### Phase 2: Purchasable Integration
5. SubscriptionPlan PurchasableInterface implementieren
6. Price Calculators (Price, RetailPrice, DiscountPrice, Discount)
7. Registrierung in Purchasable Calculator Registry (purchasable.yml)
8. Test: SubscriptionPlan in Cart legen und durch Checkout

### Phase 3: Subscription Lifecycle
9. State Machine Workflow Definition (coreshop_subscription.yml)
10. OrderCompleteListener (erstellt Subscription nach Kauf)
11. NextBillingDateResolver
12. EligibilityChecker

### Phase 4: Renewal-System
13. SubscriptionOrderCreator (erstellt Cart + Order aus Subscription)
14. RenewalProcessor (orchestriert den Renewal-Flow)
15. ProcessRenewalsCommand (Cron)
16. ExpireSubscriptionsCommand (Cron)

### Phase 5: Rule Engine
17. SubscriptionRule Model
18. Conditions (cycleCount, subscriptionAge, nested)
19. Actions (discountPercent, discountAmount)
20. Integration in Price Calculators

### Phase 6: Studio UI
21. SubscriptionPlan Admin (Liste + Detail)
22. Subscription Admin (Liste + Detail + State Actions)
23. Rule Engine UI
24. Dynamic Types + Select Components

### Phase 7: CoreBundle Integration
25. Cross-Bundle Rule Conditions
26. Tax Integration
27. Notification Rules
28. Customer/Order View Extensions

### Phase 8: Dokumentation
29. Architecture Docs (`docs/03_Development/`)
30. Configuration Guide
31. API Reference
