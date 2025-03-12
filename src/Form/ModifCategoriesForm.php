<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ModifCategoriesForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('items', CollectionType::class, [
                'entry_type' => TextType::class,
                'entry_options' => ['label' => false],
                'allow_add' => false,
                'allow_delete' => false,
            ])
            ->add('modifier', SubmitType::class, [
                'label' => 'Modifier',
                'attr' => ['class' => 'btn bg-success m-4 border-1 text-white'],
                'row_attr' => ['class' => 'text-center'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }
}
