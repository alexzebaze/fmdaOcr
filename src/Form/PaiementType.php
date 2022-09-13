<?php

namespace App\Form;

use App\Entity\Paiement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaiementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('description', TextareaType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control note'
                )
            ))
            ->add('image', FileType::class, [
                'label' => "Telecharger l'image",
                "multiple" => true,
                'attr' => array(
                    'class' => 'form-control',
                    'multiple'=>'multiple'
                ),
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Paiement::class,
        ]);
    }
}
