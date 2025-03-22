<?php

namespace Likewares\LaravelSearchString\Visitors;

use Likewares\LaravelSearchString\AST\EmptySymbol;
use Likewares\LaravelSearchString\AST\QuerySymbol;
use Likewares\LaravelSearchString\AST\RelationshipSymbol;
use Likewares\LaravelSearchString\AST\SoloSymbol;
use Likewares\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Likewares\LaravelSearchString\Options\ColumnRule;
use Likewares\LaravelSearchString\Options\Rule;

class IdentifyRelationshipsFromRulesVisitor extends Visitor
{
    public function visitSolo(SoloSymbol $solo)
    {
        if (! $this->isRelationship($solo->rule)) {
            return $solo;
        }

        return (new RelationshipSymbol($solo->content, new EmptySymbol()))
            ->attachRule($solo->rule);
    }

    public function visitQuery(QuerySymbol $query)
    {
        if (! $this->isRelationship($query->rule)) {
            return $query;
        }

        if (! ctype_digit($query->value)) {
            throw InvalidSearchStringException::fromVisitor('The expected relationship count must be an integer');
        }

        return (new RelationshipSymbol($query->key, new EmptySymbol(), $query->operator, $query->value))
            ->attachRule($query->rule);
    }

    protected function isRelationship(?Rule $rule)
    {
        return $rule && $rule instanceof ColumnRule && $rule->relationship;
    }
}
