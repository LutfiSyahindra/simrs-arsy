<?php

namespace App\Models\dbSimrs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generatePremiDokterModel extends Model
{
    protected $table = 'generate_premi_dokter';

    protected $fillable = [
        'periode',
        'source_periode',
        'source_period_mode',
        'source_tgl_awal',
        'source_tgl_akhir',
        'jenis_premi_dokter',
        'jenis_pelayanan',
        'visite_umum_percent',
        'visite_bpjs_percent',
        'visite_bpjs_nominal',
        'jumlah_transaksi',
        'jumlah_pasien',
        'jumlah_dokter',
        'jumlah_tindakan',
        'jumlah_mapping_tindakan',
        'jumlah_tidak_terkonfigurasi',
        'total_biaya_rawat',
        'total_grand',
        'total_premi',
        'config_snapshot',
        'is_locked',
        'locked_at',
        'locked_by',
        'generate_by',
    ];

    protected $casts = [
        'source_tgl_awal' => 'date',
        'source_tgl_akhir' => 'date',
        'source_period_mode' => 'string',
        'visite_umum_percent' => 'float',
        'visite_bpjs_percent' => 'float',
        'visite_bpjs_nominal' => 'integer',
        'jumlah_transaksi' => 'integer',
        'jumlah_pasien' => 'integer',
        'jumlah_dokter' => 'integer',
        'jumlah_tindakan' => 'integer',
        'jumlah_mapping_tindakan' => 'integer',
        'jumlah_tidak_terkonfigurasi' => 'integer',
        'total_biaya_rawat' => 'float',
        'total_grand' => 'float',
        'total_premi' => 'float',
        'config_snapshot' => 'array',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(generatePremiDokterDetailModel::class, 'generate_premi_dokter_id');
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
