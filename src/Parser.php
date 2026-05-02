<?php

namespace LeoNickl\Plate;

use Exception;

class Parser
{
    private const array KEYWORDS = ['if:', 'elif:', 'else:', 'each:', 'if;', 'each;'];

    private State $state = State::HTML;

    private int $pos = 0;

    private ?string $string = null;

    private int $depth = 0;

    private bool $comment = false;

    private string $buffer = '';

    private string $current = '';

    private array $parsed = [];

    public function __construct(private array $tokens) {}

    public function parse(): Parser
    {
        while ($this->pos < count($this->tokens)) {
            $this->pos += $this->handle();
        }

        if ($this->string || $this->buffer) {
            throw new Exception('Unterminated string');
        }

        if ($this->state !== State::HTML) {
            throw new Exception('Some block not terminated');
        }

        $this->memorizeAs('html', trim: false);

        return $this;
    }

    private function memorizeAs(string $title, bool $trim, bool $extend = false): void
    {
        $current = $trim ? trim($this->current) : $this->current;

        if ($extend) {
            $this->parsed[count($this->parsed) - 1] = [
                ...$this->parsed[count($this->parsed) - 1],
                $title => $current,
            ];
        } else {
            $this->parsed[] = [
                $title => $current,
            ];
        }

        $this->current = '';
    }

    /**
     * @return int: 0 == leave, 1 == next, 2 == skip next
     */
    private function handle(): int
    {
        $token = $this->tokens[$this->pos];
        $next_token = $this->tokens[$this->pos + 1] ?? null;

        if ($this->string) {
            if ($token === '\\') {
                $this->buffer .= '\\'.$next_token;

                return 2;
            }

            // terminate a string
            if ($token === $this->string) {
                $this->current .= $this->string.$this->buffer.$this->string;
                $this->string = null;
                $this->buffer = '';

                return 1;
            }

            // continue a string
            $this->buffer .= $token;

            return 1;
        }

        if ($this->state === State::HTML) {
            if ($token === '{' && $next_token === '{') {
                $this->memorizeAs('html', trim: false);
                $this->state = State::HEAD;

                return 2;
            }

            $this->current .= $token;

            return 1;
        }

        // start a string
        if ($token === '"' || $token === "'") {
            $this->state = State::ARGS;
            $this->string = $token;

            return 1;
        }

        if ($this->state === State::HEAD) {
            if (in_array($this->buffer, self::KEYWORDS)) {
                $this->current = $this->buffer;
                $this->buffer = '';

                $this->memorizeAs('head', trim: true);
                $this->state = State::ARGS;

                return 0;
            }

            if (str_contains(" \n\r\t\v\x00", $token)) {
                return 1;
            }

            // take only lowercase letters and (semi)colon for head
            if (str_contains('abcdefghijklmnopqrstuvwxyz:;', $token)) {
                $this->buffer .= $token;

                return 1;
            }

            // add empty head if no whitespace or valid header symbol found
            $this->memorizeAs('head', trim: true);

            $this->current = $this->buffer;
            $this->buffer = '';

            $this->state = State::ARGS;

            return 0;
        }

        if ($this->state === State::ARGS) {
            if ($token === '{') {
                $this->depth++;
                $this->current .= '{';

                return 1;
            }

            if ($this->depth > 0 && $token === '}') {
                $this->depth--;
                $this->current .= '}';

                return 1;
            }

            if ($token === '}' && $next_token === '}') {
                if ($this->comment) {
                    $this->current .= '#'.$this->buffer;
                    $this->buffer = '';
                    $this->comment = false;
                }

                $this->memorizeAs('args', trim: true, extend: true);
                $this->state = State::HTML;

                return 2;
            }

            if ($token === '#') {
                $this->comment = true;

                return 1;
            }

            if ($this->comment) {
                $this->buffer .= $token;

                return 1;
            }

            $this->current .= $token;

            return 1;
        }
    }

    public function dump(): void
    {
        foreach ($this->parsed as $block) {
            foreach ($block as $key => $value) {
                echo "$key: '$value' ";
            }

            echo "\n";
        }
    }

    public function get(): array
    {
        return $this->parsed;
    }
}
