<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class TestProfile extends Model
{
    protected $table = 'test_profiles';
    
    protected $fillable = ['bio', 'avatar'];
    
    public function user()
    {
        return $this->belongsTo(TestUser::class);
    }
}