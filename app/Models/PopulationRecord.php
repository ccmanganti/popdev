<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PopulationRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'household_number',
        'province',
        'city_or_municipality',
        'barangay',
        'address_1',
        'address_2',
        'name_of_respondent',
        'household_head',
        'household_members_total',
        'individual_record',
        'q25',
        'q26',
        'q27',
        'q28',
        'q29',
        'q30',
        'q31',
        'encoder_name',
        'signature',
        'data_privacy_consent',
    ];

    protected $casts = [
        'individual_record' => 'json',
    ];
}
