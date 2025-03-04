<?php

namespace Solital\PHPUnit;

class Output
{
    /**
     * @var string
     */
    protected static string $message;

    /**
     * @var string
     */
    protected static string $color_reset = '';

    /**
     * @var string
     */
    protected static string $color_success = '';

    /**
     * @var string
     */
    protected static string $color_info = '';

    /**
     * @var string
     */
    protected static string $color_warning = '';

    /**
     * @var string
     */
    protected static string $color_error = '';

    /**
     * @var string
     */
    protected static string $color_line = '';

    /**
     * Get the value of message
     *
     * @return string
     */
    public function getMessage(): string
    {
        return self::$message;
    }

    /**
     * Print a single message on CLI
     * 
     * @param string $message
     * 
     * @return Output
     */
    public function printMessage(mixed $message): Output
    {
        self::$message = $message;
        echo self::$message;

        return $this;
    }

    /**
     * Create a success message
     *
     * @param mixed $message
     * @param bool $space
     * 
     * @return static 
     */
    public static function success(mixed $message, bool $space = false): static
    {
        self::generateColors();
        self::$message = self::prepareColor($message, self::$color_success, $space);
        return new static;
    }

    /**
     * Create a info message
     *
     * @param mixed $message
     * @param bool $space
     * 
     * @return static 
     */
    public static function info(mixed $message, bool $space = false): static
    {
        self::generateColors();
        self::$message = self::prepareColor($message, self::$color_info, $space);
        return new static;
    }

    /**
     * Create a warning message
     *
     * @param mixed $message
     * @param bool $space
     * 
     * @return static 
     */
    public static function warning(mixed $message, bool $space = false): static
    {
        self::generateColors();
        self::$message = self::prepareColor($message, self::$color_warning, $space);
        return new static;
    }

    /**
     * Create a error message
     *
     * @param mixed $message
     * @param bool $space
     * 
     * @return static 
     */
    public static function error(mixed $message, bool $space = false): static
    {
        self::generateColors();
        self::$message = self::prepareColor($message, self::$color_error, $space);
        return new static;
    }

    /**
     * Create a normal message
     *
     * @param mixed $message
     * @param bool $space
     * 
     * @return static 
     */
    public static function line(mixed $message, bool $space = false): static
    {
        self::generateColors();
        self::$message = self::prepareColor($message, self::$color_line, $space);
        return new static;
    }

    /**
     * Create a message with custom color
     *
     * @param mixed $message
     * @param int $color
     * @param bool $space
     * 
     * @return static
     */
    public static function message(mixed $message, int $color, bool $space = false): static
    {
        self::generateColors();
        $color_value = $color . "m";

        self::$message = self::prepareColor($message, $color_value, $space);
        return new static;
    }

    /**
     * Print a large banner in CLI
     *
     * @param mixed $message
     * @param int $color
     * @param int $lenght
     * 
     * @return static
     */
    public static function banner(mixed $message, int $color, int $lenght = 60): static
    {
        self::generateColors();
        $color_value = $color . "m";

        self::$message = PHP_EOL;
        self::$message .= $color_value . str_pad('', $lenght, pad_type: STR_PAD_BOTH) . self::$color_reset . PHP_EOL;
        self::$message .= $color_value . str_pad($message, $lenght, pad_type: STR_PAD_BOTH) . self::$color_reset . PHP_EOL;
        self::$message .= $color_value . str_pad('', $lenght, pad_type: STR_PAD_BOTH) . self::$color_reset . PHP_EOL;
        self::$message .= PHP_EOL;
        return new static;
    }

    /**
     * Write message on CLI
     * 
     * @return Output
     */
    public function print(): Output
    {
        echo self::$message;
        return $this;
    }

    /**
     * Break a line
     * 
     * @param bool|int $repeat Break another line
     * 
     * @return Output
     */
    public function break(bool|int $repeat = false): Output
    {
        echo PHP_EOL;

        if (is_int($repeat)) {
            for ($i = 0; $i <= $repeat; $i++) {
                echo PHP_EOL;
            }
        }

        if ($repeat == true) echo PHP_EOL . PHP_EOL;
        return $this;
    }

    /**
     * Call `exit()` function
     *
     * @param string|null $message
     * 
     * @return never 
     */
    public function exit(?string $message = null): never
    {
        exit($message);
    }

    /**
     * Add message to a color
     *
     * @param mixed $message
     * @param string $color
     * @param bool $space
     * 
     * @return string
     */
    private static function prepareColor(mixed $message, string $color, bool $space): string
    {
        ($space == true) ? $space_value = "  " : $space_value = "";
        return $space_value . $color . $message . self::$color_reset;
    }

    /**
     * Generate colors for CLI
     * 
     * @return bool
     * @throws \Exception
     */
    protected static function generateColors(): bool
    {
        if (self::isCli() == false) throw new \Exception("Console Output is used only in CLI mode");

        if (self::colorIsSupported() || self::are256ColorsSupported()) {
            self::$color_reset = "\e[0m";
            self::$color_success = "\033[92m";
            self::$color_info = "\033[96m";
            self::$color_warning = "\033[93m";
            self::$color_error = "\033[41m";
            self::$color_line = "\033[0;38m";
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private static function colorIsSupported(): bool
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            if (function_exists('sapi_windows_vt100_support') && sapi_windows_vt100_support(STDOUT)) {
                return true;
            } elseif (getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON') {
                return true;
            }

            return false;
        } else {
            return function_exists('posix_isatty') && posix_isatty(STDOUT);
        }
    }

    /**
     * @return bool
     */
    private static function are256ColorsSupported(): bool
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return function_exists('sapi_windows_vt100_support') && sapi_windows_vt100_support(STDOUT);
        } else {
            return str_starts_with(getenv('TERM'), '256color');
        }
    }

    /**
     * @return bool
     */
    private static function isCli(): bool
    {
        if (defined('STDIN')) return true;

        if (
            empty($_SERVER['REMOTE_ADDR']) &&
            !isset($_SERVER['HTTP_USER_AGENT'])
        ) {
            return true;
        }

        return false;
    }
}
