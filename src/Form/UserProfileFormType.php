<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class UserProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr' => ['placeholder' => 'your@email.com'],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'New Password (leave blank to keep current)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => 'At least 8 characters',
                ],
                'constraints' => [
                    new Length(
                        min: 8,
                        minMessage: 'Your password should be at least {{ limit }} characters',
                        max: 4096,
                    ),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Update Profile',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
