<?php

namespace App\Form;

use App\Entity\Entreprise;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EntrepriseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', ChoiceType::class, array(
                'choices' => array(
                    'Entreprise individuelle' => 'Entreprise individuelle',
                    'EIRL' => 'EIRL',
                    'SARL' => 'SARL',
                    'EURL' => 'EURL',
                    'SAS' => 'SAS',
                    'SASU' => 'SASU',
                    'SA' => 'SA',
                    'SNC' => 'SNC'
                ),
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('name', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('gestionLotChantier', CheckboxType::class, array(
                'label' => false,
                'required' => false,
            ))
            ->add('bank', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('director', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('phone_director', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('email', EmailType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('phone', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('address', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('city', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('cp', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('siret', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('tva', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('ape', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('website', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('fax', TextType::class, array(
                'label' => false,
                'required'=>false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('code', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('sender_name', TextType::class, array(
                'required' => true,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('sender_mail', EmailType::class, array(
                'required' => true,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('rib', FileType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                ),
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'application/pdf',
                        ],
                        'mimeTypesMessage' => 'Le RIB ne doit pas dépasser les 2 Mo et doit être un jpg, gif, pdf ou png',
                    ])
                ],
            ))
            ->add('logo', FileType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                ),
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Le logo ne doit pas dépasser les 2 Mo et doit être un jpg, gif ou png',
                    ])
                ],
            ))
            ->add('logo_facture', FileType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                ),
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Le logo ne doit pas dépasser les 2 Mo et doit être un jpg, gif ou png',
                    ])
                ],
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Entreprise::class,
        ]);
    }
}
