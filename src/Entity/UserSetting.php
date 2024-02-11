<?php

namespace App\Entity;

use App\Enum\FrequencyEnum;
use App\Repository\UserSettingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserSettingRepository::class)]
class UserSetting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'userSetting')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["user_get"])]
    private User $user;

    #[ORM\Column(type: 'string', enumType: FrequencyEnum::class)]
    #[Groups(["user_setting_get"])]
    private FrequencyEnum $displayFrequency = FrequencyEnum::MONTHLY;

    // Getters and setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getDisplayFrequency(): FrequencyEnum
    {
        return $this->displayFrequency;
    }

    public function setDisplayFrequency(FrequencyEnum $displayFrequency): self
    {
        $this->displayFrequency = $displayFrequency;
        return $this;
    }
}
