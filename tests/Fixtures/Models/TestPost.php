<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class TestPost extends Model
{
    protected $table = 'test_posts';
    
    protected $fillable = ['title', 'content'];
    
    public function user()
    {
        return $this->belongsTo(TestUser::class);
    }
    
    public function comments()
    {
        return $this->morphMany(TestComment::class, 'commentable');
    }
}