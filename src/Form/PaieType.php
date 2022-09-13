<?php

namespace App\Form;

use App\Entity\Paie;
use App\Entity\Utilisateur;
use App\Entity\Fournisseurs;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
class PaieType extends AbstractType
{
    private $session;

    public function __construct(SessionInterface $session){
        $this->session = $session;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('heure_sup_1', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field',
                )
            ))
            ->add('heure_sup_2', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field',
                )
            ))
            ->add('heure_normale', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field',
                )
            ))
            ->add('date_paie', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field'
                )
            ))
            ->add('panier', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field',
                )
            ))
            ->add('trajet', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field',
                )
            ))
            ->add('tx_horaire', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field field-number',
                )
            ))
            ->add('cout_global', NumberType::class, array(
                'label' => false,
                'required' => true,
                'attr' => array(
                    'class' => 'form-control field field-number',
                )
            ))
            ->add('salaire_net', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field field-number',
                )
            ))
            ->add('conges_paye', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field field-number',
                )
            ))
            ->add('document_file', HiddenType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field'
                )
            ))
            ->add('utilisateur', EntityType::class, array(
                'class' => Utilisateur::class,
                'required' => true,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->andWhere('u.entreprise = :entreprise')
                        ->andWhere('u.etat = 1')
                        ->setParameter('entreprise', $this->session->get('entreprise_session_id'));
                },
                'label' => false,
                'attr' => array(
                    'class' => 'form-control field'
                )
            ))

        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Paie::class,
        ]);
    }
}
