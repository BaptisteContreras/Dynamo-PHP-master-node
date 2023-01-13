<?php

namespace App\Manager\Domain\Service;

use App\Manager\Domain\Constante\Enum\WorkerState;
use App\Manager\Domain\Contract\Out\Finder\WorkerNodeFinder;
use App\Manager\Domain\Contract\Out\Repository\WorkerNodeRepositoryInterface;
use App\Manager\Domain\Exception\LockingFailsException;
use App\Manager\Domain\Exception\NotEnoughFreeLabelSlotException;
use App\Manager\Domain\Exception\RingFullException;
use App\Manager\Domain\Exception\WorkerAlreadyRegisteredException;
use App\Manager\Domain\Exception\WrongWorkerStateException;
use App\Manager\Domain\Model\Dto\WorkerNode;
use App\Manager\Domain\Service\Worker\WorkerNodeLockerInterface;
use Psr\Log\LoggerInterface;

class Ring
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly LabelSet $labelSet,
        private readonly WorkerNodeLockerInterface $workerNodeLocker,
        private readonly WorkerNodeFinder $workerNodeFinder,
        private readonly WorkerNodeRepositoryInterface $workerNodeRepository
    ) {
    }

    /**
     * Try to make the given worker node join the pool.
     * During this method, a lock is set and no other worker node with the same network address and network port can try
     * to join the pool. As it is a pessimist lock, it throws an exception without waiting.
     *
     * ########################################## Conditions to join the ring ##########################################
     *  - No other worker node with the same network address and network port exists in the ring
     *  - The ring is not full (i.e. the maximum size of the ring depends on the number of labels) -> for the moment
     * the maximum size of the ring is fixed to 360
     *  - The worker node must be in the "joining" state
     *
     * @throws RingFullException
     * @throws LockingFailsException
     * @throws WorkerAlreadyRegisteredException|WrongWorkerStateException
     */
    public function join(WorkerNode $workerNode): void
    {
        if (!$workerNode->isJoining()) {
            throw new WrongWorkerStateException(WorkerState::JOINING, $workerNode->getWorkerState());
        }

        if ($this->isFull()) {
            throw new RingFullException();
        }

        $this->workerNodeLocker->lockWorkerNodeForJoining($workerNode);

        $alreadyExistingWorker = $this->workerNodeFinder->findOneByIpAndPort(
            $workerNode->getNetworkAddress(),
            $workerNode->getNetworkPort()
        );

        if ($alreadyExistingWorker) {
            throw new WorkerAlreadyRegisteredException($workerNode->getNetworkAddress(), $workerNode->getNetworkPort());
        }
        $this->workerNodeRepository->add($workerNode, true);

        try {
            $this->labelSet->acquireLabels($workerNode, $workerNode->getWeight(), true);
        } catch (NotEnoughFreeLabelSlotException $e) {
            $this->workerNodeLocker->unLockWorkerNodeForJoining($workerNode);

            throw new RingFullException();
        }

        $workerNode->markAsUp();

        $this->workerNodeRepository->update($workerNode, true);

        $this->workerNodeLocker->unLockWorkerNodeForJoining($workerNode);
    }

    /**
     * /!\ Locked futures resources are not taking into account for the calculation /!\.
     */
    public function isFull(): bool
    {
        return !$this->labelSet->hasFreeSlots();
    }

    public function getWorkers(): array
    {
        return $this->workerNodeFinder->findAll();
    }
}
