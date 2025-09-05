<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class TestComment extends Model
{
    protected $table = 'test_comments';
    
    protected $fillable = ['content'];
    
    public function user()
    {
        return $this->belongsTo(TestUser::class);
    }
    
    public function commentable()
    {
        return $this->morphTo();
    }
}