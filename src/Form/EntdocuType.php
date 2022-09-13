<?php

namespace App\Form;

use App\Entity\Entdocu;
use App\Entity\Chantier;
use App\Entity\Lot;
use App\Entity\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use App\Service\GlobalService;

class EntdocuType extends AbstractType
{
    private $global_s;
    private $session;

    public function __construct(GlobalService $global_s, SessionInterface $session){
        $this->global_s = $global_s;
        $this->session = $session;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('document_id', TextType::class, array(
                'label' => false,
                'required' => true,
                'attr' => array(
                    'class' => 'form-control disabled'
                )
            ))
            ->add('echeance', DateTimeType::class, array(
                'input'=>'datetime',
                'widget' => 'single_text',
                'html5' => false,
                'label' => false,
                'format'=> 'dd/MM/yyyy',
                'attr' => array(
                    'class' => 'form-control datepicker',
                    'data-date-format'=> "dd/mm/yyyy"
                )
            ))
            ->add('total_ht', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                )
            ))
            ->add('total_ttc', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                )
            ))
            ->add('create_at', DateTimeType::class, array(
                'input'=>'datetime',
                'widget' => 'single_text',
                'html5' => false,
                'label' => false,
                'format'=> 'dd/MM/yyyy',
                'attr' => array(
                    'class' => 'form-control datepicker',
                    'data-date-format'=> "dd/mm/yyyy"
                )
            ))
            ->add('info', TextareaType::class, array(
                'label' => false,
                'required' => true,
                'attr' => array(
                    'class' => 'form-control',
                    'rows'=>"2"
                )
            ))
            ->add('description_travaux', TextareaType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control',
                    'rows'=>"2"
                )
            ))
            ->add('client', EntityType::class, array(
                'class' => Client::class,
                'required' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('l')
                        ->andWhere('l.entreprise = :entreprise')
                        ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                        ->orderBy('l.nom', 'ASC');
                },
                'label' => false,
                'choice_label' => 'nom',
                'attr' => array(
                    'class' => 'js-example-basic-single form-control'
                )
            ))
            ->add('chantier', EntityType::class, array(
                'class' => Chantier::class,
                'required' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('l')
                        ->andWhere('l.entreprise = :entreprise')
                        ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                        ->orderBy('l.nameentreprise', 'ASC');
                },
                'label' => false,
                'choice_label' => 'nameentreprise',
                'attr' => array(
                    'class' => 'js-example-basic-single form-control'
                )
            ))
            ->add('lot', EntityType::class, array(
                'class' => Lot::class,
                'required' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('l')
                        ->andWhere('l.entreprise = :entreprise')
                        ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                        ->orderBy('l.lot', 'ASC');
                },
                'label' => false,
                'choice_label' => 'lot',
                'attr' => array(
                    'class' => 'js-example-basic-single form-control'
                )
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Entdocu::class,
        ]);
    }
}
