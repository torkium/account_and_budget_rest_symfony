<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Bank;

class BankFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $banks = [
            "La Banque Postale",
            "Hello Bank",
            "Crédit du Nord",
            "Crédit Mutuel",
            "BNP Paribas",
            "Société Générale",
            "Caisse d'Epargne",
            "Banque Populaire",
            "LCL",
            "HSBC",
            "Barclays",
            "Crédit Agricole",
            "Natixis",
            "Banque Courtois",
            "Banque Kolb",
            "Banque Laydernier",
            "Banque Palatine",
            "Banque Rhône-Alpes",
            "CIC",
            "ING",
            "Monabanq",
            "N26",
            "Revolut",
            "Saxo Banque",
            "Fortuneo",
            "Banque BCP",
            "Banque de Savoie",
        ];
        foreach($banks as $bank){
            $this->createBank($manager, $bank);
        }
    }

    private function createBank(ObjectManager $manager, $label){
        $bank = new Bank();
        $bank->setLabel($label);
        $manager->persist($bank);
        $manager->flush();
    }
}
