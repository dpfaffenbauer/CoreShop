<?php

declare(strict_types=1);

namespace CoreShop\Bundle\OrderReturnManagementBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

final class OrderReturnCreationItemsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('orderItemId', NumberType::class)
            ->add('quantity', NumberType::class)
        ;
    }
}
