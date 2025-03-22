<?php

namespace Likewares\LaravelSearchString\Visitors;

use Illuminate\Support\Arr;
use Likewares\LaravelSearchString\AST\AndSymbol;
use Likewares\LaravelSearchString\AST\ListSymbol;
use Likewares\LaravelSearchString\AST\NotSymbol;
use Likewares\LaravelSearchString\AST\OrSymbol;
use Likewares\LaravelSearchString\AST\QuerySymbol;
use Likewares\LaravelSearchString\AST\RelationshipSymbol;
use Likewares\LaravelSearchString\AST\SoloSymbol;

class RemoveNotSymbolVisitor extends Visitor
{
    protected $negate = false;

    public function visitOr(OrSymbol $or)
    {
        $originalNegate = $this->negate;

        $leaves = $or->expressions->map(function ($expression) use ($originalNegate) {
            $this->negate = $originalNegate;
            return $expression->accept($this);
        });

        $this->negate = $originalNegate;

        return $this->negate ? new AndSymbol($leaves) : new OrSymbol($leaves);
    }

    public function visitAnd(AndSymbol $and)
    {
        $originalNegate = $this->negate;

        $leaves = $and->expressions->map(function ($expression) use ($originalNegate) {
            $this->negate = $originalNegate;
            return $expression->accept($this);
        });

        $this->negate = $originalNegate;

        return $this->negate ? new OrSymbol($leaves) : new AndSymbol($leaves);
    }

    public function visitNot(NotSymbol $not)
    {
        $this->negate = ! $this->negate;
        $expression = $not->expression->accept($this);
        $this->negate = false;

        return $expression;
    }

    public function visitRelationship(RelationshipSymbol $relationship)
    {
        $originalNegate = $this->negate;
        $this->negate = false;
        $relationship->expression = $relationship->expression->accept($this);
        $this->negate = $originalNegate;

        if ($this->negate) {
            $relationship->expectedOperator = $this->reverseOperator($relationship->expectedOperator);
        }

        return $relationship;
    }

    public function visitSolo(SoloSymbol $solo)
    {
        if ($this->negate) {
            $solo->negate();
        }

        return $solo;
    }

    public function visitQuery(QuerySymbol $query)
    {
        if (! $this->negate) {
            return $query;
        }

        if (is_bool($query->value)) {
            $query->value = ! $query->value;
            return $query;
        }

        $query->operator = $this->reverseOperator($query->operator);
        return $query;
    }

    public function visitList(ListSymbol $list)
    {
        if ($this->negate) {
            $list->negate();
        }

        return $list;
    }

    protected function reverseOperator($operator)
    {
        return Arr::get([
            '=' => '!=',
            '!=' => '=',
            '>' => '<=',
            '>=' => '<',
            '<' => '>=',
            '<=' => '>',
        ], $operator, $operator);
    }
}
