<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Sortie;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Pseudo'
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom'
             ])
            ->add('name', TextType::class, [
                'label' => 'Nom'
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone'
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email'
            ]);

            if ($options['allow_password_change']) {
                $builder->add('plainPassword', RepeatedType::class, [
                    'type' => PasswordType::class,
                    'mapped' => false,
                    'required' => false,
                    'first_options'  => ['label' => 'Nouveau mot de passe (laisser vide pour ne pas changer)'],
                    'second_options' => ['label' => 'Confirmation du nouveau mot de passe'],
                    'invalid_message' => 'Les deux mots de passe doivent correspondre.',
                    'constraints' => [
                        new Length([
                            'min' => 6,
                            'max' => 4096,
                            'minMessage' => 'Votre mot de passe doit comporter au moins {{ limit }} caractères.',
                            'maxMessage' => 'Votre mot de passe ne peut pas dépasser {{ limit }} caractères.',
                        ]),
                    ],
                ]);
            }
            $builder
            ->add('profilePicture', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false, // Ne pas mapper ce champ à une propriété de l'entité
                'required' => false, // La photo de profil est facultative
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'allow_password_change' => true,
        ]);
    }
}
