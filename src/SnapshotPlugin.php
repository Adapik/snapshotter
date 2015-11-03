<?php

namespace Prooph\Snapshotter;

use Assert\Assertion;
use Prooph\Common\Event\ActionEvent;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Plugin\Plugin;
use Prooph\ServiceBus\CommandBus;

/**
 * Class SnapshotPlugin
 * @package Prooph\Snapshotter
 */
final class SnapshotPlugin implements Plugin
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var int
     */
    private $versionStep;

    /**
     * @param CommandBus $commandBus
     * @param int $versionStep
     */
    public function __construct(CommandBus $commandBus, $versionStep)
    {
        Assertion::min($versionStep, 1);
        $this->commandBus = $commandBus;
        $this->versionStep = $versionStep;
    }

    /**
     * @param EventStore $eventStore
     * @return void
     */
    public function setUp(EventStore $eventStore)
    {
        $eventStore->getActionEventEmitter()->attachListener('commit.post', [$this, 'onEventStoreCommitPost']);
    }

    /**
     * Publish recorded events on the event bus
     *
     * @param ActionEvent $actionEvent
     */
    public function onEventStoreCommitPost(ActionEvent $actionEvent)
    {
        $recordedEvents = $actionEvent->getParam('recordedEvents', []);

        $snapshots = [];

        foreach ($recordedEvents as $recordedEvent) {
            if ($recordedEvent->version() % $this->versionStep !== 0) {
                continue;
            }
            $metadata = $recordedEvent->metadata();
            if (!isset($metadata['aggregate_type']) || !isset($metadata['aggregate_id'])) {
                continue;
            }
            $snapshots['aggregate_type']['aggregate_id'] = $metadata['version'];
        }

        foreach ($snapshots as $aggregateType) {
            foreach ($aggregateType as $aggregateId) {
                $command = TakeSnapshot::withData($aggregateType, $aggregateId);
                $this->commandBus->dispatch($command);
            }
        }
    }
}
