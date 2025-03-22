<?php

namespace Likewares\LaravelSearchString\Visitors;

use Likewares\LaravelSearchString\AST\EmptySymbol;
use Likewares\LaravelSearchString\AST\ListSymbol;
use Likewares\LaravelSearchString\AST\QuerySymbol;
use Likewares\LaravelSearchString\AST\RelationshipSymbol;
use Likewares\LaravelSearchString\Options\KeywordRule;

class RemoveKeywordsVisitor extends Visitor
{
    public function visitRelationship(RelationshipSymbol $relationship)
    {
        // Keywords are not allowed within relationships.
        return $relationship;
    }

    public function visitQuery(QuerySymbol $query)
    {
        return $query->rule instanceof KeywordRule ? new EmptySymbol : $query;
    }

    public function visitList(ListSymbol $list)
    {
        return $list->rule instanceof KeywordRule ? new EmptySymbol : $list;
    }
}
