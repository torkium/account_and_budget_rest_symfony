<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Bank;
use App\Repository\BankRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/bank', name: 'app_api_bank')]
class BankController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/', name: 'app_api_bank_index', methods: 'GET')]
    public function index(BankRepository $bankRepository)
    {
        $banks = $bankRepository->findBy(['user' => $this->getUser()]);
        return $this->json($banks, 200, [], ['groups' => ['bank_get', 'user_get_join']]);
    }

    #[Route('/{id}', name: 'app_api_bank_show', methods: 'GET')]
    public function show(Bank $bank)
    {
        $this->denyAccessUnlessGranted('VIEW', $bank);
        return $this->json($bank, 200, [], ['groups' => ['bank_get', 'user_get_join']]);
    }

    #[Route('/', name: 'app_api_bank_create', methods: 'POST')]
    public function create(Request $request, EntityManagerInterface $entityManager)
    {
        $data = json_decode($request->getContent(), true);

        $bank = new Bank();
        $bank->setLabel($data['label']);
        
        $user = $this->security->getUser();

        $bank->setUser($user);
        $entityManager->persist($bank);
        $entityManager->flush();

        return $this->json($bank, 201, [], ['groups' => ['bank_get', 'user_get_join']]);
    }

    #[Route('/{id}', name: 'app_api_bank_edit', methods: 'PUT')]
    public function edit(Request $request, Bank $bank, EntityManagerInterface $entityManager)
    {
        $this->denyAccessUnlessGranted('EDIT', $bank);
        $data = json_decode($request->getContent(), true);

        $bank->setLabel($data['label']);

        $entityManager->flush();

        return $this->json($bank, 200, [], ['groups' => ['bank_get', 'user_get_join']]);
    }

    #[Route('/{id}', name: 'app_api_bank_delete', methods: 'DELETE')]
    public function delete(Bank $bank, EntityManagerInterface $entityManager)
    {
        $this->denyAccessUnlessGranted('DELETE', $bank);
        $entityManager->remove($bank);
        $entityManager->flush();

        return new JsonResponse(null, 204);
    }
}
