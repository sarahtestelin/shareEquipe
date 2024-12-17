<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType; // Importation du SubmitType
use Symfony\Component\OptionsResolver\OptionsResolver;

class AjoutAmiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail de l\'ami',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Saisir l\'email de la personne que vous souhaitez inviter',
                ],
            ])
            ->add('submit', SubmitType::class, [ 
                'label' => 'DEMANDER',
                'attr' => [
                    'class' => 'btn btn-primary btn-submit-center',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([

        ]);
    }
}
