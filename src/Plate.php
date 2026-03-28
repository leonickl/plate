<?php

namespace LeoNickl\Plate;

use Exception;

function dd(mixed ...$x) {
    var_dump(...$x);
    die;
}

enum State
{
    case HTML;
    case HEAD;
    case ARGS;
}

class Plate
{
    private State $state = State::HTML;
    private int $pos = 0;

    private string $current = '';
    private array $parsed = [];

    private function __construct(private array $tokens) {}

    public static function raw(string $raw): Plate
    {
        return new Plate(str_split($raw));
    }

    public static function file(string $path): Plate
    {
        return Plate::raw(file_get_contents($path));
    }

    public function parse(): Plate
    {
        while ($this->pos < count($this->tokens)) {
            $this->pos += $this->handle();
        }

        return $this;
    }

    public function memorizeAs(string $title, bool $trim, bool $extend = false): void
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

    public function toPHP(): string
    {
        $php = '';

        foreach($this->parsed as $block) {
            $php .= $this->blockToPHP((object)$block);
        }

        return $php;
    }

    private function blockToPHP(object $block): string
    {
        if (property_exists($block, 'html')) {
            return $block->html;
        }

        $head = property_exists($block, 'head') && $block->head !== '';
        $args = property_exists($block, 'args') && $block->args !== '';

        if ($head && $args) {
            if ($block->head === 'if') {
                return "<?php if ($block->args): ?>";
            }

            if ($block->head === 'elseif') {
                return "<?php elseif ($block->args): ?>";
            }

            if ($block->head === 'each') {
                return "<?php foreach ($block->args): ?>";
            }

            throw new Exception("Unknown block head '$block->head' with args");
        }

        if ($head) {
            if ($block->head === 'else') {
                return "<?php else: ?>";
            }

            if ($block->head === 'if;') {
                return "<?php endif ?>";
            }

            if ($block->head === 'each;') {
                return "<?php endforeach ?>";
            }

            if(str_starts_with($block->head, '==')) {
                $expression = substr($block->head, 2);
                return "<?php echo $expression ?>";
            }

            if(str_starts_with($block->head, '#')) {
                return "";
            }

            return "<?php echo htmlspecialchars(join(' ', [$block->head])) ?>";
        }

        if ($args) {
            return "<?php $block->args; ?>";
        }

        throw new Exception("Illegal block");
    }
}
