<?php
/**
 * This file is part of the prooph/snapshotter.
 * (c) 2015-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\Snapshotter;

use Prooph\EventSourcing\Aggregate\AggregateRepository;
use Prooph\EventSourcing\Aggregate\AggregateType;
use Prooph\EventSourcing\EventStoreIntegration\AggregateTranslator;
use Prooph\EventStore\Projection\InMemoryEventStoreReadModelProjection;
use Prooph\EventStore\StreamName;
use Prooph\SnapshotStore\InMemorySnapshotStore;
use Prooph\Snapshotter\CategorySnapshotProjection;
use Prooph\Snapshotter\SnapshotReadModel;
use ProophTest\EventSourcing\Mock\User;
use ProophTest\EventStore\EventStoreTestCase;

class CategorySnapshotProjectionTest extends EventStoreTestCase
{
    /**
     * @test
     */
    public function it_takes_snapshots(): void
    {
        $user1 = User::nameNew('Aleks');
        $user1->changeName('Alex');

        $user2 = User::nameNew('Sasha');
        $user2->changeName('Sascha');

        $snapshotStore = new InMemorySnapshotStore();
        $aggregateType = AggregateType::fromAggregateRoot($user1);
        $aggregateRepository = new AggregateRepository(
            $this->eventStore,
            $aggregateType,
            new AggregateTranslator(),
            $snapshotStore,
            new StreamName('user'),
            true
        );

        $aggregateRepository->saveAggregateRoot($user1);
        $aggregateRepository->saveAggregateRoot($user2);

        $categorySnapshotProjection = new CategorySnapshotProjection(
            new InMemoryEventStoreReadModelProjection(
                $this->eventStore,
                'user-snapshots',
                new SnapshotReadModel(
                    $aggregateRepository,
                    new AggregateTranslator(),
                    $snapshotStore
                ),
                5,
                1000,
                5
            ),
            'user'
        );

        $categorySnapshotProjection(false);

        $this->assertEquals($user1, $snapshotStore->get((string) $aggregateType, $user1->id())->aggregateRoot());
        $this->assertEquals($user2, $snapshotStore->get((string) $aggregateType, $user2->id())->aggregateRoot());
    }
}
