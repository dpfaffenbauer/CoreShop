<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2017 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Component\Index\Interpreter;

use CoreShop\Component\Index\Model\IndexColumnInterface;

class LocaleMappingInterpreter implements LocalizedInterpreterInterface
{
    /**
     * {@inheritdoc}
     */
    public function interpret($value, IndexColumnInterface $config = null)
    {
        throw new \Exception('method "interpret" in Localized Interpreter not allowed. Please use "interpretForLanguage" instead.');
    }

    /**
     * {@inheritdoc}
     */
    public function interpretForLanguage($language, $value, $config = null)
    {
        if (!is_array($value)) {
            return $value;
        }

        if (isset($value[$language])) {
            return $value[$language];
        }

        return null;
    }
}
