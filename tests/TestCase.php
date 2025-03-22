<?php

namespace Likewares\LaravelSearchString\Tests;

use Illuminate\Database\Eloquent\Model;
use Likewares\LaravelSearchString\Concerns\SearchString;
use Likewares\LaravelSearchString\SearchStringManager;
use Likewares\LaravelSearchString\Tests\Stubs\Product;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return ['Likewares\LaravelSearchString\ServiceProvider'];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('search-string', include __DIR__ . '/../src/config.php');
    }

    public function getModelWithOptions($options)
    {
        return new class($options) extends Model {
            use SearchString;

            protected $table = 'models';
            protected $options = [];

            public function __construct($options)
            {
                parent::__construct();
                $this->options = $options;
            }

            public function getSearchStringOptions()
            {
                return $this->options;
            }
        };
    }

    public function getModelWithColumns($columns)
    {
        return $this->getModelWithOptions(['columns' => $columns]);
    }

    public function getModelWithKeywords($keywords)
    {
        return $this->getModelWithOptions(['keywords' => $keywords]);
    }

    public function getSearchStringManager($model = null)
    {
        return new SearchStringManager($model ?? new Product);
    }

    public function lex($input, $model = null)
    {
        return $this->getSearchStringManager($model)->getCompiler()->lex($input);
    }

    public function parse($input, $model = null)
    {
        return $this->getSearchStringManager($model)->parse($input);
    }

    public function visit($input, $visitors, $model = null)
    {
        $ast = is_string($input) ? $this->parse($input, $model) : $input;

        foreach ($visitors as $visitor) {
            $ast = $ast->accept($visitor);
        }

        return $ast;
    }

    public function build($input, $model = null)
    {
        return $this->getSearchStringManager($model)->createBuilder($input);
    }
}
