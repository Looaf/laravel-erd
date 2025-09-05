<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class TestRole extends Model
{
    protected $table = 'test_roles';
    
    protected $fillable = ['name'];
    
    public function users()
    {
        return $this->belongsToMany(TestUser::class, 'test_user_roles');
    }
}