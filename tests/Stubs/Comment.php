<?php

namespace Likewares\LaravelSearchString\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Likewares\LaravelSearchString\Concerns\SearchString;

class Comment extends Model
{
    use SearchString;

    protected $searchStringColumns = [
        'title' => ['searchable' => true],
        'body' => ['searchable' => true],
        'spam' => ['boolean' => true],
        'user' => [
            'key' => 'author',
            'relationship' => true,
        ],
        'favourites' => ['relationship' => true],
        'favouritors' => ['relationship' => true],
        'created_at' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function favourites()
    {
        return $this->hasMany(CommentUser::class);
    }

    public function favouritors()
    {
        return $this->belongsToMany(User::class);
    }
}
