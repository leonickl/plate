<?php

namespace LeoNickl\Plate;

class Parser
{
    private State $state = State::HTML;
    private int $pos = 0;

    private string $current = '';
    private array $parsed = [];

    public function __construct(private array $tokens) {}

    public function parse(): Parser
    {
        while ($this->pos < count($this->tokens)) {
            $this->pos += $this->handle();
        }

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
        $next_token = $this->tokens[$this->pos];

        if ($this->state === State::HTML) {
            if ($token === '{' && $next_token === '{') {
                $this->memorizeAs('html', trim: false);
                $this->state = State::HEAD;
                return 2;
            }

            $this->current .= $token;
            return 1;
        }

        if ($this->state === State::HEAD) {
            if ($token === ':') {
                $this->memorizeAs('head', trim: true);
                $this->state = State::ARGS;
                return 1;
            }

            if ($token === '}' && $next_token === '}') {
                $this->memorizeAs('head', trim: true);
                $this->state = State::HTML;
                return 2;
            }

            $this->current .= $token;
            return 1;
        }

        if ($this->state === State::ARGS) {
            if ($token === '}' && $next_token === '}') {
                $this->memorizeAs('args', trim: true, extend: true);
                $this->state = State::HTML;
                return 2;
            }

            $this->current .= $token;
            return 1;
        }
    }

    public function dump(): void
    {
        foreach($this->parsed as $block) {
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