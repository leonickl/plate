# Plate – a templating engine

The name is derived from Laravel's "blade". Blade offers a simple templating engine that can be compiled to a regular PHP file.

Examples can be found in `example.plate`. Try running `php index.php | php` to compile the example to view the compiles PHP.

Compilation is achieved calling `LeoNickl\Plate\Plate::file('filename.plate')` whose result can be cast to a string that contains the PHP code.

Plate directives are embraced in double curly braces (`{{ "hello" }}`). A single expression or multiple expressions separated by commas are escaped and then printed. Prefixing a single expression with `==` omits escaping. Using `:`as a prefix, nothing is printed, but the raw following PHP code is executed.
Conditional rendering with `if`, `elseif`, and `else` is supported, as well as foreach loops with `each`. For details, seee `example.plate`.
