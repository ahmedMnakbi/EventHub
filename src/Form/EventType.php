<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Venue;
use App\Enum\EventStatus;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotNull;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $posterConstraints = [
            // Checks the real content of the file (not its name): it must be a real image.
            new Image(
                maxSize: '2M',
                mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                mimeTypesMessage: 'Formats acceptés : JPEG, PNG ou WebP.',
                maxSizeMessage: 'L\'image ne doit pas dépasser {{ limit }} {{ suffix }}.',
                minWidth: 300,
                maxWidth: 4000,
                minHeight: 300,
                maxHeight: 4000,
            ),
        ];
        if ($options['poster_required']) {
            $posterConstraints[] = new NotNull(message: 'L\'affiche de l\'événement est obligatoire.');
        }

        $builder
            ->add('title', TextType::class, ['label' => 'Titre'])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'label' => 'Catégorie',
                'placeholder' => '— Choisir —',
                'query_builder' => fn (EntityRepository $r) => $r->createQueryBuilder('c')->orderBy('c.name', 'ASC'),
            ])
            ->add('venue', EntityType::class, [
                'class' => Venue::class,
                'label' => 'Lieu',
                'placeholder' => '— Choisir —',
                'choice_label' => fn (Venue $v) => sprintf('%s (%s) · %d places', $v->getName(), $v->getCity(), $v->getCapacity()),
                'query_builder' => fn (EntityRepository $r) => $r->createQueryBuilder('v')->orderBy('v.name', 'ASC'),
            ])
            ->add('startAt', DateTimeType::class, [
                'label' => 'Début',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('endAt', DateTimeType::class, [
                'label' => 'Fin',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('status', EnumType::class, [
                'class' => EventStatus::class,
                'label' => 'Statut',
                'choice_label' => fn (EventStatus $s) => $s->label(),
                'help' => 'Un événement ne peut être publié qu\'après l\'ajout d\'au moins un type de billet.',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['rows' => 6],
            ])
            ->add('posterFile', FileType::class, [
                'label' => 'Affiche',
                'mapped' => false,
                'required' => $options['poster_required'],
                'constraints' => $posterConstraints,
                'help' => 'JPEG, PNG ou WebP, 2 Mo maximum, entre 300 et 4000 px de côté.',
                'attr' => ['accept' => 'image/jpeg,image/png,image/webp'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
            'poster_required' => false,
        ]);
        $resolver->setAllowedTypes('poster_required', 'bool');
    }
}
