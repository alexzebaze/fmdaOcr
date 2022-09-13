<?php

namespace App\Form;

use App\Entity\Pret;
use App\Entity\Banque;
use App\Entity\Chantier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\GlobalService;

class PretType extends AbstractType
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
            ->add('echeance', ChoiceType::class, array(
                'choices' => array_flip($this->global_s->getPretEcheance()),
                'required' => true,
                'label'=>false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('capital', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number'
                )
            ))
            ->add('montant_echeance_1', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number'
                )
            ))
             ->add('evenement', ChoiceType::class, array(
                'choices' => array_flip(Pret::EVENEMENT),
                'required' => false,
                'label'=>false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('montant_echeance', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number'
                )
            ))
            ->add('cout_interet', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number'
                )
            ))->add('cout_assurance', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number'
                )
            ))
            ->add('cout_total', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number'
                )
            ))
            ->add('differe', ChoiceType::class, array(
                'choices' => array_flip($this->global_s->getPretDiffusion()),
                'required' => true,
                'label'=>false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('duree_differe', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('duree', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('taux', TextType::class, array(
                'label' => false,
                'required' => true,
                'attr' => array(
                    'class' => 'form-control field-number'
                )
            ))
            ->add('taux_assurance', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number'
                )
            ))
            ->add('capital_restant', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number'
                )
            ))
            ->add('interet1', TextType::class, array(
                'label' => false,
                'required' => true,
                'attr' => array(
                    'class' => 'form-control field-number'
                )
            ))
            ->add('dureeDiffere2', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number'
                )
            ))
            ->add('rembourssementInteret', CheckboxType::class, array(
                'label' => false,
                'required' => false,
            ))
            ->add('montantDeblocageDiffere', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number'
                )
            ))
            ->add('montantDeblocage', TextType::class, array(
                'label' => false,
                'required' => true,
                'attr' => array(
                    'class' => 'form-control field-number'
                )
            ))
            ->add('remboursement1', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number'
                )
            ))
            ->add('date_deblocage', DateTimeType::class, array(
                'input'=>'datetime',
                'required' => true,
                'widget' => 'single_text',
                'html5' => false,
                'label' => false,
                'format'=> 'dd/MM/yyyy',
                'attr' => array(
                    'class' => 'form-control datepicker',
                    'data-date-format'=> "dd/mm/yyyy"
                )
            ))
            ->add('debut_prelevement_interet', DateTimeType::class, array(
                'input'=>'datetime',
                'required' => true,
                'widget' => 'single_text',
                'html5' => false,
                'label' => false,
                'format'=> 'dd/MM/yyyy',
                'attr' => array(
                    'class' => 'form-control datepicker',
                    'data-date-format'=> "dd/mm/yyyy"
                )
            ))
            ->add('date_premiere_echeance', DateTimeType::class, array(
                'input'=>'datetime',
                'required' => true,
                'widget' => 'single_text',
                'html5' => false,
                'label' => false,
                'format'=> 'dd/MM/yyyy',
                'attr' => array(
                    'class' => 'form-control datepicker',
                    'data-date-format'=> "dd/mm/yyyy"
                )
            ))
            ->add('date_fin', DateTimeType::class, array(
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
            ->add('contrat', FileType::class, [
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
            ->add('banque', EntityType::class, array(
                'class' => Banque::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->andWhere('s.entreprise = :entreprise')
                        ->setParameter('entreprise', $this->session->get('entreprise_session_id'));
                },
                'required' => true,
                'label' => false,
                'choice_label' => 'nom',
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('bien', EntityType::class, array(
                'class' => Chantier::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->andWhere('s.entreprise = :entreprise')
                        ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                        ->andWhere('s.status = :status')
                        ->setParameter('status', 1)
                        ->orderBy('s.nameentreprise', "ASC");
                },
                'required' => false,
                'label' => false,
                'choice_label' => 'nameentreprise',
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Pret::class,
        ]);
    }
}
