<?php

namespace App\Form;

use App\Entity\Entreprise;
use App\Entity\Galerie;
use App\Entity\Chantier;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class GalerieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fichier', FileType::class, array(
                'label' => false,
                "multiple" => true,
                'attr' => array(
                    'class' => 'form-control',
                    'multiple'=>'multiple'
                ),
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '1024M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'application/pdf',
                            'video/mp4',
                            'video/x-flv',
                            'application/x-mpegURL',
                            'video/MP2T',
                            'video/3gpp',
                            'video/quicktime',
                            'video/x-msvideo',
                            'video/x-ms-wmv'
                        ],
                        'mimeTypesMessage' => 'Le fichier doit être un jpg, gif, pdf ou png pour une image ou un mp4 pour les vidéos',
                        'maxSizeMessage' => 'Le fichier ne doit pas dépasser 1024 MO'
                    ])
                ],
            ))
            ->add('chantier', EntityType::class, array(
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
            'data_class' => Galerie::class,
            'chantierid' => null,
            'entreprise' => null,
        ]);
    }
}
