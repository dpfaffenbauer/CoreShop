<?php

declare(strict_types=1);

/*
 * CoreShop
 *
 * This source file is available under the terms of the
 * CoreShop Commercial License (CCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CoreShop GmbH (https://www.coreshop.com)
 * @license    CoreShop Commercial License (CCL)
 *
 */

namespace CoreShop\Bundle\SubscriptionBundle\Controller;

use CoreShop\Bundle\ResourceBundle\Controller\PimcoreController;
use Pimcore\Model\DataObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionController extends PimcoreController
{
    public function getFolderConfigurationAction(Request $request): Response
    {
        $this->isGrantedOr403();

        $name = null;
        $folderId = null;

        $subscriptionClassId = (string) $this->getParameter('coreshop.model.subscription.pimcore_class_name');
        $folderPath = (string) $this->getParameter('coreshop.folder.subscription');
        $classDefinition = DataObject\ClassDefinition::getByName($subscriptionClassId);

        $folder = DataObject::getByPath('/' . $folderPath);

        if ($folder instanceof DataObject\Folder) {
            $folderId = $folder->getId();
        }

        if ($classDefinition instanceof DataObject\ClassDefinition) {
            $name = $classDefinition->getName();
        }

        return $this->viewHandler->handle(['success' => true, 'className' => $name, 'folderId' => $folderId]);
    }
}
