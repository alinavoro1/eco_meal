<?php

namespace App\Form;

use App\Dto\OrderSearchFilter;
use App\Entity\Business;
use App\Entity\Consumer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderFiltersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('packageName', SearchType::class, [
                'required' => false,
                'label' => 'Package Name',
                'attr' => [
                    'placeholder' => 'Search a word ...'
                ]
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

        if ($options['show_consumer']) {
            $builder->add('consumer', EntityType::class, [
                'required' => false,
                'placeholder' => 'All consumers',
                'label' => 'Consumer',
                'class' => Consumer::class,
                'choice_label' => function (Consumer $consumer) {
                    return $consumer->getFirstName() . ' ' . $consumer->getLastName();
                },
            ]);
        }

        $builder->add('submit', SubmitType::class, [
            'label' => 'Filter'
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderSearchFilter::class,
            'show_business' => true,
            'show_consumer' => true,
        ]);
    }
}
