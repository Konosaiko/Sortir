<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class SortieType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('dateHeureDebut', null, [
                'widget' => 'single_text',
            ])
            ->add('duration', null, [
                'label' => "Durée : "
            ])
            ->add('dateLimite', null, [
                'widget' => 'single_text',
            ])
            ->add('registerLimit', null, [
                'label' => "Nombre d'inscriptions maximum : "
            ])
            ->add('infos', null, [
                'label' => "Description : "
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'label' => 'Organisateur : ',
                'disabled' => true, // Désactivez le champ pour le cacher dans le formulaire
            ])
            ->add('place', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'nom',
                'label' => 'Campus : '
            ])
            ->add('address', EntityType::class, [
                'class' => Lieu::class,
                'choice_label' => 'nom',
                'label' => 'Adresse : '
            ])

            ->add('enregistrer', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn btn-primary'],
            ])
            ->add('publier', SubmitType::class, [
                'label' => 'Publier',
                'attr' => ['class' => 'btn btn-success'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);


    }
}