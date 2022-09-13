<?php

namespace App\Form;

use App\Entity\Location;
use App\Entity\Paiement;
use App\Entity\Chantier;
use App\Entity\CompteRendu;
use App\Entity\Logement ;
use App\Entity\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\GlobalService;

class LocationType extends AbstractType
{
    private $session;
    private $global_s;

    public function __construct(SessionInterface $session, GlobalService $global_s){
        $this->session = $session;
        $this->global_s = $global_s;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('identifiant', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('debut_bail', DateTimeType::class, array(
                'input'=>'datetime',
                'required' => false,
                'widget' => 'single_text',
                'html5' => false,
                'label' => false,
                'format'=> 'dd/MM/yyyy',
                'attr' => array(
                    'class' => 'form-control datepicker',
                    'data-date-format'=> "dd/mm/yyyy"
                )
            ))
            ->add('fin_bail', DateTimeType::class, array(
                'input'=>'datetime',
                'required' => false,
                'widget' => 'single_text',
                'html5' => false,
                'label' => false,
                'format'=> 'dd/MM/yyyy',
                'attr' => array(
                    'class' => 'form-control datepicker',
                    'data-date-format'=> "dd/mm/yyyy"
                )
            ))
            ->add('date_paiement', DateTimeType::class, array(
                'input'=>'datetime',
                'required' => false,
                'widget' => 'single_text',
                'html5' => false,
                'label' => false,
                'format'=> 'dd/MM/yyyy',
                'attr' => array(
                    'class' => 'form-control datepicker',
                    'data-date-format'=> "dd/mm/yyyy"
                )
            ))
            ->add('periodicite', DateTimeType::class, array(
                'input'=>'datetime',
                'required' => false,
                'widget' => 'single_text',
                'html5' => false,
                'label' => false,
                'format'=> 'dd/MM/yyyy',
                'attr' => array(
                    'class' => 'form-control datepicker',
                    'data-date-format'=> "dd/mm/yyyy"
                )
            ))
            ->add('generateur_loyer', ChoiceType::class, array(
                'choices' => $this->global_s->buildGenerateurLoyer(),
                'label'=>false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('echeance_paiement', ChoiceType::class, array(
                'choices' => array_flip($this->global_s->getEcheancePaiement()),
                'label'=>false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('loyer_charge', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                    'placeholder' => '€'
                )
            ))
            ->add('loyer_charge_comprise', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                    'placeholder' => '€'
                )
            ))
            ->add('tva', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number'
                )
            ))
            ->add('loyer_hc', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                    'placeholder' => '€'
                )
            ))
            ->add('paiement', EntityType::class, array(
                'class' => Paiement::class,
                'required' => false,
                'label' => false,
                'choice_label' => 'nom',
                'attr' => array(
                    'class' => 'form-control'
                )
            )) 
            ->add('bien', EntityType::class, array(
                'class' => Chantier::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->andWhere('c.entreprise = :entreprise')
                        ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                        ->orderBy('c.nameentreprise', 'ASC');
                },
                'required' => false,
                'label' => false,
                'choice_label' => 'nameentreprise',
                'attr' => array(
                    'class' => 'form-control'
                )
            ))   
            ->add('depot_garantie', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                    'placeholder' => '€'
                )
            ))
            ->add('logement_paiement', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                    'placeholder' => '€'
                )
            ))
            ->add('frais_retard', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                    'pattern' => "[0-9]+(\.[0-9]{1,9})?%?"
                )
            ))
            ->add('frais_retard_percent', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                    'placeholder' => '%'
                )
            ))
            ->add('indice_referentiel_1', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            )) 
            ->add('indice_referentiel_2', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('periode', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('loyer_reference', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                    'placeholder' => '€'
                )
            ))
            ->add('loyer_majore', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                    'placeholder' => '€'
                )
            ))
            ->add('complement_loyer', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                    'placeholder' => '€'
                )
            ))
            ->add('complement_description', TextareaType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'rows'=>"7",
                    'class' => 'form-control'
                )
            ))
            ->add('dernier_loyer', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                    'placeholder' => '€'
                )
            ))
            ->add('date_versement', DateTimeType::class, array(
                'input'=>'datetime',
                'required' => false,
                'widget' => 'single_text',
                'html5' => false,
                'label' => false,
                'format'=> 'dd/MM/yyyy',
                'attr' => array(
                    'class' => 'form-control datepicker',
                    'data-date-format'=> "dd/mm/yyyy"
                )
            ))
            ->add('date_derniere_revision', DateTimeType::class, array(
                'input'=>'datetime',
                'required' => false,
                'widget' => 'single_text',
                'html5' => false,
                'label' => false,
                'format'=> 'dd/MM/yyyy',
                'attr' => array(
                    'class' => 'form-control datepicker',
                    'data-date-format'=> "dd/mm/yyyy"
                )
            ))
            ->add('logement', EntityType::class, array(
                'class' => Logement::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->andWhere('s.entreprise = :entreprise')
                        ->setParameter('entreprise', $this->session->get('entreprise_session_id'));
                },
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('locataire', EntityType::class, array(
                'class' => Client::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->andWhere('s.entreprise = :entreprise')
                        ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                        ->orderBy('s.nom', 'ASC');
                },
                'required' => false,
                'label' => false,
                'choice_label' => 'nom',
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('mandat_sepa', FileType::class, [
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
            ->add('etat_lieux', FileType::class, [
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
            ->add('diagnostique', FileType::class, [
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
            ->add('attestation_assurance', FileType::class, [
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
            ->add('cheque_depot_garantie', FileType::class, [
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
            ->add('offre_location_signe', FileType::class, [
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
            ->add('facture_eau', FileType::class, [
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
            ->add('plan', FileType::class, [
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
            ->add('bail', FileType::class, [
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
            ->add('diag_plomb', FileType::class, [
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
            ->add('certificat_mesurage', FileType::class, [
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
            ->add('diag_amiante', FileType::class, [
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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Location::class,
        ]);
    }
}
