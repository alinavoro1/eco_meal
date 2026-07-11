<?php

namespace App\Form;

use App\Dto\BusinessSearchFilter;
use App\Entity\BusinessType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BusinessFiltersType extends AbstractType
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
            ->add('city', ChoiceType::class, [
                'choices' => $cityChoices,
                'required' => false,
                'placeholder' => 'All cities',
                'label' => 'City'
            ])
            ->add('businessType', EntityType::class, [
                'required' => false,
                'placeholder' => 'All business types',
                'label' => 'Business Type',
                'class' => BusinessType::class,
                'choice_label' => 'name',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Filter'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BusinessSearchFilter::class,
            'cities' => [],
        ]);
    }
}
