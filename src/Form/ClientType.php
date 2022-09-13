<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Entreprise;
use App\Entity\Chantier;
use Symfony\Component\Form\AbstractType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Doctrine\Common\Collections\Collection;

class ClientType extends AbstractType
{
    private $session;

    public function __construct(SessionInterface $session){
        $this->session = $session;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('chantiers', EntityType::class, array(
                'class' => Chantier::class,
                'multiple'=>true,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->andWhere('c.entreprise = :entreprise')
                        ->andWhere('c.status = :status')
                        ->setParameter('status', 1)
                        ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                        ->orderBy('c.nameentreprise', 'ASC');
                },
                'required' => false,
                'label' => false,
                'choice_label' => 'nameentreprise',
                'attr' => array(
                    'class' => 'form-control js-example-basic-multiple'
                )
            ))
            ->add('nom', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('lieu_naissance', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('date_naissance', DateTimeType::class, array(
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
            ->add('adresse', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('cp', NumberType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('f_facturation', NumberType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('ville', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('pays', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('telone', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('teltwo', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('telecopie', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('diversone', TextareaType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('diverstwo', TextareaType::class, array(
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
            ->add('type', ChoiceType::class, array(
                'label' => false,
                'required' => false,
                'choices' => [
                    "Prospect"=>"prospect",
                    "Client"=>"client"
                ],
                'attr' => array(
                    'class' => 'form-control',
                )
            ))
            ->add('web', TextType::class, array(
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
            ->add('code', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('prix', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number'
                )
            ))
            ->add('m2', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('cni', FileType::class, [
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
            ->add('logo', FileType::class, [
                'label' => 'Telecharger votre logo',
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
            /*->add('datecrea', DateType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('datemaj', DateType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))*/
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
            'chantier' => null
        ]);
    }
}
