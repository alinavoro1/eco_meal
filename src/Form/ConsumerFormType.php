<?php

namespace App\Form;

use App\Entity\Consumer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsumerFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', TextType::class)
            ->add('lastName', TextType::class)
            ->add('phoneNumber', TextType::class);

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

        if ($options['include_submit']) {
            $builder->add('submit', SubmitType::class, [
                'label' => $options['is_create'] ? 'Create Consumer' : 'Save Consumer'
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Consumer::class,
            'include_submit' => true,
            'is_create' => false,
        ]);
    }
}
