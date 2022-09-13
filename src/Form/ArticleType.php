<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\Lot;
use App\Entity\Tva;
use App\Entity\Fournisseurs;
use App\Entity\Fabricant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\GlobalService;

class ArticleType extends AbstractType
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
            ->add('type', ChoiceType::class, [
                'label' => false,
                'choices' =>  array_flip($this->global_s->getTypeArticles()),
                'attr' => array(
                    'class' => 'form-control'
                )
            ])
            ->add('unite_mesure', TextType::class, array(
                'label' => false,
                'required' => true,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('libelle', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('prix_achat', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number var_calcul_marge_brute',
                )
            ))
            ->add('marge_brut', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number var_calcul_marge_brute disabled',
                )
            ))
            ->add('prix_vente_ht', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number var_calcul_marge_brute',
                )
            ))
            ->add('taux_tva', EntityType::class, array(
                'class' => Tva::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('l')
                        ->orderBy('l.valeur', 'ASC');
                },
                'required' => false,
                'label' => false,
                'choice_label' => 'valeur',
                'attr' => array(
                    'class' => 'form-control field'
                )
            ))
            ->add('fabricant', EntityType::class, array(
                'class' => Fabricant::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('l')
                        ->orderBy('l.nom', 'ASC');
                },
                'required' => false,
                'label' => false,
                'choice_label' => 'nom',
                'attr' => array(
                    'class' => 'form-control js-example-basic-single'
                )
            ))
            ->add('pourcentage_marge', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number disabled',
                )
            ))
            ->add('prix_vente_ttc', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                )
            ))
            ->add('sommeil', CheckboxType::class, [
                'label'    => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control',
                )
            ])
            ->add('image', FileType::class, [
                'label' => "Telecharger l'image",
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            "image/png",
                            "image/jpeg",
                            "image/jpg",
                        ],
                        'mimeTypesMessage' => 'Veillez entrer un document valide',
                    ])
                ],
            ])
            ->add('code', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control disabled'
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
                'required' => true,
                'label' => false,
                'choice_label' => 'lot',
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('code_article_fournisseur', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control disabled'
                )
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
