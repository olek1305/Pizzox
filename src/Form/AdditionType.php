<?php

namespace App\Form;

use App\Document\Addition;
use App\Document\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class AdditionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Addition Name',
                'constraints' => [
                    new NotBlank([
                        'message' => 'The addition name cannot be empty.',
                    ]),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Addition Price',
                'currency' => 'PLN',
                'constraints' => [
                    new NotBlank([
                        'message' => 'The addition price cannot be empty.',
                    ]),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'form.pizza.category.label',
                'choices' => $options['categories'],
                'choice_label' => function ($category) {
                    return $category->getName();
                },
                'choice_value' => function (?Category $category) {
                    return $category ? $category->getId() : '';
                },
                'placeholder' => 'form.pizza.category.placeholder',
                'required' => true
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