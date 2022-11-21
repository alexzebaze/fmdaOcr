<?php

namespace App\Form;

use App\Entity\Vente;
use App\Entity\Devise;
use App\Entity\Client;
use App\Entity\Entreprise;
use App\Entity\Paiement;
use App\Entity\Status;
use App\Entity\Lot;
use App\Entity\Tva;
use App\Entity\Chantier;
use Symfony\Component\Form\AbstractType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class VenteType extends AbstractType
{
    private $session;

    public function __construct(SessionInterface $session){
        $this->session = $session;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('chantier', EntityType::class, array(
                'class' => Chantier::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->andWhere('c.entreprise = :entreprise')
                        ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                        ->orderBy('c.nameentreprise', 'ASC');;
                },
                'required' => false,
                'label' => false,
                'choice_label' => 'nameentreprise',
                'attr' => array(
                    'class' => 'form-control field'
                )
            ))
            ->add('document_id', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field'
                )
            ))
            ->add('facturedAt', DateType::class, array(
                'label' => false,
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'form-control datepicker field'
                )
            ))
            ->add('devis', EntityType::class, array(
                'class' => Vente::class,
                'required' => false,
                'query_builder' => function (EntityRepository $er) use ($options) {
                    $rq = $er->createQueryBuilder('d')
                    ->andWhere('d.entreprise = :entreprise')
                    ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                    ->andWhere('d.type = :type')
                    ->setParameter('type', 'devis_client');
                    
                    if(!is_null($options) && array_key_exists("chantier", $options) &&!is_null($options['chantier'])){
                        $rq = $rq->andWhere('d.chantier = :chantier')
                        ->setParameter('chantier', $options['chantier']);
                    }
                    if(!is_null($options) && array_key_exists("params", $options) && !is_null($options['params'])){
                        $rq = $rq->andWhere('d.id IN (:in)')
                        ->setParameter('in', $options['params']);
                    }

                    return $rq;
                },
                'label' => false,
                'attr' => array(
                    'class' => 'form-control field'
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
                    'class' => 'form-control field'
                )
            )) 
            ->add('status', EntityType::class, array(
                'class' => Status::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->andWhere('s.entreprise = :entreprise')
                        ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                        ->orderBy('s.name', 'ASC');
                },
                'required' => false,
                'label' => false,
                'choice_label' => 'name',
                'attr' => array(
                    'class' => 'form-control field'
                )
            ))
            ->add('prixht', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field field-number'
                )
            ))
            ->add('prixttc', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field field-number',
                )
            ))
            ->add('client', EntityType::class, array(
                'class' => Client::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->andWhere('c.entreprise = :entreprise')
                        ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                        ->orderBy('c.nom', 'ASC');
                },
                'required' => false,
                'label' => false,
                'choice_label' => 'nom',
                'attr' => array(
                    'class' => 'form-control field'
                )
            ))
            ->add('tva', EntityType::class, array(
                'class' => Tva::class,
                'required' => false,
                'label' => false,
                'choice_label' => 'valeur',
                'attr' => array(
                    'class' => 'form-control field field-number'
                )
            ))
             
            ->add('dueAt', DateType::class, array(
                'label' => false,
                'required' => false,
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'form-control datepicker field'
                )
            ))
            ->add('document_file', FileType::class, [
                'label' => 'Telecharger le document',
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Vente::class,
            'params' => null
        ]);
    }
}
