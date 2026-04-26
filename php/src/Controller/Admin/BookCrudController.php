<?php

namespace App\Controller\Admin;

use App\Entity\Book;
use App\Enum\BookTypeEnum;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BookCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Book::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $bookTypeChoices = [];
        foreach (BookTypeEnum::cases() as $bookType) {
            $bookTypeChoices[$bookType->label()] = $bookType;
        }

        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title');
        yield TextField::new('author');
        yield TextEditorField::new('description');
        yield TextEditorField::new('summary');
        yield IntegerField::new('pages');
        yield ChoiceField::new('type')
            ->setChoices($bookTypeChoices)
            ->formatValue(static function ($value): string {
                if ($value instanceof BookTypeEnum) {
                    return $value->label();
                }

                return (string) $value;
            });
        yield BooleanField::new('isAvailable');
    }
}
