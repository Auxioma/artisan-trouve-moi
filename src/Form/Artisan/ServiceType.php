<?php

namespace App\Form\Artisan;

use App\Entity\Catalog\ArtisanService;
use App\Entity\Catalog\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Enum\PriceUnit;

class ServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de la prestation',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('priceFrom', MoneyType::class, [
                'label' => 'Prix à partir de',
                'required' => false,
                'currency' => 'EUR',
                'divisor' => 1,
            ])
            ->add('priceUnit', EnumType::class, [
                'label' => 'Unité de prix',
                'class' => PriceUnit::class,
                'choice_label' => static fn (PriceUnit $unit): string => $unit->name,
            ])
            ->add('estimatedDurationHours', IntegerType::class, [
                'label' => 'Durée estimée en heures',
                'required' => false,
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Position',
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Prestation active',
                'required' => false,
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'id',
                'placeholder' => 'Choisissez une catégorie',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ArtisanService::class,
        ]);
    }
}