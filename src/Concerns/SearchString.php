<?php

namespace Likewares\LaravelSearchString\Concerns;

use Likewares\LaravelSearchString\SearchStringManager;
use Likewares\LaravelSearchString\Visitors\AttachRulesVisitor;
use Likewares\LaravelSearchString\Visitors\BuildColumnsVisitor;
use Likewares\LaravelSearchString\Visitors\BuildKeywordsVisitor;
use Likewares\LaravelSearchString\Visitors\IdentifyRelationshipsFromRulesVisitor;
use Likewares\LaravelSearchString\Visitors\OptimizeAstVisitor;
use Likewares\LaravelSearchString\Visitors\RemoveKeywordsVisitor;
use Likewares\LaravelSearchString\Visitors\RemoveNotSymbolVisitor;
use Likewares\LaravelSearchString\Visitors\ValidateRulesVisitor;

trait SearchString
{
    public function getSearchStringManager()
    {
        $managerClass = config('search-string.manager', SearchStringManager::class);
        return new $managerClass($this);
    }

    public function getSearchStringOptions()
    {
        return [
            'columns' => $this->searchStringColumns ?? [],
            'keywords' => $this->searchStringKeywords ?? [],
        ];
    }

    public function getSearchStringVisitors($manager, $builder)
    {
        return [
            new AttachRulesVisitor($manager),
            new IdentifyRelationshipsFromRulesVisitor(),
            new ValidateRulesVisitor(),
            new RemoveNotSymbolVisitor(),
            new BuildKeywordsVisitor($manager, $builder),
            new RemoveKeywordsVisitor(),
            new OptimizeAstVisitor(),
            new BuildColumnsVisitor($manager, $builder),
        ];
    }

    public function scopeUsingSearchString($query, $string)
    {
        $this->getSearchStringManager()->updateBuilder($query, $string);
    }
}
