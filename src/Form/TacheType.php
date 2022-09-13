<?php

namespace App\Form;

use App\Entity\Category;
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
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class TacheType extends AbstractType
{
    private $session;

    public function __construct(SessionInterface $session){
        $this->session = $session;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('category', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            /*->add('parent', EntityType::class, array(
                'class' => Category::class,
                'label' => false,
                'choice_label' => 'category',
                'attr' => array(
                    'class' => 'form-control'
                ),
                'query_builder' => function (EntityRepository $er) use ($options)  {
                    return $er->createQueryBuilder('c')
                        ->andWhere('c.entreprise = :entreprise')
                        ->setParameter('entreprise', $this->session->get('entreprise_session_id'));
                },
            ))*/
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
            'entreprise' => null,
        ]);
    }
}
