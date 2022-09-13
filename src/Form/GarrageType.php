<?php

namespace App\Form;

use App\Entity\Garrage;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class GarrageType extends AbstractType
{
    private $session;

    public function __construct(SessionInterface $session){
        $this->session = $session;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('numero', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
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
            'data_class' => Garrage::class,
        ]);
    }
}
