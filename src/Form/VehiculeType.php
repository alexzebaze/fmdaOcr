<?php

namespace App\Form;

use App\Entity\Vehicule;
use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\GlobalService;

class VehiculeType extends AbstractType
{
    private $session;
    private $global_s;

    public function __construct(GlobalService $global_s, SessionInterface $session){
        $this->session = $session;
        $this->global_s = $global_s;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('immatriculation', TextType::class, array(
                'label' => false,
                'required' => true,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('marque', TextType::class, array(
                'label' => false,
                'required' => true,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('modele', TextType::class, array(
                'label' => false,
                'required' => true,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('type_carburant', TextType::class, array(
                'label' => false,
                'required' => true,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('conso_moyenne', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('cout_moyen', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('financement', ChoiceType::class, array(
                'label' => false,
                'required' => false,
                'choices' =>  array_flip($this->global_s->getTabFinancement()),
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('status', ChoiceType::class, array(
                'label' => false,
                'required' => false,
                'choices' =>  array_flip($this->global_s->getVehiculeStatus()),
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('logo_marque', FileType::class, [
                'label' => 'Telecharger le logo',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            "image/png",
                            "image/jpeg",
                            "image/jpg",
                            "image/gif"
                        ],
                        'mimeTypesMessage' => 'Veillez entrer une image valide',
                    ])
                ],
            ])
            ->add('carte_totale', FileType::class, [
                'label' => 'Telecharger la carte totale',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            "image/png",
                            "image/jpeg",
                            "image/jpg",
                            "application/pdf"
                        ],
                        'mimeTypesMessage' => 'Veillez entrer un document valide',
                    ])
                ],
            ])
            ->add('carte_grise', FileType::class, [
                'label' => 'Telecharger la carte grise',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            "image/png",
                            "image/jpeg",
                            "image/jpg",
                            "application/pdf"
                        ],
                        'mimeTypesMessage' => 'Veillez entrer un document valide',
                    ])
                ],
            ])
            ->add('assurance', FileType::class, [
                'label' => 'Telecharger l\'assurance',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            "image/png",
                            "image/jpeg",
                            "image/jpg",
                            "application/pdf"
                        ],
                        'mimeTypesMessage' => 'Veillez entrer un document valide',
                    ])
                ],
            ])
            ->add('date_service', DateType::class, array(
                'label' => false,
                'required' => false,
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'form-control datepicker'
                )
            ))
            ->add('date_ctr_tech', DateType::class, array(
                'label' => false,
                'required' => false,
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'form-control datepicker'
                )
            ))
            ->add('debut_credit_bail', DateType::class, array(
                'label' => false,
                'required' => false,
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'form-control datepicker'
                )
            ))
            ->add('fin_credit_bail', DateType::class, array(
                'label' => false,
                'required' => false,
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'form-control datepicker'
                )
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Vehicule::class,
        ]);
    }
}
