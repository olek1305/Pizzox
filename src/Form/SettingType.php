<?php

namespace App\Form;

use App\Document\Setting;
use App\Service\CurrencyService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currencies = $options['currencies'];

        $builder
            ->add('currency', ChoiceType::class, [
                'choices' => $currencies,
                'label' => 'Currency',
                'required' => true,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save Changes',
                'attr' => ['class' => 'btn btn-primary mt-3']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Setting::class,
            'currencies' => []
        ]);

        $resolver->setAllowedTypes('currencies', 'array');
    }
}