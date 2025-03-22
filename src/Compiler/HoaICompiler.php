<?php

namespace Likewares\LaravelSearchString\Compiler;

use Hoa\Compiler\Exception\UnrecognizedToken;
use Hoa\Compiler\Llk\Lexer;
use Hoa\Compiler\Llk\Llk;
use Hoa\Compiler\Llk\Parser;
use Hoa\File\Read;
use Illuminate\Support\Enumerable;
use Illuminate\Support\LazyCollection;
use Likewares\LaravelSearchString\AST\EmptySymbol;
use Likewares\LaravelSearchString\AST\Symbol;
use Likewares\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Likewares\LaravelSearchString\SearchStringManager;

class HoaICompiler implements ICompilerInterface
{
    protected $manager;

    public function __construct(SearchStringManager $manager)
    {
        $this->manager = $manager;
    }

    public function lex(?string $input): Enumerable
    {
        Llk::parsePP($this->manager->getGrammar(), $tokens, $rules, $pragmas, 'streamName');
        $lexer = new Lexer($pragmas);

        try {
            $generator = $lexer->lexMe($input ?? '', $tokens);
        } catch (UnrecognizedToken $exception) {
            throw InvalidSearchStringException::fromLexer($exception->getMessage(), $exception->getArguments()[1]);
        }

        return LazyCollection::make(function() use ($generator) {
            yield from $generator;
        });
    }

    public function parse(?string $input): Symbol
    {
        if (! $input) {
            return new EmptySymbol();
        }

        try {
            $ast = $this->getParser()->parse($input);
        } catch (UnrecognizedToken $exception) {
            throw InvalidSearchStringException::fromLexer($exception->getMessage(), $exception->getArguments()[1]);
        }

       return $ast->accept(new HoaConverterVisitor());
    }

    protected function getParser()
    {
        if (class_exists(CompiledParser::class)) {
            return new CompiledParser();
        }

        return $this->loadParser();
    }

    protected function loadParser(): Parser
    {
        return Llk::load(new Read($this->manager->getGrammarFile()));
    }

    protected function saveParser(): void
    {
        $file = "<?php\n\n";
        $file .= "namespace Likewares\LaravelSearchString\Compiler;\n\n";
        $file .= Llk::save($this->loadParser(), 'CompiledParser');

        file_put_contents(__DIR__ . '/CompiledParser.php', $file);
    }
}
