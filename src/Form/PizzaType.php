<?php

namespace App\Form;

use App\Document\Category;
use App\Document\Pizza;
use App\Repository\SettingRepository;
use App\Service\CurrencyProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Throwable;

class PizzaType extends AbstractType
{
    public function __construct(
        private readonly CurrencyProvider $currencyProvider,
        private readonly SettingRepository $settingRepository
    ) {
        //
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $settings = $this->settingRepository->findLastOrCreate();

        $builder
            ->add('name', TextType::class, [
                'label' => 'form.pizza.name.label',
                'required' => true,
                'constraints' => new NotBlank(['message' => 'not_blank']),
                'attr' => ['placeholder' => 'form.pizza.name.placeholder'],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'form.pizza.price.label',
                'currency' => $this->currencyProvider->getCurrency(),
                'constraints' => [
                    new NotBlank(['message' => 'not_blank']),
                    new Positive(['message' => 'positive']),
                ],
                'attr' => [
                    'placeholder' => 'form.pizza.price.placeholder',
                    'data-calculation-type' => $settings->getPizzaPriceCalculationType(),
                    'data-small-modifier' => $settings->getSmallSizeModifier(),
                    'data-large-modifier' => $settings->getLargeSizeModifier(),
                ],
            ])
            ->add('priceSmall', MoneyType::class, [
                'label' => 'form.pizza.price_small.label',
                'currency' => $this->currencyProvider->getCurrency(),
                'required' => false,
                'attr' => [
                    'placeholder' => 'Will be calculated if empty',
                ],
            ])
            ->add('priceLarge', MoneyType::class, [
                'label' => 'form.pizza.price_large.label',
                'currency' => $this->currencyProvider->getCurrency(),
                'required' => false,
                'attr' => [
                    'placeholder' => 'Will be calculated if empty',
                ],
            ])
            ->add('toppings', CollectionType::class, [
                'label' => 'form.pizza.toppings.label',
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'by_reference' => false
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'form.pizza.category.label',
                'required' => true,
                'choices' => $options['categories'],
                'choice_label' => function ($category) {
                    return $category->getName();
                },
                'choice_value' => function (?Category $category) {
                    return $category ? $category->getId() : '';
                },
                'placeholder' => 'form.pizza.category.placeholder',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pizza::class,
            'categories' => [],
        ]);
    }
}