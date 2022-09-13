<?php

namespace App\Form;

use App\Entity\ConfigTotal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ConfigTotalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('ht', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'data-role' => 'tagsinput'
                )
            ))
            ->add('ttc', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'data-role' => 'tagsinput'
                )
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ConfigTotal::class,
        ]);
    }
}
