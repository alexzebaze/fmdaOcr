<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Chantier;
use App\Entity\Horaire;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class HoraireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('chantierid', EntityType::class, array(
                'class' => Chantier::class,
                'query_builder' => function (EntityRepository $er) use ($options)  {
                    return $er->createQueryBuilder('c')
                        ->andWhere('c.entreprise = :entreprise')
                        ->andWhere('c.status = :status')
                        ->setParameter('entreprise', $options["entreprise"])
                        ->setParameter('status', 1)
                        ->orderBy('c.nameentreprise', 'ASC');
                },
                'label' => false,
                'choice_label' => 'nameentreprise',
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('fonction', EntityType::class, array(
                'class' => Category::class,
                'query_builder' => function (EntityRepository $er) use ($options)  {
                    return $er->createQueryBuilder('t')
                        ->andWhere('t.entreprise = :entreprise')
                        ->setParameter('entreprise', $options["entreprise"]);
                },
                'label' => false,
                'choice_label' => 'category',
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('datestart', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('dateend', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('pause', ChoiceType::class, array(
                'label' => false,
                'choices' => array(
                    '30 minutes' => 0.5,
                    '60 minutes' => 1,
                    'Aucune' => 0
                ),
                'attr' => array(
                    'class' => 'form-control'
                ),
                'mapped' => false
            ))

        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Horaire::class,
            'entreprise' => null
        ]);
    }
}
