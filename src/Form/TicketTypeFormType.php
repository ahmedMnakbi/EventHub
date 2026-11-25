<?php

namespace App\Form;

use App\Entity\TicketType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** Form for a ticket type. "sold" and "event" are deliberately not fields: they cannot be changed by the user. */
class TicketTypeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Standard, VIP, Étudiant…'],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Prix (DT)',
                'input' => 'string',
                'scale' => 2,
                'html5' => true,
                'attr' => ['min' => 0, 'step' => '0.01'],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Nombre de places',
                'attr' => ['min' => 1],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => TicketType::class]);
    }
}
