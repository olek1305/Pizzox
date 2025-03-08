<?php

namespace App\Form;

use App\Document\Setting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PizzaSizeSettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pizzaPriceCalculationType', ChoiceType::class, [
                'label' => 'Price Calculation Type',
                'choices' => [
                    'Fixed Amount' => 'fixed',
                    'Percentage' => 'percentage',
                ],
                'expanded' => true,
            ])
            ->add('largeSizeModifier', NumberType::class, [
                'label' => 'Large Size Modifier',
                'scale' => 2,
            ])
            ->add('smallSizeModifier', NumberType::class, [
                'label' => 'Small Size Modifier',
                'scale' => 2,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Setting::class,
        ]);
    }
}