<?php

namespace App\Form;

use App\Entity\ConfigImapEmail;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Service\GlobalService;

class ConfigImapEmailType extends AbstractType
{

    private $global_s;
    public function __construct(GlobalService $global_s){
        $this->global_s = $global_s;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('dossier', ChoiceType::class, array(
                'choices' => $this->global_s->getRossumFolder2(),
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('email', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('password', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('hote', TextType::class, array(
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
            'data_class' => ConfigImapEmail::class,
        ]);
    }
}
