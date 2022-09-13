<?php

namespace App\Form;

use App\Entity\PreferenceField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\GlobalService;

class PreferenceFieldType extends AbstractType
{
    private $session;
    private $global_s;

    public function __construct(SessionInterface $session, GlobalService $global_s){
        $this->session = $session;
        $this->global_s = $global_s;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // ->add('required', CheckboxType::class, array(
            //     'label' => false,
            //     'required' => false,
            //     'attr' => array(
            //         'class' => ''
            //     )
            // ))
            ->add('label', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('identifiant', ChoiceType::class, array(
                'choices' => array_flip($this->global_s->getFieldKey()),
                'label'=>false,
                'required' => true,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('type', ChoiceType::class, array(
                'choices' => array_flip($this->global_s->getType()),
                'label'=>false,
                'required' => true,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PreferenceField::class,
        ]);
    }
}
