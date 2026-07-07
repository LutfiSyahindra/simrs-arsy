<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generatePremiBersamaSourceModel extends Model
{
    protected $table = 'generate_premi_bersama_source';

    protected $fillable = [
        'generate_premi_bersama_id',
        'source_key',
        'source_label',
        'source_table',
        'source_id',
        'source_periode',
        'source_type',
        'plotingPremi_id',
        'kode_ploting',
        'nama_ploting',
        'jumlah_data',
        'total_asal',
        'total_diambil',
        'is_locked',
        'status_label',
        'note',
        'raw_snapshot',
    ];

    protected $casts = [
        'source_id' => 'integer',
        'plotingPremi_id' => 'integer',
        'jumlah_data' => 'integer',
        'total_asal' => 'decimal:2',
        'total_diambil' => 'decimal:2',
        'is_locked' => 'boolean',
        'raw_snapshot' => 'array',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(generatePremiBersamaModel::class, 'generate_premi_bersama_id');
    }
}
