<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Venue;
use App\Enum\EventStatus;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Image;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $imageConstraints = [
            // Each file is checked on its real content (not its name): it must be a real image.
            new All([
                new Image(
                    maxSize: '2M',
                    mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                    mimeTypesMessage: 'Formats acceptés : JPEG, PNG ou WebP.',
                    maxSizeMessage: 'Chaque image doit faire au plus {{ limit }} {{ suffix }}.',
                    minWidth: 300,
                    maxWidth: 4000,
                    minHeight: 300,
                    maxHeight: 4000,
                ),
            ]),
            new Count(max: Event::MAX_IMAGES, maxMessage: 'Au plus {{ limit }} images par événement.'),
        ];
        if ($options['images_required']) {
            $imageConstraints[] = new Count(min: 1, minMessage: 'Ajoutez au moins une image (l\'affiche de l\'événement).');
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
                // Only venues split into zones can host an event (ticket types need a zone).
                'query_builder' => fn (EntityRepository $r) => $r->createQueryBuilder('v')
                    ->andWhere('SIZE(v.zones) > 0')
                    ->orderBy('v.name', 'ASC'),
                'help' => 'Seuls les lieux découpés en zones sont proposés.',
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
            ->add('imageFiles', FileType::class, [
                'label' => $options['images_required'] ? 'Images' : 'Ajouter des images',
                'mapped' => false,
                'multiple' => true,
                'required' => $options['images_required'],
                'constraints' => $imageConstraints,
                'help' => sprintf('JPEG, PNG ou WebP, 2 Mo maximum chacune, entre 300 et 4000 px de côté, %d images au plus. La première sert d\'affiche.', Event::MAX_IMAGES),
                'attr' => ['accept' => 'image/jpeg,image/png,image/webp'],
            ])
        ;

        if ($options['existing_images'] !== []) {
            // Choices = the current file names only: any other value is rejected by the form.
            $builder->add('removeImages', ChoiceType::class, [
                'label' => 'Images à supprimer',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => array_combine($options['existing_images'], $options['existing_images']),
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
            'images_required' => false,
            'existing_images' => [],
        ]);
        $resolver->setAllowedTypes('images_required', 'bool');
        $resolver->setAllowedTypes('existing_images', 'string[]');
    }
}
