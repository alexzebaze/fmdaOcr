<?php

namespace App\Form;

use App\Entity\Devise;
use App\Entity\Achat;
use App\Entity\Vente;
use App\Entity\Lot;
use App\Entity\Status;
use App\Entity\Fournisseurs;
use App\Entity\Clients;
use App\Entity\Entreprise;
use App\Entity\Paiement;
use App\Entity\Tva;
use App\Entity\Chantier;
use Symfony\Component\Form\AbstractType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\GlobalService;

class AchatType extends AbstractType
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
            ->add('chantier', EntityType::class, array(
                'class' => Chantier::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->andWhere('c.status = :status')
                        ->andWhere('c.entreprise = :entreprise')
                        ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                        ->setParameter('status', 1)
                        ->orderBy('c.nameentreprise', 'DESC');
                },
                'required' => false,
                'label' => false,
                'choice_label' => 'nameentreprise',
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
                    'class' => 'form-control field datepicker'
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
            ->add('fournisseur', EntityType::class, array(
                'class' => Fournisseurs::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('f')
                        ->andWhere('f.entreprise = :entreprise')
                        ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                        ->orderBy('f.nom', 'ASC');
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
            ->add('code_compta', ChoiceType::class, [
                'label' => false,
                'required' => false,
                'choices' =>  array_flip($this->global_s->getTabCodeCompta()),
                'attr' => array(
                    'class' => 'form-control field'
                )
            ])
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
            ->add('devis', EntityType::class, array(
                'class' => Vente::class,
                'query_builder' => function (EntityRepository $er) use ($options){
                        $rq = $er->createQueryBuilder('l')
                        ->andWhere('l.entreprise = :entreprise')
                        ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                        ->andWhere('l.type = :type')
                        ->setParameter('type', 'devis_client');
                        
                        if(!is_null($options['chantier'])){
                            $rq = $rq->andWhere('l.chantier = :chantier')
                            ->setParameter('chantier', $options['chantier']);
                        }
                        return $rq;
                },
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control field'
                )
            ))  
            ->add('dueAt', DateType::class, array(
                'label' => false,
                'required' => false,
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'form-control field datepicker'
                )
            ))
            ->add('document_file', HiddenType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field'
                )
            ))          
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Achat::class,
            'chantier' => null
        ]);
    }
}
