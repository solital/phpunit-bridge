<?php

namespace Solital\PHPUnit\Subscriber\Application;

use PHPUnit\Event\Application\Started;
use PHPUnit\Event\Application\StartedSubscriber;
use Solital\PHPUnit\Output;

class ApplicationStartedSubscriber implements StartedSubscriber
{
    public function notify(Started $event): void
    {
        ob_start();
        $msg = sprintf('%s', $event->runtime()->asString());
        Output::banner($msg, 49)->print()->break();
        $result = ob_get_contents();
        ob_clean();

        echo $result;
    }
}
