<?php

namespace App\Form;

use App\Entity\Utilisateur;
use App\Entity\Chantier;
use App\Entity\Configuration;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints\File;

class UtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('lastname', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('firstname', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('email', EmailType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('phone', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('address', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('city', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('cp', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('heureHebdo', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('categoryuser', ChoiceType::class, array(
                'label' => false,
                'choices' => array(
                    'Ouvrier' => 1,
                    'Chef de chantier' => 2,
                    'Apprenti' => 3,
                ),
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('poste', ChoiceType::class, array(
                'label' => false,
                'choices' => array(
                    'En poste' => 1,
                    'En congé' => 2,
                    'En arrêt' => 3
                ),
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('sousTraitant', ChoiceType::class, array(
                'label' => false,
                'choices' => array(
                    'Non' => 0,
                    'Oui' => 1,
                ),
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('panier', ChoiceType::class, array(
                'label' => false,
                'choices' => array(
                    'Oui' => 1,
                    'Non' => 0,
                ),
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('trajet', ChoiceType::class, array(
                'label' => false,
                'choices' => array(
                    'Oui' => 1,
                    'Non' => 0,
                ),
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('birth', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('date_entree', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('date_sortie', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('numeroSecu', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('tauxHoraire', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('coefficient', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('typeContrat', EntityType::class, array(
                'class' => Configuration::class,
                'query_builder' => function (EntityRepository $er)  {
                    return $er->createQueryBuilder('c')
                        ->andWhere('c.type = :type')
                        ->setParameter('type', 'contrat');
                },
                'label' => false,
                'choice_label' => 'nom',
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('image', FileType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                ),
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/heic',
                        ],
                        'mimeTypesMessage' => 'L\'image ne doit pas dépasser les 2 Mo et doit être un jpg, gif ou png',
                    ])
                ],
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
