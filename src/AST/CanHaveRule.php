<?php

namespace Likewares\LaravelSearchString\AST;

use Likewares\LaravelSearchString\Options\Rule;

trait CanHaveRule
{
    /** @var Rule */
    public $rule;

    public function attachRule(Rule $rule)
    {
        $this->rule = $rule;

        return $this;
    }
}
