<?php

namespace App\Form;

use App\Document\Setting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class MapboxSettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mapboxToken', TextType::class, [
                'label' => 'settings.mapbox.token',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a Mapbox token',
                    ]),
                ],
            ])
            ->add('restaurantName', TextType::class, [
                'label' => 'settings.mapbox.restaurant.name',
                'required' => true,
            ])
            ->add('restaurantAddress', TextType::class, [
                'label' => 'settings.mapbox.restaurant.address',
                'required' => false,
            ])
            ->add('latitude', HiddenType::class, [
                'required' => false,
            ])
            ->add('longitude', HiddenType::class, [
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Setting::class,
        ]);
    }
}