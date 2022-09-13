<?php

namespace App\Form;

use App\Entity\RossumConfig;
use App\Entity\Entreprise;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Service\GlobalService;

class RossumConfigType extends AbstractType
{
    private $global_s;
    public function __construct(GlobalService $global_s){
        $this->global_s = $global_s;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('mkey', ChoiceType::class, array(
                'choices' => $this->global_s->getRossumFolder(),
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('value', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RossumConfig::class,
        ]);
    }
}
