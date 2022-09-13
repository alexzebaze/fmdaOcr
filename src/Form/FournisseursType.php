<?php

namespace App\Form;

use App\Entity\Fournisseurs;
use App\Entity\Entreprise;
use App\Entity\Paiement;
use App\Entity\Lot;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\GlobalService;

class FournisseursType extends AbstractType
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
            ->add('nom', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('nom2', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('typeBonLivraison', CheckboxType::class, array(
                'label' => false,
                'required' => false,
            ))
            ->add('adresse', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('totalConfig', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('totalConfigTtc', TextType::class, array(
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
            ->add('emailFactureElectronique', EmailType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('emailBl', EmailType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
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
            ->add('tel_contact', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('contact', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('email_contact', TextType::class, array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('lot', EntityType::class, array(
                'class' => Lot::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('l')
                        ->andWhere('l.entreprise = :entreprise')
                        ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                        ->orderBy('l.lot', 'ASC');
                },
                'required' => false,
                'label' => false,
                'choice_label' => 'lot',
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('paiement', EntityType::class, array(
                'class' => Paiement::class,
                'label' => false,
                'required' => true,
                'choice_label' => 'nom',
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('code_compta', ChoiceType::class, [
                'label' => false,
                'required' => false,
                'choices' =>  array_flip($this->global_s->getTabCodeCompta()),
                'attr' => array(
                    'class' => 'form-control'
                )
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
            'data_class' => Fournisseurs::class,
            'entreprise' => null
        ]);
    }
}
