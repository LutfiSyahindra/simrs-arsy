<?php

namespace App\Models\dbSimrs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generateApotekModel extends Model
{
    protected $table = 'generate_apotek';

    protected $fillable = [
        'periode',
        'source_periode',
        'source_tgl_awal',
        'source_tgl_akhir',
        'jenis_apotek',
        'jnsTindakan_id',
        'kode_jenis_tindakan',
        'nama_jenis_tindakan',
        'jumlah_data_sumber',
        'jumlah_pasien_sumber',
        'jumlah_obat_sumber',
        'jumlah_data_mapping',
        'jumlah_pasien',
        'jumlah_obat',
        'total_qty',
        'tarif_per_item',
        'grand_total',
        'total_jasa_farmasi_pool',
        'total_formula_31',
        'total_formula_7',
        'total_formula_12',
        'total_premi_bersama',
        'total_dibagikan',
        'config_snapshot',
        'is_locked',
        'locked_at',
        'locked_by',
        'generate_by',
    ];

    protected $casts = [
        'source_tgl_awal' => 'date',
        'source_tgl_akhir' => 'date',
        'jnsTindakan_id' => 'integer',
        'jumlah_data_sumber' => 'integer',
        'jumlah_pasien_sumber' => 'integer',
        'jumlah_obat_sumber' => 'integer',
        'jumlah_data_mapping' => 'integer',
        'jumlah_pasien' => 'integer',
        'jumlah_obat' => 'integer',
        'total_qty' => 'float',
        'tarif_per_item' => 'integer',
        'grand_total' => 'integer',
        'total_jasa_farmasi_pool' => 'integer',
        'total_formula_31' => 'integer',
        'total_formula_7' => 'integer',
        'total_formula_12' => 'integer',
        'total_premi_bersama' => 'integer',
        'total_dibagikan' => 'integer',
        'config_snapshot' => 'array',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(generateApotekDetailModel::class, 'generate_apotek_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(generateApotekRecipientModel::class, 'generate_apotek_id');
    }

    public function jenisTindakan(): BelongsTo
    {
        return $this->belongsTo(jnsTindakanModel::class, 'jnsTindakan_id');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function generateBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generate_by');
    }
}
