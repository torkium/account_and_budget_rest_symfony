<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Bank;
use App\Repository\BankRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/banks', name: 'app_api_bank')]
class BankController extends AbstractController
{

    #[Route('/', name: 'app_api_bank_index', methods: 'GET')]
    public function index(BankRepository $bankRepository)
    {
        $banks = $bankRepository->findAll();
        return $this->json($banks, 200, [], ['groups' => ['bank_get']]);
    }

    #[Route('/{id}', name: 'app_api_bank_show', methods: 'GET')]
    public function show(Bank $bank)
    {
        return $this->json($bank, 200, [], ['groups' => ['bank_get']]);
    }

    #[Route('/', name: 'app_api_bank_create', methods: 'POST')]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, EntityManagerInterface $entityManager)
    {
        $data = json_decode($request->getContent(), true);

        $bank = new Bank();
        $bank->setLabel($data['label']);
        $entityManager->persist($bank);
        $entityManager->flush();

        return $this->json($bank, 201, [], ['groups' => ['bank_get']]);
    }

    #[Route('/{id}', name: 'app_api_bank_edit', methods: 'PUT')]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Bank $bank, EntityManagerInterface $entityManager)
    {
        $data = json_decode($request->getContent(), true);

        $bank->setLabel($data['label']);

        $entityManager->flush();

        return $this->json($bank, 200, [], ['groups' => ['bank_get']]);
    }

    #[Route('/{id}', name: 'app_api_bank_delete', methods: 'DELETE')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Bank $bank, EntityManagerInterface $entityManager)
    {
        $entityManager->remove($bank);
        $entityManager->flush();

        return new JsonResponse(null, 204);
    }
}
