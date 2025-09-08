<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title','description','due_date','status','priority','creator_id','assignee_id'
    ];

    public function scopeVisibleTo($query, User $user)
    {
        // vê as que criou ou que estão atribuídas a ele
        return $query->where(function ($w) use ($user) {
            $w->where('creator_id', $user->id)
              ->orWhere('assignee_id', $user->id);
        });
    }

    public function creator() { return $this->belongsTo(User::class, 'creator_id'); }
    public function assignee() { return $this->belongsTo(User::class, 'assignee_id'); }
}
