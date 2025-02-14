<?php

namespace App\Form;

use App\Entity\Categorie;
use App\Entity\Scategorie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class SupprCategorieForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('categories', EntityType::class, [
                'class' => Categorie::class,
                'choices' => $options['categories'],
                'choice_label' => 'nom',
                'expanded' => true,
                'label' => false,
                'mapped' => false,
                'multiple' => true,
                'required' => false
            ])
            ->add('scategories', EntityType::class, [
                'class' => Scategorie::class,
                'choices' => $options['scategories'],
                'choice_label' => 'libelle',
                'expanded' => true,
                'label' => false,
                'mapped' => false,
                'multiple' => true,
                'required' => false
            ])
            ->add('supprimer_c', SubmitType::class, [
                'label' => 'Supprimer la sélection',
                'attr' => ['class' => 'btn btn-primary m-4']
            ])
            ->add('supprimer_sc', SubmitType::class, [
                'label' => 'Supprimer la sélection',
                'attr' => ['class' => 'btn btn-danger m-4']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'categories' => [],
            'scategories' => []
        ]);
    }
}
