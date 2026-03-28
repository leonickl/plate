<?php

namespace LeoNickl\Plate;

class Plate
{
    private function __construct(private string $raw) {}

    public static function raw(string $raw): Plate
    {
        return new Plate($raw);
    }

    public static function file(string $path): Plate
    {
        return new Plate(file_get_contents($path));
    }

    public function parse(): array
    {
        return new Parser(str_split($this->raw))->parse()->get();
    }

    public function convert(): string
    {
        return new Converter($this->parse())->convert();
    }

    public function __toString(): string
    {
        return $this->convert();
    }
}
