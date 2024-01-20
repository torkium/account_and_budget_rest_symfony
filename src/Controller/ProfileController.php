<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Profile;
use App\Entity\User;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/profile', name: 'app_api_profile')]
class ProfileController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/', name: 'app_api_profile_index', methods: 'GET')]
    public function index(ProfileRepository $profileRepository)
    {
        $profiles = $profileRepository->findBy(['user' => $this->getUser()]);
        return $this->json($profiles, 200, [], ['groups' => ['profile_get', 'user_get_join']]);
    }

    #[Route('/{id}', name: 'app_api_profile_show', methods: 'GET')]
    public function show(Profile $profile)
    {
        $this->denyAccessUnlessGranted('VIEW', $profile);
        return $this->json($profile, 200, [], ['groups' => ['profile_get', 'user_get_join']]);
    }

    #[Route('/', name: 'app_api_profile_create', methods: 'POST')]
    public function create(Request $request, EntityManagerInterface $entityManager)
    {
        $data = json_decode($request->getContent(), true);

        $profile = new Profile();
        $profile->setLabel($data['label']);
        
        $user = $this->security->getUser();

        $profile->setUser($user);
        $entityManager->persist($profile);
        $entityManager->flush();

        return $this->json($profile, 201, [], ['groups' => ['profile_get', 'user_get_join']]);
    }

    #[Route('/{id}/edit', name: 'app_api_profile_edit', methods: 'PUT')]
    public function edit(Request $request, Profile $profile, EntityManagerInterface $entityManager)
    {
        $this->denyAccessUnlessGranted('EDIT', $profile);
        $data = json_decode($request->getContent(), true);

        $profile->setLabel($data['label']);

        $entityManager->flush();

        return $this->json($profile, 200, [], ['groups' => ['profile_get', 'user_get_join']]);
    }

    #[Route('/{id}', name: 'app_api_profile_delete', methods: 'DELETE')]
    public function delete(Profile $profile, EntityManagerInterface $entityManager)
    {
        $this->denyAccessUnlessGranted('DELETE', $profile);
        $entityManager->remove($profile);
        $entityManager->flush();

        return new JsonResponse(null, 204);
    }
}
