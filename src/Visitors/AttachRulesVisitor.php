<?php

namespace Likewares\LaravelSearchString\Visitors;

use Likewares\LaravelSearchString\AST\ListSymbol;
use Likewares\LaravelSearchString\AST\QuerySymbol;
use Likewares\LaravelSearchString\AST\RelationshipSymbol;
use Likewares\LaravelSearchString\AST\SearchSymbol;
use Likewares\LaravelSearchString\AST\SoloSymbol;
use Likewares\LaravelSearchString\AST\Symbol;
use Likewares\LaravelSearchString\SearchStringManager;

class AttachRulesVisitor extends Visitor
{
    /** @var SearchStringManager */
    protected $manager;

    public function __construct(SearchStringManager $manager)
    {
        $this->manager = $manager;
    }

    public function visitRelationship(RelationshipSymbol $relationship)
    {
        if (! $rule = $this->manager->getColumnRule($relationship->key)) {
            return $relationship;
        }

        $relationship->attachRule($rule);

        $originalManager = $this->manager;
        $this->manager = $rule->relationshipModel->getSearchStringManager();
        $relationship->expression = $relationship->expression->accept($this);
        $this->manager = $originalManager;

        return $relationship;
    }

    public function visitSolo(SoloSymbol $solo)
    {
        return $this->attachRuleFromColumns($solo, $solo->content);
    }

    public function visitQuery(QuerySymbol $query)
    {
        return $this->attachRuleFromKeywordsOrColumns($query, $query->key);
    }

    public function visitList(ListSymbol $list)
    {
        return $this->attachRuleFromKeywordsOrColumns($list, $list->key);
    }

    protected function attachRuleFromKeywordsOrColumns(Symbol $symbol, string $key)
    {
        if ($rule = $this->manager->getRule($key)) {
            return $symbol->attachRule($rule);
        }

        return $symbol;
    }

    protected function attachRuleFromColumns(Symbol $symbol, string $key)
    {
        if ($rule = $this->manager->getColumnRule($key)) {
            return $symbol->attachRule($rule);
        }

        return $symbol;
    }
}
