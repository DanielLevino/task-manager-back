<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = ['id', 'name', /*'icon',*/ 'created_by'];
    public $incrementing = false;
    protected $keyType = 'string';

    public function memberships()
    {
        return $this->hasMany(TeamMembership::class);
    }
}
