<?php

namespace App\Form;

use App\Entity\Venue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VenueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom du lieu'])
            ->add('address', TextType::class, ['label' => 'Adresse'])
            ->add('city', TextType::class, ['label' => 'Ville'])
            ->add('capacity', IntegerType::class, [
                'label' => 'Capacité (places)',
                'attr' => ['min' => 1],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Venue::class]);
    }
}
