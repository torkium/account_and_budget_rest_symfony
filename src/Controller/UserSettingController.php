<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserSetting;
use App\Enum\FrequencyEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/user-settings', name: 'app_api_user_setting')]
class UserSettingController extends AbstractController
{

    #[Route('/', name: 'app_api_user_setting_show', methods: 'GET')]
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException('This user does not have access to user settings.');
        }

        $userSetting = $user->getUserSetting();
        if (!$userSetting) {
            $userSetting = new UserSetting();
            $userSetting->setUser($user);
        }

        return $this->json($userSetting, Response::HTTP_OK, [], ['groups' => ['user_setting_get']]);
    }

    #[Route('/', name: 'app_api_user_setting_update', methods: ['PUT'])]
    public function update(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException('This user does not have access to update user settings.');
        }

        $data = json_decode($request->getContent(), true);
        $userSetting = $user->getUserSetting();
        if (!$userSetting) {
            // Create new UserSetting if not exists
            $userSetting = new UserSetting();
            $userSetting->setUser($user);
            $userSetting->setDisplayFrequency(FrequencyEnum::MONTHLY);
            $entityManager->persist($userSetting);
        }

        if (isset($data['displayFrequency'])) {
            $userSetting->setDisplayFrequency(FrequencyEnum::from($data['displayFrequency']));
        }

        $entityManager->flush();

        return $this->json($userSetting, Response::HTTP_OK, [], ['groups' => ['user_setting_get']]);
    }
}
