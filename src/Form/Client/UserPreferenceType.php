<?php

namespace App\Form\Client;

use App\Entity\Users\UserPreferences;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserPreferenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('newQuotesEnabled')
            ->add('artisanMessagesEnabled')
            ->add('appointmentRemindersEnabled')
            ->add('reviewInvitationsEnabled')
            ->add('profileVisibleToArtisans')
            ->add('phoneSharedAfterAcceptance')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserPreferences::class,
        ]);
    }
}
