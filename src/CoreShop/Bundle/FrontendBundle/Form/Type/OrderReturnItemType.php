<?php

declare(strict_types=1);

namespace CoreShop\Bundle\FrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;

final class OrderReturnItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('orderItemId', IntegerType::class, [
                'label' => false,
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'coreshop.ui.quantity_to_return',
                'attr' => ['min' => 0],
            ])
        ;
    }
}
