<?php

namespace App\Form;

use App\Entity\Book;
use App\Enum\BookTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookCreationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
            ])
            ->add('author', TextType::class, [
                'label' => 'Author',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'rows' => 3,
                ],
            ])
            ->add('summary', TextareaType::class, [
                'label' => 'Summary',
                'attr' => [
                    'rows' => 5,
                ],
            ])
            ->add('type', EnumType::class, [
                'class' => BookTypeEnum::class,
                'label' => 'Type',
                'placeholder' => 'Select a type',
                'choice_label' => static fn (BookTypeEnum $choice): string => $choice->label(),
            ])
            ->add('pages', IntegerType::class, [
                'label' => 'Pages',
            ])
            ->add('isAvailable', CheckboxType::class, [
                'label' => 'Available for checkout',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Book::class,
        ]);
    }
}
