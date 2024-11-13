<?php

namespace App\Form;

use App\Entity\Fichier;
use App\Entity\Scategorie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class FichierUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fichier', FileType::class, [
                'label' => 'Fichier',
                'mapped' => false, // Ne pas mapper directement à l'entité Fichier
                'attr' => ['class' => 'form-control'],
                'label_attr' => ['class' => 'fw-bold'],
                'constraints' => [
                    new File([
                        'maxSize' => '200k',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Seuls les fichiers PDF, PNG et JPG sont acceptés.',
                    ]),
                ],
            ])
            ->add('scategories', EntityType::class, [
                'class' => Scategorie::class,
                'choices' => $options['scategories'],
                'choice_label' => 'libelle',
                'expanded' => true,
                'multiple' => true,
                'label' => false,
                'mapped' => false, // Non mappé si on ne souhaite pas l'enregistrer directement
            ])
            ->add('ajouter', SubmitType::class, [
                'attr' => ['class' => 'btn bg-primary text-white m-4'],
                'row_attr' => ['class' => 'text-center'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Fichier::class,
            'scategories' => [],
        ]);
    }
}
