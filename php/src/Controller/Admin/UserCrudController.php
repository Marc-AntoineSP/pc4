<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Enum\UserStatusEnum;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<User>
 */
class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $statusChoices = [];
        foreach (UserStatusEnum::cases() as $status) {
            $statusChoices[strtoupper($status->value)] = $status;
        }

        yield IdField::new('id')->hideOnForm();
        yield TextField::new('email');
        yield ChoiceField::new('status')
            ->setChoices($statusChoices)
            ->renderAsNativeWidget();
    }
}
