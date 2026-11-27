<?php

namespace App\Form;

use App\Entity\TicketType;
use App\Entity\Venue;
use App\Entity\Zone;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** Form for a ticket type. "event" is never a field: it is set by the controller. */
class TicketTypeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Venue $venue */
        $venue = $options['venue'];

        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Standard, VIP, Étudiant…'],
            ])
            ->add('zone', EntityType::class, [
                'class' => Zone::class,
                'label' => 'Zone',
                'placeholder' => '— Choisir —',
                // Only the zones of the event's venue can be chosen (any other id is rejected).
                'query_builder' => fn (EntityRepository $r) => $r->createQueryBuilder('z')
                    ->andWhere('z.venue = :venue')
                    ->setParameter('venue', $venue)
                    ->orderBy('z.name', 'ASC'),
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
        $resolver->setRequired('venue');
        $resolver->setAllowedTypes('venue', Venue::class);
    }
}
