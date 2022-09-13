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

class GalerieMobileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fichier', FileType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control'
                ),
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '120M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'application/pdf',
                            'video/mp4',
                            'video/mov',
                        ],
                        'mimeTypesMessage' => 'Le fichier doit être un jpg, gif, pdf ou png pour une image ou un mp4 pour les vidéos',
                        'maxSizeMessage' => 'Le fichier ne doit pas dépasser 120 MO'
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
            'csrf_protection' => false,
            'data_class' => Galerie::class,
            'chantierid' => null,
            'entreprise' => null,
        ]);
    }
}
