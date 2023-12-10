<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sprint extends Model
{
    use HasFactory;

    // Define the table associated with the Sprint model
    protected $table = 'sprints';

    // The model's default values for attributes
    protected $attributes = [
        'is_active' => true,
    ];

    // Automatically cast these fields to native types
    protected $casts = [
        'is_active' => 'boolean',
        'startDate' => 'datetime',
    ];

    // Fields that can be mass assigned
    protected $fillable = [
        'startDate',
        'user_id',
        'is_active'
    ];

    // Define relationship to User model
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

