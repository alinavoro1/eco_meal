<?php

namespace App\Entity;

use App\Repository\ConsumerRepository;
use App\Entity\Business;
use App\Entity\Category;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConsumerRepository::class)]
class Consumer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $first_name = null;

    #[ORM\Column(length: 50)]
    private ?string $last_name = null;

    #[ORM\Column(length: 20)]
    private ?string $phone_number = null;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'consumer', orphanRemoval: true)]
    private Collection $orders;

    #[ORM\OneToOne(mappedBy: 'consumer', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    #[ORM\ManyToMany(targetEntity: Business::class)]
    #[ORM\JoinTable(name: 'consumer_favorite_business')]
    private Collection $favoriteBusinesses;

    #[ORM\ManyToMany(targetEntity: Category::class)]
    #[ORM\JoinTable(name: 'consumer_preferred_category')]
    private Collection $preferredCategories;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
        $this->favoriteBusinesses = new ArrayCollection();
        $this->preferredCategories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): static
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): static
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function __toString(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    public function setPhoneNumber(string $phone_number): static
    {
        $this->phone_number = $phone_number;

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setConsumer($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getConsumer() === $this) {
                $order->setConsumer(null);
            }
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        if ($user === null && $this->user !== null) {
            $this->user->setConsumer(null);
        }

        if ($user !== null && $user->getConsumer() !== $this) {
            $user->setConsumer($this);
        }

        $this->user = $user;

        return $this;
    }

    public function getFavoriteBusinesses(): Collection
    {
        return $this->favoriteBusinesses;
    }

    public function hasFavoriteBusiness(Business $business): bool
    {
        return $this->favoriteBusinesses->contains($business);
    }

    public function addFavoriteBusiness(Business $business): static
    {
        if (!$this->favoriteBusinesses->contains($business)) {
            $this->favoriteBusinesses->add($business);
        }
        return $this;
    }

    public function removeFavoriteBusiness(Business $business): static
    {
        $this->favoriteBusinesses->removeElement($business);
        return $this;
    }

    public function getPreferredCategories(): Collection
    {
        return $this->preferredCategories;
    }

    public function setPreferredCategories(Collection $categories): static
    {
        $this->preferredCategories = $categories;
        return $this;
    }
}
