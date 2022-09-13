<?php

namespace App\Form;

use App\Entity\Devise;
use App\Entity\Reglement;
use App\Entity\Fournisseurs;
use App\Entity\Clients;
use App\Entity\Entreprise;
use App\Entity\Paiement;
use App\Entity\Tva;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Validator\Constraints\File;

class ReglementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('commentaire', TextareaType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'rows'=>4,
                    'class' => 'form-control'
                )
            ))
            ->add('date_reglement', DateTimeType::class, array(
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
            ->add('montant_non_encaisse', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                )
            ))
            ->add('montant_reglement', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                )
            ))
            ->add('restant_encaisse', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                )
            ))
            ->add('paiement', EntityType::class, array(
                'class' => Paiement::class,
                'required' => true,
                'label' => false,
                'choice_label' => 'nom',
                'attr' => array(
                    'class' => 'form-control'
                )
            ))     
            ->add('document', FileType::class, array(
                'label' => false,
                "multiple" => true,
                'attr' => array(
                    'class' => 'form-control',
                    'multiple'=>'multiple'
                ),
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'application/pdf'
                        ],
                        'mimeTypesMessage' => 'Le fichier doit être un jpg, gif, pdf ou png',
                    ])
                ],
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Reglement::class,
        ]);
    }
}
