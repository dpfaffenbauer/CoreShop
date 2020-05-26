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

namespace CoreShop\Component\Resource\DataHub;

use Doctrine\ORM\QueryBuilder;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class GraphPageInfo
{
    const NAME = 'PageInfo';

    public $cursor;

    public static function getType(): ObjectType
    {
        $pageFields = array(
            array(
                'name' => 'hasMore',
                'type' => Type::boolean(),
            ),
        );

        return new ObjectType(array('name' => self::NAME, 'fields' => $pageFields));
    }

    public static function getQueryFilters($provider): array
    {
        $filterFields = array();

        $filterFields['first'] = array('name' => 'first', 'type' => Type::int());
        $filterFields['after'] = array('name' => 'after', 'type' => Type::string());
        $filterFields['offset'] = array('name' => 'offset', 'type' => Type::int());

        $sortFieldType = $provider->getType(GraphSortField::NAME);

        $filterFields['sort'] = array('name' => 'sort', 'type' => Type::listOf($sortFieldType));

        return $filterFields;
    }

    public static function getFilters(): array
    {
        $filterFields = array();

        $filterFields['first'] = array('name' => 'first', 'type' => Type::int());

        return $filterFields;

    }

    public static function paginateQuery(QueryBuilder $queryBuilder, array $identifiers, array $args): array
    {
        if (array_key_exists('first', $args)) {

            $maxResults = $args['first'];
            $queryBuilder->setMaxResults($maxResults + 1);

            unset($args['first']);
        }

        $hasOffset = false;

        if (array_key_exists('offset', $args)) {

            $queryBuilder->setFirstResult($args['offset']);

            unset($args['offset']);

            $hasOffset = true;

        }

        if (array_key_exists('after', $args)) {

            if (!$hasOffset) {
                static::addAfter($queryBuilder, $identifiers, $args['after']);
            }

            unset($args['after']);

        }

        return $args;
    }

    public static function sortQuery(QueryBuilder $queryBuilder, array $identifiers, array $args): array
    {
        $hasOrderBy = false;

        // Handle the first argument.
        if (array_key_exists('sort', $args)) {

            foreach ($args['sort'] as $sortField) {

                $sortDirection = strtolower((isset($sortField['order']) ? $sortField['order'] : 'asc'));

                if (!($sortDirection === 'asc' || $sortDirection === 'desc')) {
                    $sortDirection = 'asc';
                }

                $queryBuilder->addOrderBy('e.'.$sortField['field'], $sortField['order']);
            }

            unset($args['sort']);

            $hasOrderBy = true;
        }

        if (!$hasOrderBy)
        {
            foreach ($identifiers as $id) {
                $queryBuilder->addOrderBy('e.'.$id, 'ASC');
            }
        }

        return $args;
    }

    public static function addAfter(QueryBuilder $queryBuilder, array $identifiers, $values): void
    {
        $after = explode(':', base64_decode($values));

        $identifierString = static::generateQuery($identifiers, $after);

        $queryBuilder->andWhere($identifierString);
    }

    public static function generateQuery(array $identifiers, array $values)
    {
        $nextIdentifiers = array_slice($identifiers, 1, count($values) - 1);
        $nextValues = array_slice($values, 1, count($values) - 1);

        if (count($nextIdentifiers) !== 0) {
            $identifierString = 'e.'.$identifiers[0].' >= \''.$values[0].'\' AND ( e.'.$identifiers[0].' > \''.$values[0].'\' OR ('.static::generateQuery($nextIdentifiers, $nextValues).'))';
        } else {
            $identifierString = 'e.'.$identifiers[0].' > \''.$values[0].'\'';
        }

        return $identifierString;
    }
}
