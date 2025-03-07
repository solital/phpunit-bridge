<?php

namespace Tests\Unit\Subscriber\Application;

use Tests\SpyOutput;
use PHPUnit\Framework\TestCase;
use PHPUnit\Event\Telemetry\Info;
use PHPUnit\Event\Runtime\Runtime;
use PHPUnit\Event\Telemetry\HRTime;
use PHPUnit\Event\Telemetry\Duration;
use PHPUnit\Event\Telemetry\Snapshot;
use PHPUnit\Event\Application\Started;
use Spatie\Snapshots\MatchesSnapshots;
use PHPUnit\Event\Telemetry\MemoryUsage;
use PHPUnit\Event\Telemetry\GarbageCollectorStatus;
use Solital\PHPUnit\Subscriber\Application\ApplicationStartedSubscriber;

//use function Termwind\renderUsing;

class ApplicationStartedSubscriberTest extends TestCase
{
    use MatchesSnapshots;

    public function testNotify(): void
    {
        $spyOutput = new SpyOutput();
        renderUsing($spyOutput);

        $subscriber = new ApplicationStartedSubscriber();
        $subscriber->notify(new Started(
            new Info(
                new Snapshot(
                    HRTime::fromSecondsAndNanoseconds(1, 0),
                    MemoryUsage::fromBytes(100),
                    MemoryUsage::fromBytes(100),
                    new GarbageCollectorStatus(0, 0, 0, 0, null, null, null, null, null, null, null, null)
                ),
                Duration::fromSecondsAndNanoseconds(1, 0),
                MemoryUsage::fromBytes(100),
                Duration::fromSecondsAndNanoseconds(1, 0),
                MemoryUsage::fromBytes(100),
            ),
            new Runtime()
        ));

        $this->assertMatchesRegularExpression(
            '/Runtime: PHPUnit [\s\S]+ using PHP [\s\S]+ on [\s\S]+/', 
            $spyOutput
        );
    }
}