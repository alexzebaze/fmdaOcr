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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class HoraireEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('time', NumberType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('chantierid', EntityType::class, array(
                'class' => Chantier::class,
                'label' => false,
                'data' => $options['chantierid'],
                'choice_label' => 'nameentreprise',
                'attr' => array(
                    'class' => 'form-control'
                ),
                'query_builder' => function (EntityRepository $er) use ($options)  {
                    return $er->createQueryBuilder('c')
                        ->andWhere('c.status != :status')
                        ->andWhere('c.entreprise = :entreprise')
                        ->setParameter('entreprise', $options["entreprise"])
                        ->setParameter('status', 0);
                },
            ))

        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Horaire::class,
            'chantierid' => null,
            'entreprise' => null,
        ]);
    }
}
