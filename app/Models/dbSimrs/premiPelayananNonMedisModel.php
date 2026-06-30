<?php

namespace App\Models\dbSimrs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class premiPelayananNonMedisModel extends Model
{
    protected $table = 'premi_pelayanan_non_medis';

    protected $fillable = [
        'periode',
        'jenis_pelayanan',
        'jnsPremi_id',
        'kode_premi',
        'nama_premi',
        'generate_bhp_id',
        'generate_kamar_inap_id',
        'jumlah_transaksi',
        'jumlah_jenis_tindakan',
        'jumlah_mapping_premi',
        'total_biaya_rawat',
        'total_mapping_premi',
        'total_bhp',
        'total_kamar_inap',
        'total_final',
        'is_locked',
        'locked_at',
        'locked_by',
        'generate_by',
    ];

    protected $casts = [
        'jnsPremi_id' => 'integer',
        'jumlah_transaksi' => 'integer',
        'jumlah_jenis_tindakan' => 'integer',
        'jumlah_mapping_premi' => 'integer',
        'total_biaya_rawat' => 'decimal:2',
        'total_mapping_premi' => 'decimal:2',
        'total_bhp' => 'decimal:2',
        'total_kamar_inap' => 'decimal:2',
        'total_final' => 'decimal:2',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(
            premiPelayananNonMedisDetailModel::class,
            'premi_pelayanan_non_medis_id'
        );
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(
            premiPelayananNonMedisDistributionModel::class,
            'premi_pelayanan_non_medis_id'
        );
    }

    public function generateBhp(): BelongsTo
    {
        return $this->belongsTo(generateBhpModel::class, 'generate_bhp_id');
    }

    public function generateKamar(): BelongsTo
    {
        return $this->belongsTo(generateKamarModel::class, 'generate_kamar_inap_id');
    }

    public function jnsPremi(): BelongsTo
    {
        return $this->belongsTo(jnsPremiModel::class, 'jnsPremi_id');
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
