<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\FinancialCategory;
use App\Enum\FinancialCategoryTypeEnum;

class FinancialCategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $income = $this->createCategory($manager, 'Revenus', FinancialCategoryTypeEnum::Income);
        $logement = $this->createCategory($manager, 'Logement', FinancialCategoryTypeEnum::EssentialFixedExpense);
        $alimentation = $this->createCategory($manager, 'Alimentation', FinancialCategoryTypeEnum::EssentialVariableExpense);
        $transport = $this->createCategory($manager, 'Transport', FinancialCategoryTypeEnum::EssentialVariableExpense);
        $loisirs = $this->createCategory($manager, 'Loisirs', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense);
        $sante = $this->createCategory($manager, 'Santé', FinancialCategoryTypeEnum::EssentialVariableExpense);
        $education = $this->createCategory($manager, 'Education', FinancialCategoryTypeEnum::EssentialVariableExpense);
        $habillage = $this->createCategory($manager, 'Habillage et soins personnels', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense);
        $donsCadeaux = $this->createCategory($manager, 'Dons et cadeaux', FinancialCategoryTypeEnum::DonationCharity);
        $savings = $this->createCategory($manager, 'Épargne', FinancialCategoryTypeEnum::Savings);
        $investments = $this->createCategory($manager, 'Investissements', FinancialCategoryTypeEnum::Investment);
        $debtsLoans = $this->createCategory($manager, 'Dettes et Prêts', FinancialCategoryTypeEnum::DebtRepayment);
        $this->createCategory($manager, 'Salaire', FinancialCategoryTypeEnum::Income, $income);
        $this->createCategory($manager, 'Bonus', FinancialCategoryTypeEnum::Income, $income);
        $this->createCategory($manager, 'Revenus locatifs', FinancialCategoryTypeEnum::Income, $income);
        $this->createCategory($manager, 'Intérêts et dividendes', FinancialCategoryTypeEnum::Income, $income);
        $this->createCategory($manager, 'Freelance', FinancialCategoryTypeEnum::Income, $income);
        $this->createCategory($manager, 'Autres revenus', FinancialCategoryTypeEnum::Income, $income);
        $this->createCategory($manager, 'Loyer ou prêt immobilier', FinancialCategoryTypeEnum::EssentialFixedExpense, $logement);
        $this->createCategory($manager, 'Assurances', FinancialCategoryTypeEnum::EssentialFixedExpense, $logement);
        $this->createCategory($manager, 'Eau, gaz, électricité', FinancialCategoryTypeEnum::EssentialFixedExpense, $logement);
        $this->createCategory($manager, 'Internet et téléphone', FinancialCategoryTypeEnum::EssentialFixedExpense, $logement);
        $this->createCategory($manager, 'Courses', FinancialCategoryTypeEnum::EssentialVariableExpense, $alimentation);
        $this->createCategory($manager, 'Carburant', FinancialCategoryTypeEnum::EssentialVariableExpense, $transport);
        $this->createCategory($manager, 'Transports publics', FinancialCategoryTypeEnum::EssentialVariableExpense, $transport);
        $this->createCategory($manager, 'Entretien véhicule', FinancialCategoryTypeEnum::EssentialVariableExpense, $transport);
        $this->createCategory($manager, 'Assurance', FinancialCategoryTypeEnum::EssentialVariableExpense, $transport);
        $this->createCategory($manager, 'Assurances santé / Mutuelle', FinancialCategoryTypeEnum::EssentialVariableExpense, $sante);
        $this->createCategory($manager, 'Médicaments', FinancialCategoryTypeEnum::EssentialVariableExpense, $sante);
        $this->createCategory($manager, 'Consultations médicales', FinancialCategoryTypeEnum::EssentialVariableExpense, $sante);
        $this->createCategory($manager, 'Restaurants', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $loisirs);
        $this->createCategory($manager, 'Cinéma, concerts', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $loisirs);
        $this->createCategory($manager, 'Abonnements (streaming, magazines)', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $loisirs);
        $this->createCategory($manager, 'Sports et activités', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $loisirs);
        $this->createCategory($manager, 'Scolarité', FinancialCategoryTypeEnum::EssentialVariableExpense, $education);
        $this->createCategory($manager, 'Livres et fournitures', FinancialCategoryTypeEnum::EssentialVariableExpense, $education);
        $this->createCategory($manager, 'Cours extra-scolaires', FinancialCategoryTypeEnum::EssentialVariableExpense, $education);
        $this->createCategory($manager, 'Vêtements', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $habillage);
        $this->createCategory($manager, 'Coiffure et esthétique', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $habillage);
        $this->createCategory($manager, 'Dons caritatifs', FinancialCategoryTypeEnum::DonationCharity, $donsCadeaux);
        $this->createCategory($manager, 'Cadeaux', FinancialCategoryTypeEnum::DonationCharity, $donsCadeaux);
        $this->createCategory($manager, 'Compte épargne', FinancialCategoryTypeEnum::Savings, $savings);
        $this->createCategory($manager, 'Livret A', FinancialCategoryTypeEnum::Savings, $savings);
        $this->createCategory($manager, 'Livret de développement durable (LDD)', FinancialCategoryTypeEnum::Savings, $savings);
        $this->createCategory($manager, 'Assurance-vie', FinancialCategoryTypeEnum::Savings, $savings);
        $this->createCategory($manager, 'Plan épargne logement (PEL)', FinancialCategoryTypeEnum::Savings, $savings);
        $immobilier = $this->createCategory($manager, 'Immobilier', FinancialCategoryTypeEnum::Investment, $investments);
        $this->createCategory($manager, 'Achat pour louer', FinancialCategoryTypeEnum::Investment, $immobilier);
        $this->createCategory($manager, 'SCPI', FinancialCategoryTypeEnum::Investment, $immobilier);
        $marchesFinanciers = $this->createCategory($manager, 'Marchés financiers', FinancialCategoryTypeEnum::Investment, $investments);
        $this->createCategory($manager, 'Actions', FinancialCategoryTypeEnum::Investment, $marchesFinanciers);
        $this->createCategory($manager, 'Obligations', FinancialCategoryTypeEnum::Investment, $marchesFinanciers);
        $this->createCategory($manager, 'Fonds mutuels', FinancialCategoryTypeEnum::Investment, $marchesFinanciers);
        $this->createCategory($manager, 'Comptes de trading', FinancialCategoryTypeEnum::Investment, $marchesFinanciers);
        $this->createCategory($manager, 'Cryptomonnaies', FinancialCategoryTypeEnum::Investment, $investments);
        $this->createCategory($manager, 'Or et métaux précieux', FinancialCategoryTypeEnum::Investment, $investments);
        $this->createCategory($manager, 'Prêt immobilier', FinancialCategoryTypeEnum::DebtRepayment, $debtsLoans);
        $this->createCategory($manager, 'Crédit à la consommation', FinancialCategoryTypeEnum::DebtRepayment, $debtsLoans);
        $this->createCategory($manager, 'Prêt étudiant', FinancialCategoryTypeEnum::DebtRepayment, $debtsLoans);
        $this->createCategory($manager, 'Cartes de crédit', FinancialCategoryTypeEnum::DebtRepayment, $debtsLoans);
    }

    private function createCategory(ObjectManager $manager, string $label, FinancialCategoryTypeEnum $type, ?FinancialCategory $parent = null): FinancialCategory
    {
        $category = new FinancialCategory();
        $category->setLabel($label);
        $category->setType($type);

        if ($parent !== null) {
            $category->setParent($parent);
        }

        $manager->persist($category);
        $manager->flush();

        return $category;
    }
}
