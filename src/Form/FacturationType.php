<?php

namespace App\Form;

use App\Entity\Devise;
use App\Entity\Facturation;
use App\Entity\Fournisseurs;
use App\Entity\Clients;
use App\Entity\Paiement;
use App\Entity\Tva;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FacturationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('numero', TextType::class, array(
                'label' => false,
                'required' => false,
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
            ->add('prixht', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('prixttc', NumberType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control',
                    'readonly' => true
                )
            ))
            ->add('facturedAt', DateType::class, array(
                'label' => false,
                'required' => false,
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'form-control datepicker'
                )
            ))
            ->add('dueAt', DateType::class, array(
                'label' => false,
                'required' => false,
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'form-control datepicker'
                )
            ))
            ->add('fournisseur', EntityType::class, array(
                'class' => Fournisseurs::class,
                'label' => false,
                'required' => true,
                'choice_label' => 'nom',
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('client', EntityType::class, array(
                'class' => Clients::class,
                'label' => false,
                'required' => true,
                'choice_label' => 'nom',
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('tva', EntityType::class, array(
                'class' => Tva::class,
                'label' => false,
                'required' => false,
                'choice_label' => 'valeur',
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('devise', EntityType::class, array(
                'class' => Devise::class,
                'label' => false,
                'required' => false,
                'choice_label' => 'nom',
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('reglement', EntityType::class, array(
                'class' => Paiement::class,
                'label' => false,
                'required' => false,
                'choice_label' => 'nom',
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('imageFile', FileType::class, [
                'required' => false,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )                
            ])            
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Facturation::class,
        ]);
    }
}
