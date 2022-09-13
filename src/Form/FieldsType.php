<?php

namespace App\Form;

use App\Entity\Fields;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\GlobalService;

class FieldsType extends AbstractType
{
    private $session;
    private $global_s;

    public function __construct(GlobalService $global_s, SessionInterface $session){
        $this->session = $session;
        $this->global_s = $global_s;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('cle', ChoiceType::class, [
                'label' => false,
                'required' => false,
                'choices' =>  array_flip($this->global_s->getTypeFields()),
                'attr' => array(
                    'class' => 'js-example-basic-multiple form-control'
                )
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Fields::class,
        ]);
    }
}
