<?php

namespace App\Shared\Infrastructure\Persistence\Doctrine;

use App\Shared\Domain\Const\NodeState;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\UuidV7;

#[Entity]
class Node
{
    /**
     * @param Collection<int, VirtualNode> $virtualNodes
     */
    public function __construct(
        #[Column(type: Types::STRING, length: 255)] private string $host,
        #[Column(type: Types::INTEGER)] private int $networkPort,
        #[Column(type: Types::SMALLINT, enumType: NodeState::class)] private NodeState $state,
        #[Column(type: Types::DATETIME_IMMUTABLE)] private \DateTimeImmutable $joinedAt,
        #[Column(type: Types::SMALLINT)] private int $weight,
        #[Column(type: Types::BOOLEAN)] private bool $selfEntry,
        #[Column(type: Types::BOOLEAN)] private bool $seed,
        #[Column(type: Types::STRING, length: 10, unique: true)] private string $label,
        #[OneToMany(targetEntity: VirtualNode::class, mappedBy: 'node')] private Collection $virtualNodes = new ArrayCollection(),
        #[Id] #[Column(type: UuidType::NAME, unique: true)] private UuidV7 $id = new UuidV7()
    ) {
    }

    public function getId(): ?UuidV7
    {
        return $this->id;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return positive-int
     */
    public function getNetworkPort(): int
    {
        return $this->networkPort;
    }

    public function getState(): NodeState
    {
        return $this->state;
    }

    public function setState(NodeState $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getJoinedAt(): \DateTimeImmutable
    {
        return $this->joinedAt;
    }

    /**
     * @return positive-int
     */
    public function getWeight(): int
    {
        return $this->weight;
    }

    public function isSelfEntry(): bool
    {
        return $this->selfEntry;
    }

    public function isSeed(): bool
    {
        return $this->seed;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return Collection<int, VirtualNode>
     */
    public function getVirtualNodes(): Collection
    {
        return $this->virtualNodes;
    }

    /**
     * @param Collection<int, VirtualNode> $virtualNodes
     */
    public function setVirtualNodes(Collection $virtualNodes): void
    {
        $this->virtualNodes = $virtualNodes;
    }
}
