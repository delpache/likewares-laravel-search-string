<?php

namespace Likewares\LaravelSearchString\Tests\Stubs;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Likewares\LaravelSearchString\Concerns\SearchString;

class CommentUser extends Pivot
{
    use SearchString;

    protected $searchStringColumns = [
        'comment' => ['relationship' => true],
        'user' => ['relationship' => true],
        'created_at' => 'date',
    ];

    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
