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

abstract class AbstractExtensionsMutationQuery implements DataHubMutationQueryInterface
{
    protected $extensions;

    public function __construct(iterable $extensions)
    {
        $this->extensions = $extensions;
    }

    protected function handleExtension(string $type, array $params)
    {
        foreach ($this->extensions as $extension) {
            if (!$extension instanceof DataHubQueryExtensionInterface) {
                continue;
            }

            if (!$extension->supports($this)) {
                continue;
            }

            return $extension->handle($type, $params);
        }

        return $params;
    }
}
