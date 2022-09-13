<?php

namespace App\Form;

use App\Entity\Logement;
use App\Entity\Chantier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\GlobalService;

class LogementType extends AbstractType
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
            ->add('batiment', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('escalier', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('etage', TextType::class, array(
                'label' => false,
                'required' => false,
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
            ->add('numero', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number'
                )
            ))
            ->add('annee_acquisition', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number'
                )
            ))
            ->add('etat', ChoiceType::class, [
                'label' => false,
                'required' => false,
                'choices' => [
                    "Location"=>"location",
                    "Vente"=>"vente",
                ],
                'attr' => array(
                    'class' => 'form-control'
                )
            ])
            ->add('type', ChoiceType::class, [
                'choices' => array_flip($this->global_s->getLogementType()),
                'label'=>false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ])
            ->add('superficie', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number'
                )
            ))
            ->add('superficie_terrasse', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number'
                )
            ))
            ->add('nombre_piece', ChoiceType::class, [
                'label' => false,
                'required' => false,
                'choices' => [
                    "STUDIO"=>"STUDIO",
                    "T2"=>"T2",
                    "T3"=>"T3",
                    "T4"=>"T4",
                    "T5"=>"T5",
                    "T6"=>"T6",
                ],
                'attr' => array(
                    'class' => 'form-control'
                )
            ])
            ->add('status', ChoiceType::class, [
                'label' => false,
                'required' => false,
                'choices' => [
                    "En Reflexion"=>"En Reflexion",
                    "Réservé"=>"Réservé",
                    "Vendu"=>"Vendu"
                ],
                'attr' => array(
                    'class' => 'form-control'
                )
            ])
            ->add('nombre_chambre', ChoiceType::class, array(
                'label' => false,
                'required' => false,
                'choices' => [
                    "1"=>"1",
                    "2"=>"2",
                    "3"=>"3",
                    "4"=>"4",
                    "5"=>"5",
                ],
                'attr' => array(
                    'class' => 'form-control field-number'
                )
            ))
            ->add('annee_construction', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number'
                )
            ))
            ->add('description', TextareaType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('web', TextareaType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('cave', ChoiceType::class, array(
                'label' => false,
                'required' => false,
                'choices' => [
                    "OUI"=>"OUI",
                    "NON"=>"NON",
                ],
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('balcon', ChoiceType::class, array(
                'label' => false,
                'required' => false,
                'choices' => [
                    "OUI"=>"OUI",
                    "NON"=>"NON",
                ],
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('superficie_cave', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('superficie_balcon', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('exposition', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('nombre_wc', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number'
                )
            ))
            ->add('permalien', TextareaType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('stationnement', ChoiceType::class, array(
                'label' => false,
                'required' => false,
                'choices' => [
                    "OUI"=>"OUI",
                    "NON"=>"NON",
                ],
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('terrasse', ChoiceType::class, array(
                'label' => false,
                'required' => false,
                'choices' => [
                    "OUI"=>"OUI",
                    "NON"=>"NON",
                ],
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('cellier', ChoiceType::class, array(
                'label' => false,
                'required' => false,
                'choices' => [
                    "OUI"=>"OUI",
                    "NON"=>"NON",
                ],
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('normes', TextareaType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('notes', TextareaType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('chantier', EntityType::class, array(
                'class' => Chantier::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->andWhere('c.entreprise = :entreprise')
                        ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                        ->orderBy('c.nameentreprise', 'ASC');
                },
                'required' => "required",
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
            'data_class' => Logement::class,
        ]);
    }
}
