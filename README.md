# PHPUnit bridge for Solital Framework

This package was created using the [robiningelbrecht/phpunit-pretty-print)](https://packagist.org/packages/robiningelbrecht/phpunit-pretty-print) component.

## Installation

```bash
composer require solital/phpunit-bridge --dev
```

## Configuration

Navigate to your `phpunit.xml` file and add following config to set default options 
(you can also set these options at run time):

```xml
<extensions>
    <bootstrap class="Solital\PHPUnit\PhpUnitExtension">
    </bootstrap>
</extensions>
```

Also make sure the `color` attribute is set to `true`:

```xml
<phpunit 
        colors="true">
</phpunit>
```

## Options

All these options can be set at runtime as well, see <a href="#usage">usage</a>.

### Output profiling report

```xml
<extensions>
    <bootstrap class="Solital\PHPUnit\PhpUnitExtension">
        <parameter name="displayProfiling" value="true"/>
    </bootstrap>
</extensions>
```

### Enable compact mode

```xml
<extensions>
    <bootstrap class="Solital\PHPUnit\PhpUnitExtension">
        <parameter name="useCompactMode" value="true"/>
    </bootstrap>
</extensions>
```

### Feel good about yourself after running your testsuite by displaying a Chuck Noris quote

```xml
<extensions>
    <bootstrap class="Solital\PHPUnit\PhpUnitExtension">
        <parameter name="displayQuote" value="true"/>
    </bootstrap>
</extensions>
```

### Disable pretty print. 

This can be useful when you only want to prettify the output when forced via CLI (see <a href="#usage">usage</a>).

```xml
<extensions>
    <bootstrap class="Solital\PHPUnit\PhpUnitExtension">
        <parameter name="enableByDefault" value="false"/>
    </bootstrap>
</extensions>
```

## Usage

```bash
> vendor/bin/phpunit
```

### Output profiling report

```bash
> vendor/bin/phpunit -d --profiling
```

### Enable compact mode

```bash
> vendor/bin/phpunit -d --compact
```

### Display Chuck Norris quote

```bash
> vendor/bin/phpunit -d --display-quote
```

### Enable/disable pretty print

```bash
> vendor/bin/phpunit -d --enable-pretty-print
> vendor/bin/phpunit -d --disable-pretty-print
```

### Combine multiple options

```bash
> vendor/bin/phpunit --configuration=tests/phpunit.test.xml -d --compact -d --display-quote
```

## PHPUnit 9.x

This package does not support PHPUnit 9.x

# License

MIT