<?php

namespace LeoNickl\Plate;

use Exception;

class Converter
{
    public function __construct(private array $parsed) {}

    public function convert(): string
    {
        $php = '';

        foreach ($this->parsed as $block) {
            $php .= $this->convertBlock((object) $block);
        }

        return $php;
    }

    private function convertBlock(object $block): string
    {
        $head = property_exists($block, 'head') && $block->head !== '';
        $args = property_exists($block, 'args') && $block->args !== '';

        if ($head && $args) {
            if ($block->head === 'if:') {
                return "<?php if ($block->args): ?>";
            }

            if ($block->head === 'elseif:') {
                return "<?php elseif ($block->args): ?>";
            }

            if ($block->head === 'each:') {
                return "<?php foreach ($block->args): ?>";
            }

            throw new Exception("Unknown block head '$block->head' with args");
        }

        if ($head) {
            if ($block->head === 'else:') {
                return '<?php else: ?>';
            }

            if ($block->head === 'if;') {
                return '<?php endif ?>';
            }

            if ($block->head === 'each;') {
                return '<?php endforeach ?>';
            }

            throw new Exception("Unknown block head '$block->head' without args");
        }

        if ($args) {
            if (str_starts_with($block->args, '#')) {
                return '';
            }

            if (str_starts_with($block->args, '==')) {
                $expression = substr($block->args, 2);

                return "<?php echo $expression ?>";
            }

            if (str_starts_with($block->args, ':')) {
                $expression = substr($block->args, 1);

                return "<?php $expression ?>";
            }

            return "<?php echo htmlspecialchars(join(' ', [$block->args])) ?>";
        }

        if (property_exists($block, 'html')) {
            return $block->html;
        }

        throw new Exception('Illegal block');
    }
}
