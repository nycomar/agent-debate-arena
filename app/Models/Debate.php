<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Debate extends Model
{
    protected $fillable = [
        'topic',
        'status',
        'agents',
        'arguments',
        'winner',
        'space_debate_id',
        'space_round_ids'
    ];
}