<?php

namespace App\Form;

use App\Document\Pizza;
use Symfony\Component\Form\AbstractType;
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
                'attr' => ['placeholder' => 'form.pizza.name.placeholder'],
                'constraints' => [
                    new NotBlank(['message' => 'form.pizza.name.not_blank']),
                ],
            ])
            ->add('price', NumberType::class, [
                'label' => 'form.pizza.price.label',
                'attr' => ['placeholder' => 'form.pizza.price.placeholder'],
                'constraints' => [
                    new NotBlank(['message' => 'form.pizza.price.not_blank']),
                    new Positive(['message' => 'form.pizza.price.positive']),
                ],
            ])
            ->add('size', TextType::class, [
                'label' => 'form.pizza.size.label',
                'attr' => ['placeholder' => 'form.pizza.size.placeholder'],
                'constraints' => [
                    new NotBlank(['message' => 'form.pizza.size.not_blank']),
                ],
            ])
            ->add('ingredients', CollectionType::class, [
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'entry_options' => [
                    'label' => 'form.pizza.ingredients.label',
                    'attr' => ['placeholder' => 'form.pizza.ingredients.placeholder'],
                    'constraints' => [
                        new NotBlank(['message' => 'form.pizza.ingredients.not_blank']),
                    ],
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pizza::class,
        ]);
    }
}