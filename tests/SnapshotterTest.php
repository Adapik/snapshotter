<?php
/**
 * This file is part of the prooph/snapshotter.
 * (c) 2015-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\Snapshotter;

use PHPUnit_Framework_TestCase as TestCase;
use Prooph\EventStore\Aggregate\AggregateRepository;
use Prooph\EventStore\Snapshot\SnapshotStore;
use Prooph\Snapshotter\Snapshotter;
use Prooph\Snapshotter\TakeSnapshot;
use ProophTest\EventStore\Mock\User;

/**
 * Class SnapshotterTest
 * @package ProophTest\Snapshotter
 */
final class SnapshotterTest extends TestCase
{
    /**
     * @test
     */
    public function it_takes_snapshots(): void
    {
        $user = User::create('Alex', 'contact@prooph.de');

        $repository = $this->prophesize(AggregateRepository::class);
        $repository->getAggregateRoot('some id')->willReturn($user);
        $repository->extractAggregateVersion($user)->willReturn(1);

        $snapshotStore = $this->prophesize(SnapshotStore::class);
        $snapshotStore->save($this->any());

        $snapshotter = new Snapshotter($snapshotStore->reveal(), [
            'ProophTest\EventStore\Mock\User' => $repository->reveal()
        ]);

        $snapshotter(TakeSnapshot::withData('ProophTest\EventStore\Mock\User', 'some id'));
    }

    /**
     * @test
     */
    public function it_throws_exception_when_no_repository_given(): void
    {
        $this->expectException(\Assert\InvalidArgumentException::class);

        $snapshotStore = $this->prophesize(SnapshotStore::class);

        new Snapshotter($snapshotStore->reveal(), []);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_aggregate_root_cannot_get_handled(): void
    {
        $this->expectException(\Prooph\Snapshotter\Exception\RuntimeException::class);
        $this->expectExceptionMessage('No repository for aggregate type ProophTest\EventStore\Mock\Todo configured');

        $repository = $this->prophesize(AggregateRepository::class);

        $snapshotStore = $this->prophesize(SnapshotStore::class);

        $snapshotter = new Snapshotter($snapshotStore->reveal(), [
            'ProophTest\EventStore\Mock\User' => $repository->reveal()
        ]);

        $snapshotter(TakeSnapshot::withData('ProophTest\EventStore\Mock\Todo', 'some id'));
    }

    /**
     * @test
     */
    public function it_throws_exception_when_aggregate_root_not_found(): void
    {
        $this->expectException(\Prooph\Snapshotter\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Could not find aggregate root');

        $repository = $this->prophesize(AggregateRepository::class);

        $snapshotStore = $this->prophesize(SnapshotStore::class);

        $snapshotter = new Snapshotter($snapshotStore->reveal(), [
            'ProophTest\EventStore\Mock\User' => $repository->reveal()
        ]);

        $snapshotter(TakeSnapshot::withData('ProophTest\EventStore\Mock\User', 'invalid id'));
    }
}
