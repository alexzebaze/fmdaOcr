<?php

namespace App\Form;

use App\Entity\Page;
use App\Entity\Fields;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\GlobalService;
class PageType extends AbstractType
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
            ->add('libelle', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('cle', ChoiceType::class, [
                'label' => false,
                'required' => false,
                'choices' =>  array_flip($this->global_s->getFieldsPage()),
                'attr' => array(
                    'class' => 'js-example-basic-multiple form-control'
                )
            ])
            ->add('fields', EntityType::class, array(
                'class' => Fields::class,
                'required' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('l')
                        ->orderBy('l.cle', 'ASC');
                },
                'multiple' => true,
                'label' => false,
                'choice_label' => 'cle',
                'attr' => array(
                    'class' => 'form-control',
                )
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Page::class,
        ]);
    }
}
