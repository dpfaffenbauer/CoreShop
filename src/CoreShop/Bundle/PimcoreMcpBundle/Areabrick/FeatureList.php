<?php

declare(strict_types=1);

namespace CoreShop\Bundle\PimcoreMcpBundle\Areabrick;

use Pimcore\Extension\Document\Areabrick\AbstractTemplateAreabrick;
use Pimcore\Extension\Document\Areabrick\Attribute\AsAreabrick;

#[AsAreabrick(id: 'feature-list')]
class FeatureList extends AbstractTemplateAreabrick
{
    public function getName(): string
    {
        return 'Feature List';
    }
}
