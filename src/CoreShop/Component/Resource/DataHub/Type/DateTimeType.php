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

namespace CoreShop\Component\Resource\DataHub\Type;

use GraphQL\Type\Definition\ScalarType;

class DateTimeType extends ScalarType
{
    public $name = 'datetime';

    public function serialize($value)
    {
        if ($value !== null) {
            return $value->getTimestamp();
        }

        return null;
    }

    public function parseValue($value)
    {
        if ($value !== null)
        {
            $date = new \DateTime();

            return $date->setTimestamp($value);
        }

        return null;
    }

    public function parseLiteral($valueNode, ?array $variables = null)
    {
        if ($valueNode != null && $valueNode->value !== null)
        {
            $date = new \DateTime();

            return $date->setTimestamp($valueNode->value);
        }

        return null;
    }
}
