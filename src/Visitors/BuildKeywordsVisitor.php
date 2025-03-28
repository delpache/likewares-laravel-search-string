<?php

namespace Likewares\LaravelSearchString\Visitors;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Likewares\LaravelSearchString\AST\ListSymbol;
use Likewares\LaravelSearchString\AST\RelationshipSymbol;
use Likewares\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Likewares\LaravelSearchString\AST\QuerySymbol;
use Likewares\LaravelSearchString\Options\KeywordRule;
use Likewares\LaravelSearchString\SearchStringManager;

class BuildKeywordsVisitor extends Visitor
{
    /** @var SearchStringManager */
    protected $manager;
    protected $builder;

    public function __construct(SearchStringManager $manager, $builder)
    {
        $this->manager = $manager;
        $this->builder = $builder;
    }

    public function visitRelationship(RelationshipSymbol $relationship)
    {
        // Keywords are not allowed within relationships.
        return $relationship;
    }

    public function visitQuery(QuerySymbol $query)
    {
        if (! $query->rule instanceof KeywordRule) {
            return $query;
        }

        switch ($query->rule->column) {
            case 'order_by':
                $this->buildOrderBy($query->value);
                break;
            case 'select':
                $this->buildSelect($query->value, $query->operator === '!=');
                break;
            case 'limit':
                $this->buildLimit($query->value);
                break;
            case 'offset':
                $this->buildOffset($query->value);
                break;
        }

        return $query;
    }

    public function visitList(ListSymbol $list)
    {
        if (! $list->rule instanceof KeywordRule) {
            return $list;
        }

        switch ($list->rule->column) {
            case 'order_by':
                $this->buildOrderBy($list->values);
                break;
            case 'select':
                $this->buildSelect($list->values, $list->negated);
                break;
            case 'limit':
                throw InvalidSearchStringException::fromVisitor('The limit must be an integer');
            case 'offset':
                throw InvalidSearchStringException::fromVisitor('The offset must be an integer');
        }

        return $list;
    }

    protected function buildOrderBy($values)
    {
        $this->builder->getQuery()->orders = null;

        Collection::wrap($values)->each(function ($value) {
            $desc = Str::startsWith($value, '-') ? 'desc' : 'asc';
            $column = Str::startsWith($value, '-') ? Str::after($value, '-') : $value;
            $column = $this->manager->getColumnNameFromAlias($column);
            $qualifiedColumn = SearchStringManager::qualifyColumn($this->builder, $column);
            $this->builder->orderBy($qualifiedColumn, $desc);
        });
    }

    protected function buildSelect($values, bool $negated)
    {
        $columns = Collection::wrap($values)->map(function ($value) {
            return $this->manager->getColumnNameFromAlias($value);
        });

        $columns = $negated
            ? $this->manager->getColumns()->diff($columns)
            : $this->manager->getColumns()->intersect($columns);

        $columns = $columns->map(function ($column) {
            return SearchStringManager::qualifyColumn($this->builder, $column);
        });

        $this->builder->select($columns->values()->toArray());
    }

    protected function buildLimit($value)
    {
        if (! ctype_digit($value)) {
            throw InvalidSearchStringException::fromVisitor('The limit must be an integer');
        }

        $this->builder->limit($value);
    }

    protected function buildOffset($value)
    {
        if (! ctype_digit($value)) {
            throw InvalidSearchStringException::fromVisitor('The offset must be an integer');
        }

        $this->builder->offset($value);
    }
}
