<?php

namespace Solital\PHPUnit\Subscriber\Application;

use PHPUnit\Event\Application\Finished;
use PHPUnit\Event\Application\FinishedSubscriber;
use Solital\PHPUnit\Quotes;

final class ApplicationFinishedSubscriber implements FinishedSubscriber
{
    public function notify(Finished $event): void
    {
        //echo sprintf("\n\e[32m%s\e[0m", Quotes::getRandom());
        //echo PHP_EOL;
    }
}