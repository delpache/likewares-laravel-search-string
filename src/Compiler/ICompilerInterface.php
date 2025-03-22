<?php

namespace Likewares\LaravelSearchString\Compiler;

use Illuminate\Support\Enumerable;
use Likewares\LaravelSearchString\AST\Symbol;

interface ICompilerInterface
{
    public function lex(?string $input): Enumerable;
    public function parse(?string $input): Symbol;
}
