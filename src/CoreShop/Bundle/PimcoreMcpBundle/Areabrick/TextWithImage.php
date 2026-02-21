<?php

declare(strict_types=1);

namespace CoreShop\Bundle\PimcoreMcpBundle\Areabrick;

use Pimcore\Extension\Document\Areabrick\AbstractTemplateAreabrick;
use Pimcore\Extension\Document\Areabrick\Attribute\AsAreabrick;

#[AsAreabrick(id: 'text-with-image')]
class TextWithImage extends AbstractTemplateAreabrick
{
    public function getName(): string
    {
        return 'Text with Image';
    }
}
