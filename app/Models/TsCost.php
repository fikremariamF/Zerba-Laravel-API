<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TsCost extends Model
{
    use HasFactory;

    protected $table = 'ts_costs'; // Specify the table name if it's not the plural of the model name

    protected $fillable = [
        'date',
        'spent',
        'sprint_id'
    ];

    // If you want to automatically cast the date field to a Carbon instance
    protected $dates = ['date'];

}
