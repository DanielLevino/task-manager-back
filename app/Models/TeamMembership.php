<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMembership extends Model
{
    protected $fillable = ['id', 'team_id', 'user_id', 'role'];
    public $incrementing = false;
    protected $keyType = 'string';

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
