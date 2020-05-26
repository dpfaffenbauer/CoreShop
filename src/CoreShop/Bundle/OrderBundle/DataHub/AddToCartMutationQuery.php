<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2020 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

declare(strict_types=1);

namespace CoreShop\Bundle\OrderBundle\DataHub;

use CoreShop\Bundle\OrderBundle\DataHub\InputType\AddToCartInputType;
use CoreShop\Bundle\ResourceBundle\DataHub\AbstractExtensionsMutationQuery;
use CoreShop\Bundle\ResourceBundle\DataHub\InputTypeFactoryInterface;
use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Order\Cart\CartModifierInterface;
use CoreShop\Component\Order\Factory\OrderItemFactoryInterface;
use CoreShop\Component\Order\Manager\CartManagerInterface;
use CoreShop\Component\Order\Model\OrderItemInterface;
use CoreShop\Component\Order\Model\PurchasableInterface;
use CoreShop\Component\Order\Repository\OrderRepositoryInterface;
use CoreShop\Component\Resource\Factory\FactoryInterface;
use CoreShop\Component\Resource\Repository\PimcoreRepositoryInterface;
use CoreShop\Component\Resource\Repository\RepositoryInterface;
use CoreShop\Component\Store\Model\StoreInterface;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Pimcore\Bundle\DataHubBundle\Configuration;
use Pimcore\Bundle\DataHubBundle\GraphQL\ClassTypeDefinitions;
use Pimcore\Bundle\DataHubBundle\GraphQL\Resolver\QueryType;
use Pimcore\Bundle\DataHubBundle\GraphQL\Service;
use Pimcore\Model\DataObject\ClassDefinition;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AddToCartMutationQuery extends AbstractExtensionsMutationQuery
{
    protected $cartClass;
    protected $inputTypeFactory;
    protected $graphQlService;
    protected $eventDispatcher;
    protected $storeRepository;
    protected $purchasableRepository;
    protected $cartRepository;
    protected $cartFactory;
    protected $cartItemFactory;
    protected $cartModifier;
    protected $cartManager;

    public function __construct(
        string $cartClass,
        InputTypeFactoryInterface $inputTypeFactory,
        Service $graphQlService,
        EventDispatcherInterface $eventDispatcher,
        RepositoryInterface $storeRepository,
        PimcoreRepositoryInterface $purchasableRepository,
        OrderRepositoryInterface $cartRepository,
        FactoryInterface $cartFactory,
        OrderItemFactoryInterface $cartItemFactory,
        CartModifierInterface $cartModifier,
        CartManagerInterface $cartManager,
        iterable $extensions
    )
    {
        parent::__construct($extensions);

        $this->cartClass = $cartClass;
        $this->inputTypeFactory = $inputTypeFactory;
        $this->graphQlService = $graphQlService;
        $this->eventDispatcher = $eventDispatcher;
        $this->storeRepository = $storeRepository;
        $this->purchasableRepository = $purchasableRepository;
        $this->cartRepository = $cartRepository;
        $this->cartFactory = $cartFactory;
        $this->cartItemFactory = $cartItemFactory;
        $this->cartModifier = $cartModifier;
        $this->cartManager = $cartManager;
        $this->extensions = $extensions;
    }

    public function getDataHubQueries(array $dataHubContext, array $dataHubConfig, ObjectType $queryType): ?array
    {
        /** @var Configuration $configuration */
        $configuration = $dataHubContext['configuration'];
        $entities = $configuration->getQueryEntities();
        $mutationEntities = $configuration->getMutationEntities();
        $cartEntity = null;

        if (!in_array($this->cartClass, $mutationEntities, true)) {
            return null;
        }

        foreach ($entities as $entity) {
            if ($entity !== $this->cartClass) {
                continue;
            }

            $cartEntity = $entity;
            break;
        }

        if ($cartEntity === null) {
            return null;
        }

        $class = ClassDefinition::getByName($cartEntity);
        $queryResolver = new QueryType($this->eventDispatcher, $class, $configuration);
        $queryResolver->setGraphQlService($this->graphQlService);

        $mutationTypeResult = new ObjectType([
            'name' => 'AddToCartMutationResult',
            'fields' => [
                'success' => ['type' => Type::boolean()],
                'message' => ['type' => Type::string()],
                'cart' => [
                    'type' => ClassTypeDefinitions::get($cartEntity),
                    'resolve' => function ($value, $args, $context, ResolveInfo $info) use ($queryResolver) {
                        $args = $this->handleExtension('resolveMutationTypeResultResolve', ['value' => $value, 'args' => $args, 'context' => $context, 'info' => $info]);

                        $args['id'] = $value['id'];
                        $value = $queryResolver->resolveObjectGetter($value, $args, $context, $info);
                        return $value;
                    }
                ],
            ]
        ]);

        $this->handleExtension('mutationTypeResult', ['type' => $mutationTypeResult]);

        $mutationType = [
            'type' => $mutationTypeResult,
            'args' => [
                'add_to_cart' => [
                    'type' => $this->inputTypeFactory->buildType(AddToCartInputType::class)
                ]
            ],
            'resolve' => function ($value, $args, $context, ResolveInfo $info) {
                $args = $this->handleExtension('resolveMutationType', $args);

                /**
                 * @var OrderInterface $cart
                 */
                $cart = $this->cartRepository->findOneBy(['token' => $args['add_to_cart']['cart']['token']]);
                $store = $this->storeRepository->find($args['add_to_cart']['store']['storeId']);

                if (!$store instanceof StoreInterface) {
                    return [
                        'success' => false,
                        'message' => 'Store Invalid'
                    ];
                }

                if (!$cart) {
                    $cart = $this->cartFactory->createNew();
                    $cart->setToken($args['add_to_cart']['cart']['token']);

                    $this->handleExtension('cartFactory,', ['cart' => $cart, 'args' => $args, 'context' => $context, 'info' => $info]);
                }

                $cart->setStore($store);
                $cart->setCurrency($store->getCurrency());

                $purchasable = $this->purchasableRepository->find($args['add_to_cart']['cartItem']['productId']);

                if (!$purchasable instanceof PurchasableInterface) {
                    return [
                        'success' => false,
                        'message' => 'Purchasable Invalid'
                    ];
                }

                $cartItem = $this->createCartItem($cart, $purchasable, (float)$args['add_to_cart']['cartItem']['quantity']);

                $this->handleExtension('cartItemFactory,', ['cart' => $cart, 'cartItem' => $cartItem, 'args' => $args, 'context' => $context, 'info' => $info]);

                $this->cartModifier->addToList($cart, $cartItem);
                $this->cartManager->persistCart($cart);

                return [
                    "success" => true,
                    "message" => "cart " . $cart->getId(),
                    "id" => $cart->getId()
                ];
            }
        ];

        $this->handleExtension('mutationType', ['type' => $mutationType]);

        return [
            'AddToCartMutation' => $mutationType
        ];
    }

    protected function createCartItem(OrderInterface $cart, PurchasableInterface $product, float $quantity, array $args = []): OrderItemInterface
    {
        return $this->cartItemFactory->createWithCart(
            $cart,
            $product,
            $quantity
        );
    }
}
