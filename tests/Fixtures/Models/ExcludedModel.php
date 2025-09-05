<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class ExcludedModel extends Model
{
    protected $table = 'excluded_table';
}