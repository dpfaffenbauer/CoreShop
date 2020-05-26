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

namespace CoreShop\Bundle\ResourceBundle\DataHub\EventListener;

use CoreShop\Bundle\ResourceBundle\DataHub\DataHubMutationQueryInterface;
use CoreShop\Bundle\ResourceBundle\DataHub\DataHubQueryInterface;
use CoreShop\Component\Registry\ServiceRegistryInterface;
use GraphQL\Type\Definition\ObjectType;
use Pimcore\Bundle\DataHubBundle\Event\GraphQL\Model\MutationTypeEvent;
use Pimcore\Bundle\DataHubBundle\Event\GraphQL\Model\QueryTypeEvent;
use Pimcore\Bundle\DataHubBundle\Event\GraphQL\MutationEvents;
use Pimcore\Bundle\DataHubBundle\Event\GraphQL\QueryEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webmozart\Assert\Assert;

final class BuildSchemaListener implements EventSubscriberInterface
{
    private $registry;

    public function __construct(ServiceRegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    public static function getSubscribedEvents()
    {
        return [
            QueryEvents::POST_BUILD => 'postBuildQuery',
            MutationEvents::POST_BUILD => 'postBuildMutation',
        ];
    }

    public function postBuildQuery(QueryTypeEvent $event)
    {
        $schema = $this->createSchema(
            $event->getContext(),
            $event->getConfig(),
            $event->getQueryType(),
            false
        );

        $event->setConfig($schema);
    }

    public function postBuildMutation(MutationTypeEvent $event)
    {
        $schema = $this->createSchema(
            $event->getContext(),
            $event->getConfig(),
            $event->getMutationType(),
            true
        );

        $event->setConfig($schema);
    }

    protected function createSchema(array $dataHubContext, array $schema, ObjectType $queryType, bool $mutation = false)
    {
        $queries = [];

        foreach ($this->registry->all() as $dataHubQuery) {
            if ($mutation && !$dataHubQuery instanceof DataHubMutationQueryInterface) {
                continue;
            }

            if (!$mutation && $dataHubQuery instanceof DataHubMutationQueryInterface) {
                continue;
            }

            /**
             * @var DataHubQueryInterface $dataHubQuery
             */
            Assert::isInstanceOf($dataHubQuery, DataHubQueryInterface::class);

            $queryQueries = $dataHubQuery->getDataHubQueries(
                $dataHubContext,
                $schema,
                $queryType
            );

            if (null === $queryQueries) {
                continue;
            }

            $queries = array_merge($queries, $queryQueries);
        }

        $schema['fields'] = array_merge(
            $schema['fields'],
            $queries
        );

        return $schema;
    }
}
