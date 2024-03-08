<?php

namespace App\Controller;

use App\Entity\FinancialCategory;
use App\Entity\User;
use App\Enum\FinancialCategoryTypeEnum;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Serializer\SerializerInterface;

class UserController extends AbstractController
{
    #[Route('/user/create', name: 'user_create', methods: ['POST'])]
    public function createUser(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, SerializerInterface $serializer): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true);

        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));

        $entityManager->persist($user);
        $entityManager->flush();
        $this->loadCategoriesForNewUser($entityManager, $user);
        $response = $serializer->serialize($user, 'json', ['groups' => 'user_get']);
        return new Response($response, Response::HTTP_CREATED, ['Content-Type' => 'application/json']);
    }

    private function loadCategoriesForNewUser(EntityManager $manager, User $user): void
    {
        $income = $this->createCategory($manager, 'Revenus', FinancialCategoryTypeEnum::Income, null, $user);
        $logement = $this->createCategory($manager, 'Logement', FinancialCategoryTypeEnum::EssentialFixedExpense, null, $user);
        $alimentation = $this->createCategory($manager, 'Alimentation', FinancialCategoryTypeEnum::EssentialVariableExpense, null, $user);
        $transport = $this->createCategory($manager, 'Transport', FinancialCategoryTypeEnum::EssentialVariableExpense, null, $user);
        $loisirs = $this->createCategory($manager, 'Loisirs', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, null, $user);
        $sante = $this->createCategory($manager, 'Santé', FinancialCategoryTypeEnum::EssentialVariableExpense, null, $user);
        $education = $this->createCategory($manager, 'Education', FinancialCategoryTypeEnum::EssentialVariableExpense, null, $user);
        $habillage = $this->createCategory($manager, 'Habillage et soins personnels', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, null, $user);
        $fraisBancaires = $this->createCategory($manager, 'Frais bancaires', FinancialCategoryTypeEnum::EssentialVariableExpense, null, $user);
        $impots = $this->createCategory($manager, 'Impôts et taxes', FinancialCategoryTypeEnum::EssentialVariableExpense, null, $user);
        $donsCadeaux = $this->createCategory($manager, 'Dons et cadeaux', FinancialCategoryTypeEnum::DonationCharity, null, $user);
        $savings = $this->createCategory($manager, 'Épargne', FinancialCategoryTypeEnum::Savings, null, $user);
        $investments = $this->createCategory($manager, 'Investissements', FinancialCategoryTypeEnum::Investment, null, $user);
        $debtsLoans = $this->createCategory($manager, 'Dettes et Prêts', FinancialCategoryTypeEnum::DebtRepayment, null, $user);
        $this->createCategory($manager, 'Salaire', FinancialCategoryTypeEnum::Income, $income, $user);
        $this->createCategory($manager, 'Bonus', FinancialCategoryTypeEnum::Income, $income, $user);
        $this->createCategory($manager, 'Revenus locatifs', FinancialCategoryTypeEnum::Income, $income, $user);
        $this->createCategory($manager, 'Intérêts et dividendes', FinancialCategoryTypeEnum::Income, $income, $user);
        $this->createCategory($manager, 'Freelance', FinancialCategoryTypeEnum::Income, $income, $user);
        $this->createCategory($manager, 'Autres revenus', FinancialCategoryTypeEnum::Income, $income, $user);

        $this->createCategory($manager, 'Loyer ou prêt immobilier', FinancialCategoryTypeEnum::EssentialFixedExpense, $logement, $user);
        $this->createCategory($manager, 'Travaux', FinancialCategoryTypeEnum::EssentialVariableExpense, $logement, $user);
        $this->createCategory($manager, 'Assurances', FinancialCategoryTypeEnum::EssentialFixedExpense, $logement, $user);
        $this->createCategory($manager, 'Eau, gaz, électricité', FinancialCategoryTypeEnum::EssentialFixedExpense, $logement, $user);
        $this->createCategory($manager, 'Internet et téléphone', FinancialCategoryTypeEnum::EssentialFixedExpense, $logement, $user);
        $maisonEtJardin = $this->createCategory($manager, 'Maison et jardin / Equipements', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $logement, $user);

        $this->createCategory($manager, 'Aménagement intérieur', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $maisonEtJardin, $user);
        $this->createCategory($manager, 'Électroménager ', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $maisonEtJardin, $user);
        $this->createCategory($manager, 'Accessoires de cuisine', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $maisonEtJardin, $user);
        $this->createCategory($manager, 'Aménagement extérieur', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $maisonEtJardin, $user);
        $this->createCategory($manager, 'Autres', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $maisonEtJardin, $user);

        $this->createCategory($manager, 'Courses', FinancialCategoryTypeEnum::EssentialVariableExpense, $alimentation, $user);

        $this->createCategory($manager, 'Carburant', FinancialCategoryTypeEnum::EssentialVariableExpense, $transport, $user);
        $this->createCategory($manager, 'Transports publics', FinancialCategoryTypeEnum::EssentialVariableExpense, $transport, $user);
        $this->createCategory($manager, 'Entretien véhicule', FinancialCategoryTypeEnum::EssentialVariableExpense, $transport, $user);
        $this->createCategory($manager, 'Assurance', FinancialCategoryTypeEnum::EssentialVariableExpense, $transport, $user);

        $this->createCategory($manager, 'Assurances santé / Mutuelle', FinancialCategoryTypeEnum::EssentialVariableExpense, $sante, $user);
        $this->createCategory($manager, 'Médicaments', FinancialCategoryTypeEnum::EssentialVariableExpense, $sante, $user);
        $this->createCategory($manager, 'Consultations médicales', FinancialCategoryTypeEnum::EssentialVariableExpense, $sante, $user);

        $this->createCategory($manager, 'Restaurants', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $loisirs, $user);
        $this->createCategory($manager, 'Livraison repas', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $loisirs, $user);
        $this->createCategory($manager, 'Cinéma, concerts, expos', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $loisirs, $user);
        $this->createCategory($manager, 'Abonnements (streaming, magazines)', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $loisirs, $user);
        $this->createCategory($manager, 'Sports et activités', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $loisirs, $user);
        $this->createCategory($manager, 'Autres dépenses', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $loisirs, $user);
        $vacances = $this->createCategory($manager, 'Vacances', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $loisirs, $user);

        $this->createCategory($manager, 'Hébergement', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $vacances, $user);
        $this->createCategory($manager, 'Transport', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $vacances, $user);
        $this->createCategory($manager, 'Restaurant', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $vacances, $user);
        $this->createCategory($manager, 'Courses', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $vacances, $user);
        $this->createCategory($manager, 'Autres frais', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $vacances, $user);

        $this->createCategory($manager, 'Multimédia', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $loisirs, $user);

        $this->createCategory($manager, 'Scolarité', FinancialCategoryTypeEnum::EssentialVariableExpense, $education, $user);
        $this->createCategory($manager, 'Livres et fournitures', FinancialCategoryTypeEnum::EssentialVariableExpense, $education, $user);
        $this->createCategory($manager, 'Cours extra-scolaires', FinancialCategoryTypeEnum::EssentialVariableExpense, $education, $user);

        $this->createCategory($manager, 'Frais courants', FinancialCategoryTypeEnum::EssentialVariableExpense, $fraisBancaires, $user);
        $this->createCategory($manager, 'Agios', FinancialCategoryTypeEnum::EssentialVariableExpense, $fraisBancaires, $user);
        $this->createCategory($manager, 'Autres frais', FinancialCategoryTypeEnum::EssentialVariableExpense, $fraisBancaires, $user);

        $this->createCategory($manager, 'Impôt sur le revenu', FinancialCategoryTypeEnum::EssentialVariableExpense, $impots, $user);
        $this->createCategory($manager, 'Taxe d\'habitation', FinancialCategoryTypeEnum::EssentialVariableExpense, $impots, $user);
        $this->createCategory($manager, 'Taxe foncière', FinancialCategoryTypeEnum::EssentialVariableExpense, $impots, $user);
        $this->createCategory($manager, 'Autres impositions et taxes', FinancialCategoryTypeEnum::EssentialVariableExpense, $impots, $user);

        $this->createCategory($manager, 'Vêtements', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $habillage, $user);
        $this->createCategory($manager, 'Coiffure et esthétique', FinancialCategoryTypeEnum::NonEssentialFlexibleExpense, $habillage, $user);

        $this->createCategory($manager, 'Dons caritatifs', FinancialCategoryTypeEnum::DonationCharity, $donsCadeaux, $user);
        $this->createCategory($manager, 'Cadeaux', FinancialCategoryTypeEnum::DonationCharity, $donsCadeaux, $user);

        $this->createCategory($manager, 'Compte épargne', FinancialCategoryTypeEnum::Savings, $savings, $user);
        $this->createCategory($manager, 'Livret A', FinancialCategoryTypeEnum::Savings, $savings, $user);
        $this->createCategory($manager, 'Livret de développement durable (LDD)', FinancialCategoryTypeEnum::Savings, $savings, $user);
        $this->createCategory($manager, 'Assurance-vie', FinancialCategoryTypeEnum::Savings, $savings, $user);
        $this->createCategory($manager, 'Plan épargne logement (PEL)', FinancialCategoryTypeEnum::Savings, $savings, $user);

        $immobilier = $this->createCategory($manager, 'Immobilier', FinancialCategoryTypeEnum::Investment, $investments, $user);
        $this->createCategory($manager, 'Achat pour louer', FinancialCategoryTypeEnum::Investment, $immobilier, $user);
        $this->createCategory($manager, 'SCPI', FinancialCategoryTypeEnum::Investment, $immobilier, $user);
        
        $marchesFinanciers = $this->createCategory($manager, 'Marchés financiers', FinancialCategoryTypeEnum::Investment, $investments, $user);
        $this->createCategory($manager, 'Actions', FinancialCategoryTypeEnum::Investment, $marchesFinanciers, $user);
        $this->createCategory($manager, 'Obligations', FinancialCategoryTypeEnum::Investment, $marchesFinanciers, $user);
        $this->createCategory($manager, 'Fonds mutuels', FinancialCategoryTypeEnum::Investment, $marchesFinanciers, $user);
        $this->createCategory($manager, 'Comptes de trading', FinancialCategoryTypeEnum::Investment, $marchesFinanciers, $user);
        $this->createCategory($manager, 'Cryptomonnaies', FinancialCategoryTypeEnum::Investment, $investments, $user);
        $this->createCategory($manager, 'Or et métaux précieux', FinancialCategoryTypeEnum::Investment, $investments, $user);
        $this->createCategory($manager, 'Prêt immobilier', FinancialCategoryTypeEnum::DebtRepayment, $debtsLoans, $user);
        $this->createCategory($manager, 'Crédit à la consommation', FinancialCategoryTypeEnum::DebtRepayment, $debtsLoans, $user);
        $this->createCategory($manager, 'Prêt étudiant', FinancialCategoryTypeEnum::DebtRepayment, $debtsLoans, $user);
        $this->createCategory($manager, 'Cartes de crédit', FinancialCategoryTypeEnum::DebtRepayment, $debtsLoans, $user);
    }

    private function createCategory(EntityManager $manager, string $label, FinancialCategoryTypeEnum $type, ?FinancialCategory $parent = null, User $user = null): FinancialCategory
    {
        $category = new FinancialCategory();
        $category->setLabel($label);
        $category->setType($type);

        if ($parent !== null) {
            $category->setParent($parent);
        }
        if ($user !== null) {
            $category->setUser($user);
        }

        $manager->persist($category);
        $manager->flush();

        return $category;
    }
}
