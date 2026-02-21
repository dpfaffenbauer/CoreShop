<?php

declare(strict_types=1);

namespace CoreShop\Bundle\PimcoreMcpBundle\Areabrick;

use Pimcore\Extension\Document\Areabrick\AbstractTemplateAreabrick;
use Pimcore\Extension\Document\Areabrick\Attribute\AsAreabrick;

#[AsAreabrick(id: 'hero-banner')]
class HeroBanner extends AbstractTemplateAreabrick
{
    public function getName(): string
    {
        return 'Hero Banner';
    }
}
