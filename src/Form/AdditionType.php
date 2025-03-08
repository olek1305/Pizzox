<?php

namespace App\Form;

use App\Document\Addition;
use App\Document\Category;
use App\Service\CurrencyProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class AdditionType extends AbstractType
{
    public function __construct(
        private readonly CurrencyProvider $currencyProvider
    ) {
        //
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Addition Name',
                'required' => true,
                'constraints' => new NotBlank(['message' => 'not_blank']),
                'attr' => ['class' => 'form-control'],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Addition Price ',
                'currency' => $this->currencyProvider->getCurrency(),
                'constraints' => [
                    new NotBlank(['message' => 'not_blank']),
                    new Positive(['message' => 'positive']),
                ],
                'attr' => ['class' => 'form-control'],
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
                'placeholder' => 'form.pizza.category.placeholder'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Addition::class,
            'categories' => [],
        ]);
    }
}