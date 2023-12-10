<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Foam extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'sprint_id',
        'sold',
        'percentage',
        'net'
    ];

    // Relationship to Sprint model
    public function sprint()
    {
        return $this->belongsTo(Sprint::class, 'sprint_id');
    }

    // If you want to automatically cast the date field to a Carbon instance
    protected $dates = ['date'];

}
