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

namespace CoreShop\Component\Configuration\Service;

use CoreShop\Component\Configuration\Model\ConfigurationInterface;
use CoreShop\Component\Configuration\Repository\ConfigurationRepositoryInterface;
use CoreShop\Component\Resource\Factory\FactoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class ConfigurationService implements ConfigurationServiceInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var ConfigurationRepositoryInterface
     */
    protected $configurationRepository;

    /**
     * @var FactoryInterface
     */
    protected $configurationFactory;

    /**
     * @param EntityManagerInterface           $entityManager
     * @param ConfigurationRepositoryInterface $configurationRepository
     * @param FactoryInterface                 $configurationFactory
     */
    public function __construct(EntityManagerInterface $entityManager, ConfigurationRepositoryInterface $configurationRepository, FactoryInterface $configurationFactory)
    {
        $this->entityManager = $entityManager;
        $this->configurationRepository = $configurationRepository;
        $this->configurationFactory = $configurationFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, bool $returnObject = false)
    {
        $config = $this->configurationRepository->findByKey($key);

        if ($config instanceof ConfigurationInterface) {
            return $returnObject ? $config : $config->getData();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $data): ConfigurationInterface
    {
        $config = $this->get($key, true);

        if (!$config) {
            $config = $this->configurationFactory->createNew();
            $config->setKey($key);
        }

        $config->setData($data);
        $this->entityManager->persist($config);
        $this->entityManager->flush();

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $key)
    {
        $config = $this->get($key, true);

        if ($config instanceof ConfigurationInterface) {
            $this->entityManager->remove($config);
            $this->entityManager->flush();
        }
    }
}
