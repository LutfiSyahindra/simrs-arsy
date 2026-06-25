<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateIcuConfigTindakanModel extends Model
{
    protected $table = 'generate_icu_config_tindakan';

    protected $fillable = [
        'config_id',
        'jnsTindakan_id',
    ];

    protected $casts = [
        'config_id' => 'integer',
        'jnsTindakan_id' => 'integer',
    ];

    public function config(): BelongsTo
    {
        return $this->belongsTo(generateIcuConfigModel::class, 'config_id');
    }

    public function jenisTindakan(): BelongsTo
    {
        return $this->belongsTo(jnsTindakanModel::class, 'jnsTindakan_id');
    }
}
