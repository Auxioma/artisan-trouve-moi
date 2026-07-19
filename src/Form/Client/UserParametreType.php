<?php

namespace App\Form\Client;

use App\Entity\Users\UserProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserParametreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label')
            ->add('addressLine2')
            ->add('postalCode')
            ->add('city')
            ->add('addressLine1', HiddenType::class)
            ->add('district', HiddenType::class)
            ->add('region', HiddenType::class)
            ->add('department', HiddenType::class)
            ->add('countryCode', HiddenType::class)
            ->add('formattedAddress', HiddenType::class)
            ->add('providerPlaceId', HiddenType::class)
            ->add('providerName', HiddenType::class)
            ->add('latitude', HiddenType::class)
            ->add('longitude', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserProfile::class,
        ]);
    }
}
