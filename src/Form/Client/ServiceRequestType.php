<?php

declare(strict_types=1);

namespace App\Form\Client;

use App\Entity\Catalog\Category;
use App\Entity\Requests\ServiceRequest;
use App\Entity\Users\ArtisanProfile;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ServiceRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $artisan = $options['artisan'];
        $categories = [];

        foreach ($artisan->getServices() as $service) {
            $category = $service->getCategory();

            if ($service->isActive() && null !== $category) {
                $categories[$category->getId() ?? spl_object_id($category)] = $category;
            }
        }

        $builder
            ->add('title', TextType::class)
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choices' => array_values($categories),
                'choice_label' => 'name',
                'placeholder' => 'Sélectionnez une catégorie',
            ])
            ->add('description', TextareaType::class)
            ->add('urgency', ChoiceType::class, [
                'choices' => [
                    'Projet planifié' => 'normal',
                    'Rapidement' => 'soon',
                    'Urgent' => 'urgent',
                    'Situation critique' => 'emergency',
                ],
                'mapped' => false,
            ])
            ->add('budgetMin', MoneyType::class, [
                'required' => false,
                'currency' => false,
                'divisor' => 1,
            ])
            ->add('budgetMax', MoneyType::class, [
                'required' => false,
                'currency' => false,
                'divisor' => 1,
            ])
            ->add('desiredStartAt', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('propertyType', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Appartement' => 'apartment',
                    'Maison' => 'house',
                    'Bureau' => 'office',
                    'Local commercial' => 'shop',
                    'Parties communes' => 'common',
                ],
                'placeholder' => 'Sélectionnez',
            ])
            ->add('surfaceM2', NumberType::class, [
                'required' => false,
                'scale' => 2,
            ])
            ->add('accessDetails', TextareaType::class, [
                'required' => false,
            ])
            ->add('availabilityNote', TextType::class, [
                'required' => false,
            ])
            ->add('addressLine1', TextType::class, [
                'required' => false,
            ])
            ->add('postalCode', TextType::class)
            ->add('city', TextType::class)
            ->add('district', TextType::class, [
                'required' => false,
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event): void {
            /** @var ServiceRequest $serviceRequest */
            $serviceRequest = $event->getData();
            $urgency = $event->getForm()->get('urgency')->getData();

            $serviceRequest->setIsUrgent(
                in_array($urgency, ['urgent', 'emergency'], true)
            );
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ServiceRequest::class,
        ]);
        $resolver->setRequired('artisan');
        $resolver->setAllowedTypes('artisan', ArtisanProfile::class);
    }
}
