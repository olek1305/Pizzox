<?php

namespace App\Form;

use App\Document\Pizza;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class PizzaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'form.pizza.name.label',
                'required' => true,
                'attr' => [
                    'placeholder' => 'form.pizza.name.placeholder',
                ],
            ])

            ->add('price', NumberType::class, [
                'label' => 'form.pizza.price.label',
                'attr' => [
                    'placeholder' => 'form.pizza.price.placeholder',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'form.pizza.price.not_blank']),
                    new Positive(['message' => 'form.pizza.price.positive']),
                ],
            ])
            ->add('size', ChoiceType::class, [
                'label' => 'form.pizza.size.label',
                'choices' => [
                    'Small' => 'small',
                    'Medium' => 'medium',
                    'Large' => 'large',
                ],
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('toppings', CollectionType::class, [
                'label' => 'form.pizza.toppings.label',
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'by_reference' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pizza::class,
        ]);
    }
}