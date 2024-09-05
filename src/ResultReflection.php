<?php

declare(strict_types=1);

namespace Solital\PHPUnit;

use PHPUnit\TestRunner\TestResult\TestResult;

/**
 * @internal
 */
final class ResultReflection
{
    private static int $numberOfTests;
    
    /**
     * The number of processed tests.
     */
    public static function numberOfTests(TestResult $testResult): int
    {
        return (fn () => self::$numberOfTests)->call($testResult);
    }
}