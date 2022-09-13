<?php

namespace App\Form;

use App\Entity\Menu;
use Symfony\Component\Form\AbstractType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class MenuType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('libelle', TextType::class, array(
                'label' => false,
                'required' => true,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('link', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('icone', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('parent', EntityType::class, array(
                'class' => Menu::class,
                'query_builder' => function (EntityRepository $er) use ($options) {
                    return  $er->createQueryBuilder('m')
                        ->andWhere('m.parent IS NULL')
                        ->orderBy('m.rang', 'ASC');
                },
                'required' => false,
                'label' => false,
                'choice_label' => 'libelle',
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Menu::class,
            'menu'=> null
        ]);
    }
}
