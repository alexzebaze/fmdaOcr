<?php

namespace App\Form;

use App\Entity\Chantier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class ChantierType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nameentreprise', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('city', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('numero', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('m2', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('ignoreIa', CheckboxType::class, array(
                'label' => false,
                'required' => false,
            ))
            ->add('address', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('distance', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('temps_route', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('cp', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('description', TextareaType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('date_signature_compromis', DateTimeType::class, array(
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
            ->add('date_signature_acte', DateTimeType::class, array(
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
            ->add('prix_acquisition', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                    'placeholder' => '€'
                )
            ))
            ->add('frais_agence', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                    'placeholder' => '€'
                )
            ))
            ->add('frais_negociation', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                    'placeholder' => '€'
                )
            ))
            ->add('frais_bancaire', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                    'placeholder' => '€'
                )
            ))
            ->add('frais_huissier', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                    'placeholder' => '€'
                )
            ))
            ->add('prorata_taxe_fonciere', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                    'placeholder' => '€'
                )
            ))
            ->add('prorata_copropriete', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control field-number',
                    'placeholder' => '€'
                )
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Chantier::class,
        ]);
    }
}
