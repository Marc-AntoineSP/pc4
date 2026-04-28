<?php

declare(strict_types=1);

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
use Symfony\Component\Validator\Constraints\Length;

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
                'constraints' => [
                    new Length(
                        min: 0,
                        max: 1000,
                        minMessage: 'Summary must be at least {{ limit }} characters long.',
                        maxMessage: 'Summary cannot be longer than {{ limit }} characters.',
                    ),
                ],
            ])
            ->add('summary', TextareaType::class, [
                'label' => 'Summary',
                'attr' => [
                    'rows' => 5,
                ],
                'constraints' => [
                    new Length(
                        min: 10,
                        max: 5000,
                        minMessage: 'Summary must be at least {{ limit }} characters long.',
                        maxMessage: 'Summary cannot be longer than {{ limit }} characters.',
                    ),
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
