<?php

namespace App\Form;

use App\Entity\Business;
use App\Entity\BusinessType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BusinessFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class)
            ->add('city', TextType::class)
            ->add('street', TextType::class)
            ->add('houseNumber', TextType::class)
            ->add('phoneNumber', TextType::class)
            ->add('businessType', EntityType::class, [
                'class' => BusinessType::class,
                'choice_label' => 'name'
            ]);

        if ($options['is_create']) {
            $builder
                ->add('email', EmailType::class, [
                    'mapped' => false,
                    'required' => true,
                    'label' => 'Account Email'
                ])
                ->add('password', PasswordType::class, [
                    'mapped' => false,
                    'required' => true,
                    'label' => 'Account Password'
                ]);
        }

        $builder->add('submit', SubmitType::class,[
            'label' => $options['is_create'] ? 'Create Business' : 'Save Business',
            'attr' => [
                'class' => 'btn btn-success'
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Business::class,
            'is_create' => false,
        ]);
    }
}
