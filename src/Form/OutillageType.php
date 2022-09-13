<?php

namespace App\Form;

use App\Entity\Outillage;
use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OutillageType extends AbstractType
{
    private $session;

    public function __construct(SessionInterface $session){
        $this->session = $session;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('note', TextareaType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control note'
                )
            ))
            ->add('modele', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('num_serie', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('marque', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('gestion', ChoiceType::class, array(
                'choices' => array("Disposition"=>"disposition", "Distribué"=>"distribue"),
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('mise_en_service', DateType::class, array(
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
            ->add('derniere_inspection', DateTimeType::class, array(
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
            ->add('proprietaire', ChoiceType::class, array(
                'choices' => array("Achat"=>"achat", "Location"=>"location"),
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('status', ChoiceType::class, array(
                'choices' => array("OK"=>1, "HS"=>0),
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('image', FileType::class, [
                'label' => "Telecharger l'image",
                "multiple" => true,
                'attr' => array(
                    'class' => 'form-control',
                    'multiple'=>'multiple'
                ),
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
            ->add('utilisateur', EntityType::class, array(
                'class' => Utilisateur::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->andWhere('u.etat = :verif')
                        ->andWhere('u.entreprise = :entreprise')
                        ->setParameter('verif', 1)
                        ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                        ->orderBy('u.lastname', 'ASC');
                },
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Outillage::class,
        ]);
    }
}
