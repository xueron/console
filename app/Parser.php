<?php
namespace App;

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Parser
{
    /**
     * Parse the given console command definition into an array.
     *
     * @param string $expression
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    public static function parse($expression)
    {
        if (trim($expression) === '') {
            throw new InvalidArgumentException('Console command definition is empty.');
        }

        preg_match('/[^\s]+/', $expression, $matches);

        if (isset($matches[0])) {
            $name = $matches[0];
        } else {
            throw new InvalidArgumentException('Unable to determine command name from signature.');
        }

        preg_match_all('/\{\s*(.*?)\s*\}/', $expression, $matches);

        $tokens = isset($matches[1]) ? $matches[1] : [];

        if (count($tokens)) {
            return array_merge([$name], static::parameters($tokens));
        }

        return [$name, [], []];
    }

    /**
     * Extract all of the parameters from the tokens.
     *
     * @param array $tokens
     *
     * @return array
     */
    protected static function parameters(array $tokens)
    {
        $arguments = [];

        $options = [];

        foreach ($tokens as $token) {
            if (!static::startsWith($token, '--')) {
                $arguments[] = static::parseArgument($token);
            } else {
                $options[] = static::parseOption(ltrim($token, '-'));
            }
        }

        return [$arguments, $options];
    }

    /**
     * Parse an argument expression.
     *
     * @param string $token
     *
     * @return \Symfony\Component\Console\Input\InputArgument
     */
    protected static function parseArgument($token)
    {
        $description = null;

        if (mb_strpos($token, ' : ') !== false) {
            list($token, $description) = explode(' : ', $token, 2);

            $token = trim($token);

            $description = trim($description);
        }

        switch (true) {
            case static::endsWith($token, '?*'):
                return new InputArgument(trim($token, '?*'), InputArgument::IS_ARRAY, $description);
            case static::endsWith($token, '*'):
                return new InputArgument(trim($token, '*'), InputArgument::IS_ARRAY | InputArgument::REQUIRED, $description);
            case static::endsWith($token, '?'):
                return new InputArgument(trim($token, '?'), InputArgument::OPTIONAL, $description);
            case preg_match('/(.+)\=(.+)/', $token, $matches):
                return new InputArgument($matches[1], InputArgument::OPTIONAL, $description, $matches[2]);
            default:
                return new InputArgument($token, InputArgument::REQUIRED, $description);
        }
    }

    /**
     * Parse an option expression.
     *
     * @param string $token
     *
     * @return \Symfony\Component\Console\Input\InputOption
     */
    protected static function parseOption($token)
    {
        $description = null;

        if (mb_strpos($token, ' : ') !== false) {
            list($token, $description) = explode(' : ', $token);
            $token = trim($token);
            $description = trim($description);
        }

        $shortcut = null;

        $matches = preg_split('/\s*\|\s*/', $token, 2);

        if (isset($matches[1])) {
            $shortcut = $matches[0];
            $token = $matches[1];
        }

        switch (true) {
            case static::endsWith($token, '='):
                return new InputOption(trim($token, '='), $shortcut, InputOption::VALUE_OPTIONAL, $description);
            case static::endsWith($token, '=*'):
                return new InputOption(trim($token, '=*'), $shortcut, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, $description);
            case preg_match('/(.+)\=(.+)/', $token, $matches):
                return new InputOption($matches[1], $shortcut, InputOption::VALUE_OPTIONAL, $description, $matches[2]);
            default:
                return new InputOption($token, $shortcut, InputOption::VALUE_NONE, $description);
        }
    }

    protected static function startsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && mb_strpos($haystack, $needle) === 0) {
                return true;
            }
        }

        return false;
    }

    protected static function endsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ((string) $needle === static::substr($haystack, -static::length($needle))) {
                return true;
            }
        }

        return false;
    }

    protected static function substr($string, $start, $length = null)
    {
        return mb_substr($string, $start, $length, 'UTF-8');
    }

    protected static function length($value)
    {
        return mb_strlen($value);
    }
}

