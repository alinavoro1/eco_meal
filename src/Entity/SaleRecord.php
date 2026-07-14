<?php

namespace App\Entity;

use App\Repository\SaleRecordRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SaleRecordRepository::class)]
class SaleRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Business $business = null;

    #[ORM\Column(length: 100)]
    private ?string $packageName = null;

    #[ORM\Column]
    private ?float $packagePrice = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $categoryName = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $orderedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $fulfilledAt = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'fulfilled';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBusiness(): ?Business
    {
        return $this->business;
    }

    public function setBusiness(?Business $business): static
    {
        $this->business = $business;
        return $this;
    }

    public function getPackageName(): ?string
    {
        return $this->packageName;
    }

    public function setPackageName(string $packageName): static
    {
        $this->packageName = $packageName;
        return $this;
    }

    public function getPackagePrice(): ?float
    {
        return $this->packagePrice;
    }

    public function setPackagePrice(float $packagePrice): static
    {
        $this->packagePrice = $packagePrice;
        return $this;
    }

    public function getCategoryName(): ?string
    {
        return $this->categoryName;
    }

    public function setCategoryName(?string $categoryName): static
    {
        $this->categoryName = $categoryName;
        return $this;
    }

    public function getOrderedAt(): ?\DateTimeImmutable
    {
        return $this->orderedAt;
    }

    public function setOrderedAt(\DateTimeImmutable $orderedAt): static
    {
        $this->orderedAt = $orderedAt;
        return $this;
    }

    public function getFulfilledAt(): ?\DateTimeImmutable
    {
        return $this->fulfilledAt;
    }

    public function setFulfilledAt(\DateTimeImmutable $fulfilledAt): static
    {
        $this->fulfilledAt = $fulfilledAt;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }
}
