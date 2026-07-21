<?php

namespace App\Form\Artisan;

use App\Entity\Users\ArtisanNotificationPreferences;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NotificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('newRequestsEnabled')
            ->add('urgentRequestsSmsEnabled')
            ->add('clientMessagesEnabled')
            ->add('newReviewsEnabled')
            ->add('quoteRemindersEnabled')
            ->add('weeklySummaryEnabled')
            ->add('tipsAndNewsEnabled')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ArtisanNotificationPreferences::class,
        ]);
    }
}
