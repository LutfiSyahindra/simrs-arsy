<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateApotekConfigMappingModel extends Model
{
    protected $table = 'generate_apotek_config_mapping';

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
        return $this->belongsTo(generateApotekConfigModel::class, 'config_id');
    }

    public function jenisTindakan(): BelongsTo
    {
        return $this->belongsTo(jnsTindakanModel::class, 'jnsTindakan_id');
    }
}
