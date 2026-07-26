<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MuscleGroupTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'muscle_group_id',
        'locale',
        'name',
    ];

    public function muscleGroup(): BelongsTo
    {
        return $this->belongsTo(MuscleGroup::class);
    }
}
