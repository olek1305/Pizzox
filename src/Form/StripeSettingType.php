<?php

namespace App\Form;

use App\Document\Setting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class StripeSettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('stripeSecretKey', PasswordType::class, [
                'label' => 'Stripe Secret Key',
                'required' => true,
                'mapped' => true,
                'attr' => [
                    'placeholder' => '••••••••••',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'not_blank'])
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'saves',
                'attr' => [
                    'class' => 'bg-green-500 text-white rounded-lg hover:bg-green-700'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Setting::class,
        ]);
    }
}
