<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class TestUser extends Model
{
    protected $table = 'test_users';
    
    protected $fillable = ['name', 'email'];
    
    protected $guarded = ['password'];
    
    protected $casts = [
        'email_verified_at' => 'datetime'
    ];
    
    public function posts()
    {
        return $this->hasMany(TestPost::class);
    }
    
    public function profile()
    {
        return $this->hasOne(TestProfile::class);
    }
    
    public function roles()
    {
        return $this->belongsToMany(TestRole::class, 'test_user_roles');
    }
    
    public function comments()
    {
        return $this->hasMany(TestComment::class);
    }
}