<?php

namespace App\Controller\Admin;

use App\Entity\Specialite;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SpecialiteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Specialite::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(), // Hide ID when creating new ones
            
            TextField::new('nom', 'Nom de la Spécialité'),
            
            // This displays a color picker input and a color bubble in the list
            ColorField::new('couleur', 'Code Couleur'),

            // This creates a dropdown menu to pick the Department
            AssociationField::new('departement', 'Département Associé')
                ->setRequired(true),
        ];
    }
}