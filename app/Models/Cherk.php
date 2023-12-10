<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cherk extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'sold',
        'percentage',
        'net',
        'sprint_id'
    ];

    // Define the relationship with the Sprint model
    public function sprint()
    {
        return $this->belongsTo(Sprint::class, 'sprint_id');
    }

    // If you want to automatically cast the date field to a Carbon instance
    protected $dates = ['date'];

}
