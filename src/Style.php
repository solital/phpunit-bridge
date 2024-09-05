<?php

declare(strict_types=1);

namespace Solital\PHPUnit;

use ReflectionClass;
use PHPUnit\Event\Code\Throwable;
use PHPUnit\Event\Telemetry\Info;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\IncompleteTestError;
use PHPUnit\Framework\SkippedWithMessageException;
use PHPUnit\TestRunner\TestResult\TestResult as PHPUnitTestResult;
use PHPUnit\TextUI\Configuration\Registry;
use Solital\Core\Console\Output\ConsoleOutput;
use Solital\PHPUnit\Printer\DefaultPrinter;

/**
 * @internal
 */
final class Style
{
    private string $color_reset = "\e[0m";
    private string $color_warning = "\033[93m";
    private string $color_error = "\033[41m";
    private string $color_error_line = "\033[1;31m";
    private string $color_line = "\033[97m";
    private string $color_gray = "\033[0;90m";

    private int $compactProcessed = 0;
    private int $compactSymbolsPerLine = 0;

    /**
     * @var string[]
     */
    private const TYPES = [
        TestResult::DEPRECATED,
        TestResult::FAIL,
        TestResult::WARN,
        TestResult::RISKY,
        TestResult::INCOMPLETE,
        TestResult::NOTICE,
        TestResult::TODO,
        TestResult::SKIPPED,
        TestResult::PASS
    ];

    /**
     * Prints the content similar too:.
     *
     * ```
     *    WARN  Your XML configuration validates against a deprecated schema...
     * ```
     */
    public function writeWarning(string $message): void
    {
        ConsoleOutput::warning("WARM ")->print();
        ConsoleOutput::line($message)->print()->break();
    }

    /**
     * Prints the content similar too:.
     *
     * ```
     *    WARN  Your XML configuration validates against a deprecated schema...
     * ```
     */
    public function writeThrowable(\Throwable $throwable): void
    {
        ConsoleOutput::error("ERROR " . $throwable->getMessage())->print()->break();
    }

    /**
     * Prints the content similar too:.
     *
     * ```
     *    PASS  Unit\ExampleTest
     *    ✓ basic test
     * ```
     */
    public function writeCurrentTestCaseSummary(State $state): void
    {
        if ($state->testCaseTestsCount() === 0 || is_null($state->testCaseName)) return;
        if (!$state->headerPrinted && ! DefaultPrinter::compact()) {
            ConsoleOutput::line($this->titleLineFrom(
                $state->getTestCaseFontColor(),
                $state->getTestCaseTitleColor(),
                $state->getTestCaseTitle(),
                $state->testCaseName,
                $state->todosCount(),
            ))->print()->break();

            $state->headerPrinted = true;
        }

        $state->eachTestCaseTests(function (TestResult $testResult): void {
            if ($testResult->description !== '') {
                if (DefaultPrinter::compact()) {
                    $this->writeCompactDescriptionLine($testResult);
                } else {
                    $this->writeDescriptionLine($testResult);
                }
            }
        });
    }

    /**
     * Prints the content similar too:.
     *
     * ```
     *    PASS  Unit\ExampleTest
     *    ✓ basic test
     * ```
     */
    public function writeErrorsSummary(State $state): void
    {
        $configuration = Registry::get();
        $failTypes = [
            TestResult::FAIL,
        ];

        if ($configuration->displayDetailsOnTestsThatTriggerNotices())
            $failTypes[] = TestResult::NOTICE;

        if ($configuration->displayDetailsOnTestsThatTriggerDeprecations())
            $failTypes[] = TestResult::DEPRECATED;

        if ($configuration->failOnWarning() || $configuration->displayDetailsOnTestsThatTriggerWarnings())
            $failTypes[] = TestResult::WARN;

        if ($configuration->failOnRisky()) $failTypes[] = TestResult::RISKY;

        if ($configuration->failOnIncomplete() || $configuration->displayDetailsOnIncompleteTests())
            $failTypes[] = TestResult::INCOMPLETE;

        if ($configuration->failOnSkipped() || $configuration->displayDetailsOnSkippedTests())
            $failTypes[] = TestResult::SKIPPED;

        $failTypes = array_unique($failTypes);
        $errors = array_values(array_filter($state->suiteTests, fn(TestResult $testResult) => in_array(
            $testResult->type,
            $failTypes,
            true
        )));

        array_map(function (TestResult $testResult): void {
            if (!$testResult->throwable instanceof Throwable) {
                throw new \Exception;
            }

            ConsoleOutput::line(str_pad("", 50, "-"))->print()->break();
            $testCaseName = $testResult->testCaseName;
            $description = $testResult->description;
            $throwableClassName = $testResult->throwable->className();

            $throwableClassName = ! in_array($throwableClassName, [
                ExpectationFailedException::class,
                IncompleteTestError::class,
                SkippedWithMessageException::class,
                \Exception::class,
            ], true) ? sprintf(
                $this->color_error_line . "%s" . $this->color_reset,
                (new ReflectionClass($throwableClassName))->getShortName()
            ) : '';

            $truncateClasses = true ? '' : 'flex-1 truncate';

            echo sprintf(
                "%s %s %s %s - %s `%s` %s" . $this->color_reset,
                $truncateClasses,
                $testResult->color === $this->color_warning ? $this->color_warning : $testResult->color,
                $testResult->color === $this->color_warning ? $this->color_warning : $this->color_line,
                strtoupper($testResult->type),
                $testCaseName,
                $description,
                $throwableClassName
            );
            echo PHP_EOL;

            ConsoleOutput::line(str_pad("", 50, "-"))->print()->break();
            $this->writeError($testResult->throwable);
        }, $errors);
    }

    /**
     * Writes the final recap.
     */
    public function writeRecap(State $state, Info $telemetry, PHPUnitTestResult $result): void
    {
        $tests = [];
        foreach (self::TYPES as $type) {
            if (($countTests = $state->countTestsInTestSuiteBy($type)) !== 0) {
                $color = TestResult::makeColor($type);

                if ($type === TestResult::WARN && $countTests < 2) {
                    $type = 'warning';
                }

                if ($type === TestResult::NOTICE && $countTests > 1) {
                    $type = 'notices';
                }

                if ($type === TestResult::TODO && $countTests > 1) {
                    $type = 'todos';
                }

                $tests[] =  $color . " $countTests $type" . $this->color_reset;
            }
        }

        $pending = ResultReflection::numberOfTests($result) - $result->numberOfTestsRun();
        if ($pending > 0) {
            $tests[] = "\e[2m$pending pending\e[22m";
        }

        $timeElapsed = number_format($telemetry->durationSinceStart()->asFloat(), 2, '.', '');
        ConsoleOutput::line("")->print()->break();

        if (! empty($tests)) {
            ConsoleOutput::line(str_pad("", 70, "-"))->print()->break();
            echo sprintf(
                $this->color_gray . "Tests:    " . $this->color_line . "%s" . $this->color_gray . " (%s assertions)" . $this->color_reset,
                implode($this->color_gray . "," . $this->color_reset . " ", $tests),
                $result->numberOfAssertions(),
            );
        }

        echo sprintf($this->color_gray . '  Duration:' . $this->color_reset . ' %ss', $timeElapsed);
        ConsoleOutput::line("")->print()->break();
    }

    /**
     * @param  array<int, TestResult>  $slowTests
     */
    public function writeSlowTests(array $slowTests, Info $telemetry): void
    {
        ConsoleOutput::message("\nTop 10 slowest tests:", 39)->print()->break(true);
        $timeElapsed = $telemetry->durationSinceStart()->asFloat();

        foreach ($slowTests as $testResult) {
            $seconds = number_format($testResult->duration / 1000, 2, '.', '');
            $color = ($testResult->duration / 1000) > $timeElapsed * 0.25 ?
                $this->color_error : ($testResult->duration > $timeElapsed * 0.1 ?
                    $this->color_warning : $this->color_gray);

            echo sprintf("%s (%ss) %s %s" . $this->color_reset, $color, $seconds, $testResult->testCaseName, $testResult->description);
            echo PHP_EOL;
        }

        $timeElapsedInSlowTests = array_sum(array_map(fn(TestResult $testResult) => $testResult->duration / 1000, $slowTests));
        $timeElapsedAsString = number_format($timeElapsed, 2, '.', '');
        $percentageInSlowTestsAsString = number_format($timeElapsedInSlowTests * 100 / $timeElapsed, 2, '.', '');
        $timeElapsedInSlowTestsAsString = number_format($timeElapsedInSlowTests, 2, '.', '');

        echo PHP_EOL;
        echo sprintf(
            $this->color_gray . "(%s%% of %ss) %ss" . $this->color_reset,
            $percentageInSlowTestsAsString,
            $timeElapsedAsString,
            $timeElapsedInSlowTestsAsString
        );
        echo PHP_EOL;
    }

    /**
     * Displays the error using Collision's writer and terminates with exit code === 1.
     */
    public function writeError(Throwable $throwable): void
    {
        $throwable = new TestException($throwable, true);

        $info_error_exception = [
            'message' => $throwable->getMessage(),
            'code' => $throwable->getCode(),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'type_exception' => $throwable->getClassName(),
            'namespace_exception' => get_class($throwable)
        ];

        echo PHP_EOL;

        if (isset($info_error_exception['type_exception'])) {
            ConsoleOutput::error($info_error_exception['type_exception'])->print();
        } /* else {
                ConsoleOutput::error($this->getError())->print();
            } */

        ConsoleOutput::line(" : " . $info_error_exception['message'])->print()->break();
        ConsoleOutput::line("at ")->print();
        ConsoleOutput::warning($info_error_exception['file'])->print();
        ConsoleOutput::line(" : ")->print();
        ConsoleOutput::warning($info_error_exception['line'])->print()->break(true);
        $this->getLines($info_error_exception['file'], $info_error_exception['line']);

        if (!empty($this->trace)) {
            echo PHP_EOL;
            ConsoleOutput::warning("Exception Trace")->print()->break(true);
        }

        foreach ($throwable->getTrace() as $key => $trace) {
            echo $key . "   ";
            ConsoleOutput::info($trace['file'])->print();
            echo " : ";
            ConsoleOutput::info($trace['line'])->print()->break();
        }
    }

    /**
     * Returns the title contents.
     */
    private function titleLineFrom(string $fg, string $bg, string $title, string $testCaseName, int $todos): string
    {
        $msg = sprintf(
            "\n%s %s " . $this->color_reset . " %s %s",
            $bg,
            $title,
            $testCaseName,
            $todos > 0 ? sprintf(
                $this->color_gray . ' - %s todo%s' . $this->color_reset,
                $todos,
                $todos > 1 ? 's' : ''
            ) : ''
        );

        $msg .= PHP_EOL;
        return $msg;
    }

    /**
     * Writes a description line.
     */
    private function writeCompactDescriptionLine(TestResult $result): void
    {
        $symbolsOnCurrentLine = $this->compactProcessed % $this->compactSymbolsPerLine;

        /* if ($symbolsOnCurrentLine >= $this->terminal->width() - 4) {
            $symbolsOnCurrentLine = 0;
        } */

        if ($symbolsOnCurrentLine === 0) {
            echo " \n";
            echo "  ";
        }

        echo sprintf("%s" . $this->color_reset, $result->compactColor, $result->compactIcon);
        $this->compactProcessed++;
    }

    /**
     * Writes a description line.
     */
    private function writeDescriptionLine(TestResult $result): void
    {
        if (!empty($warning = $result->warning)) {
            if (!str_contains($warning, "\n")) {
                $warning = sprintf(' → %s', $warning);
            } else {
                $warningLines = explode("\n", $warning);
                $warning = '';

                foreach ($warningLines as $w) {
                    $warning .= sprintf(
                        "\n    " . $this->color_warning . "⇂ %s" . $this->color_reset,
                        trim($w)
                    );
                }
            }
        }

        $seconds = '';

        if (($result->duration / 1000) > 0.0) {
            $seconds = number_format($result->duration / 1000, 2, '.', '');
            $seconds = $seconds !== '0.00' ? sprintf(
                $this->color_gray . '%ss' . $this->color_reset,
                $seconds
            ) : '';
        }

        if (
            isset($_SERVER['REBUILD_SNAPSHOTS']) ||
            (isset($_SERVER['COLLISION_IGNORE_DURATION']) &&
                $_SERVER['COLLISION_IGNORE_DURATION'] === 'true')
        ) {
            $seconds = '';
        }

        $truncateClasses = true ? '' : 'flex-1 truncate';

        if ($warning !== '') {
            $warning = sprintf($this->color_warning . '%s' . $this->color_reset, $warning);

            if (! empty($result->warningSource)) {
                $warning .= ' // ' . $result->warningSource;
            }
        }

        $description = $result->description;
        $description = preg_replace('/`([^`]+)`/', $this->color_line . '$1' . $this->color_reset, $description);

        echo sprintf(
            "%s %s %s (%s) " . $this->color_reset . " %s %s %s",
            $truncateClasses,
            $result->color,
            $result->icon,
            $result->line,
            $description,
            $warning,
            $seconds
        );
        echo PHP_EOL;
    }

    private function getLines(string $context, int $line): self
    {
        for ($i = 0; $i < 4; $i++) {
            $lines_up[] = $line + $i;
        }

        for ($i = 0; $i < 4; $i++) {
            $lines_down[] = $line - $i;
        }

        $lines = array_merge($lines_up, $lines_down);
        $lines = array_unique($lines);
        sort($lines);
        $is_resource = false;

        if (is_resource($context)) {
            //Você pode definir um resource ao invés de um "path"
            $fp = $context;
            $is_resource = true;
        } else if (is_file($context)) {
            $fp = fopen($context, 'r');
        }

        $i = 0;
        $result = [];

        if ($fp) {
            while (false === feof($fp)) {
                ++$i;
                $data = fgets($fp);
                if (in_array($i, $lines)) {
                    $result[$i] = $data;
                }
            }
        }

        //Pega última linha
        if ($i !== 1 && in_array('last', $lines)) {
            $result[] = $data;
        }

        if ($is_resource === true) {
            //Não fecha se for resource, pois pode estar sendo usada em outro lugar
            $fp = null;
        } else {
            fclose($fp);
        }

        $fp = null;

        foreach ($result as $key => $value) {
            if ($key == $line) {
                ConsoleOutput::message("↪︎   " . $key . "| " . $value, 9)->print()->break();
            } else {
                ConsoleOutput::message("   " . $key . "| ", 60)->print();
                ConsoleOutput::message($value, 39)->print()->break();
            }
        }

        echo PHP_EOL;
        return $this;
    }

    private function getClassName(object $classname): string
    {
        $class = get_class($classname);
        $class = explode("\\", $class);

        return end($class);
    }
}
