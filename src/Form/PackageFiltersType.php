<?php

namespace App\Form;

use App\Dto\PackageSearchFilter;
use App\Entity\Business;
use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PackageFiltersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $cityChoices = [];
        foreach ($options['cities'] as $city) {
            $cityChoices[$city] = $city;
        }

        $builder
            ->add('name', SearchType::class, [
                'required' => false,
                'label' => 'Name',
                'attr' => [
                    'placeholder' => 'Search a word ...'
                ]
            ])
            ->add('minPrice', NumberType::class, [
                'required' => false,
                'label' => 'Min Price',
                'attr' => [
                    'placeholder' => 'Min price'
                ]
            ])
            ->add('maxPrice', NumberType::class, [
                'required' => false,
                'label' => 'Max Price',
                'attr' => [
                    'placeholder' => 'Max price'
                ]
            ])
            ->add('category', EntityType::class, [
                'required' => false,
                'placeholder' => 'All categories',
                'label' => 'Category',
                'class' => Category::class,
                'choice_label' => 'name',
            ])
            ->add('city', ChoiceType::class, [
                'choices' => $cityChoices,
                'required' => false,
                'placeholder' => 'All cities',
                'label' => 'City'
            ]);

        if ($options['show_business']) {
            $builder->add('business', EntityType::class, [
                'required' => false,
                'placeholder' => 'All businesses',
                'label' => 'Business',
                'class' => Business::class,
                'choice_label' => 'name',
            ]);
        }

        $builder->add('submit', SubmitType::class, [ 'label' => 'Filter' ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PackageSearchFilter::class,
            'show_business' => true,
            'cities' => [],
        ]);
    }
}
