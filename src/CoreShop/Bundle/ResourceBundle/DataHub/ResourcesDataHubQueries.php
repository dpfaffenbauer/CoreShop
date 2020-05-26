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

namespace CoreShop\Bundle\ResourceBundle\DataHub;

use CoreShop\Component\Resource\DataHub\DoctrineProvider;
use GraphQL\Type\Definition\ObjectType;

class ResourcesDataHubQueries implements DataHubQueryInterface
{
    private $doctrineProvider;

    public function __construct(DoctrineProvider $doctrineProvider)
    {
        $this->doctrineProvider = $doctrineProvider;
    }

    public function getDataHubQueries(array $dataHubContext, array $dataHubConfig, ObjectType $queryType): ?array
    {
        return $this->doctrineProvider->getGraphQlQueries();
    }
}
