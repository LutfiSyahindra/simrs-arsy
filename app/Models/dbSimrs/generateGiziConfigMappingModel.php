<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateGiziConfigMappingModel extends Model
{
    protected $table = 'generate_gizi_config_mapping';

    protected $fillable = [
        'config_id',
        'kelompok',
        'jnsTindakan_id',
    ];

    protected $casts = [
        'config_id' => 'integer',
        'jnsTindakan_id' => 'integer',
    ];

    public function config(): BelongsTo
    {
        return $this->belongsTo(generateGiziConfigModel::class, 'config_id');
    }

    public function jenisTindakan(): BelongsTo
    {
        return $this->belongsTo(jnsTindakanModel::class, 'jnsTindakan_id');
    }
}
