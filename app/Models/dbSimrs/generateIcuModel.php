<?php

namespace App\Models\dbSimrs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generateIcuModel extends Model
{
    protected $table = 'generate_icu';

    protected $fillable = [
        'periode',
        'source_periode',
        'source_tgl_awal',
        'source_tgl_akhir',
        'jenis_icu',
        'jnsTindakan_id',
        'kode_jenis_tindakan',
        'nama_jenis_tindakan',
        'jumlah_pasien_sumber',
        'jumlah_pasien_icu',
        'jumlah_pasien',
        'jumlah_tindakan',
        'jumlah_tindakan_icu',
        'jumlah_tindakan_kritikal',
        'grand_total',
        'total_perawat_icu',
        'total_perawat_icu_reguler',
        'total_pegawai_icu_khusus',
        'total_premi_medis_pool',
        'premi_medis_per_orang',
        'total_premi_bersama',
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
        'jumlah_pasien_sumber' => 'integer',
        'jumlah_pasien_icu' => 'integer',
        'jumlah_pasien' => 'integer',
        'jumlah_tindakan' => 'integer',
        'jumlah_tindakan_icu' => 'integer',
        'jumlah_tindakan_kritikal' => 'integer',
        'grand_total' => 'integer',
        'total_perawat_icu' => 'integer',
        'total_perawat_icu_reguler' => 'integer',
        'total_pegawai_icu_khusus' => 'integer',
        'total_premi_medis_pool' => 'integer',
        'premi_medis_per_orang' => 'integer',
        'total_premi_bersama' => 'integer',
        'config_snapshot' => 'array',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(generateIcuDetailModel::class, 'generate_icu_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(generateIcuRecipientModel::class, 'generate_icu_id');
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
