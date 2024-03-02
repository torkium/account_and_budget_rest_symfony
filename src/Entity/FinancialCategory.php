<?php

namespace App\Entity;

use App\Enum\FinancialCategoryTypeEnum;
use App\Repository\FinancialCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

#[ORM\Entity(repositoryClass: FinancialCategoryRepository::class)]
class FinancialCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["financial_category_get"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["financial_category_get"])]
    private ?string $label = null;

    #[ORM\ManyToOne(targetEntity: FinancialCategory::class)]
    #[ORM\JoinColumn(name: "financial_category_id", referencedColumnName: "id", nullable: true)]
    #[Groups(["financial_category_get_parent"])]
    #[MaxDepth(1)]
    private ?FinancialCategory $parent = null;

    #[ORM\OneToMany(targetEntity: FinancialCategory::class, mappedBy: "parent", cascade: ['persist', 'remove'])]
    #[Groups(["financial_category_get_children"])]
    #[MaxDepth(1)]
    private Collection $children;

    #[ORM\Column(type: 'string', enumType: FinancialCategoryTypeEnum::class)]
    #[Groups(["financial_category_get"])]
    private FinancialCategoryTypeEnum $type;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    #[Groups(["user_get"])]
    private ?User $user = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function hasParent(): bool
    {
        return !empty($this->getParent());
    }

    public function getParent(): ?FinancialCategory
    {
        return $this->parent;
    }

    public function setParent(?FinancialCategory $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }
    
    #[Groups(["financial_category_get_parent_id"])]
    public function getParentId(): ?int{
        return $this->getParent()?->getId();
    }

    public function addChild(FinancialCategory $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(FinancialCategory $child): self
    {
        if ($this->children->removeElement($child) && $child->getParent() === $this) {
            $child->setParent(null);
        }

        return $this;
    }
    public function getType(): FinancialCategoryTypeEnum
    {
        return $this->type;
    }

    public function setType(FinancialCategoryTypeEnum $type): self
    {
        $this->type = $type;

        return $this;
    }
}
