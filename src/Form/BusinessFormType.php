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
            ->add('name', TextType::class, [
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        'message' => 'Please enter the business name.',
                    ]),
                ],
            ])
            ->add('city', TextType::class, [
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        'message' => 'Please enter the city.',
                    ]),
                ],
            ])
            ->add('street', TextType::class, [
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        'message' => 'Please enter the street name.',
                    ]),
                ],
            ])
            ->add('houseNumber', TextType::class, [
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        'message' => 'Please enter the house number.',
                    ]),
                ],
            ])
            ->add('phoneNumber', TextType::class, [
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        'message' => 'Please enter a phone number.',
                    ]),
                    new \Symfony\Component\Validator\Constraints\Regex([
                        'pattern' => '/^\+40[237]\d{8}$/',
                        'message' => 'Please enter a valid Romanian phone number (e.g. 0712345678).',
                    ]),
                ],
            ])
            ->add('businessType', EntityType::class, [
                'class' => BusinessType::class,
                'choice_label' => 'name'
            ]);

        if ($options['is_create']) {
            $builder
                ->add('email', EmailType::class, [
                    'mapped' => false,
                    'required' => true,
                    'label' => 'Account Email',
                    'constraints' => [
                        new \Symfony\Component\Validator\Constraints\NotBlank([
                            'message' => 'Please enter an email address.',
                        ]),
                        new \Symfony\Component\Validator\Constraints\Email([
                            'message' => 'Please enter a valid email address.',
                        ]),
                    ],
                ])
                ->add('password', PasswordType::class, [
                    'mapped' => false,
                    'required' => true,
                    'label' => 'Account Password',
                    'constraints' => [
                        new \Symfony\Component\Validator\Constraints\NotBlank([
                            'message' => 'Please enter a password.',
                        ]),
                        new \Symfony\Component\Validator\Constraints\Length([
                            'min' => 8,
                            'minMessage' => 'Your password should be at least {{ limit }} characters',
                            'max' => 4096,
                        ]),
                    ],
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
