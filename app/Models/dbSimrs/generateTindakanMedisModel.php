<?php

namespace App\Models\dbSimrs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generateTindakanMedisModel extends Model
{
    protected $table = 'generate_tindakan_medis';

    protected $fillable = [
        'periode',
        'source_periode',
        'source_tgl_awal',
        'source_tgl_akhir',
        'jenis_pelayanan',
        'jnsPremi_id',
        'kode_premi',
        'nama_premi',
        'ugd_plotingPremi_id',
        'ugd_kode_ploting',
        'ugd_nama_ploting',
        'vk_plotingPremi_id',
        'vk_kode_ploting',
        'vk_nama_ploting',
        'bpjs_source_mode',
        'ignore_icu',
        'ignore_nicu',
        'jumlah_transaksi',
        'jumlah_pasien',
        'jumlah_jenis_tindakan',
        'jumlah_mapping_premi',
        'jumlah_terabaikan_icu',
        'jumlah_terabaikan_nicu',
        'total_biaya_rawat',
        'total_mapping_premi',
        'total_ugd',
        'total_vk',
        'grand_total',
        'pembagi',
        'total_final',
        'distribution_mode',
        'jumlah_penerima',
        'total_dasar_dibagikan',
        'total_tambahan_icu',
        'total_tambahan_nicu',
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
        'jnsPremi_id' => 'integer',
        'ugd_plotingPremi_id' => 'integer',
        'vk_plotingPremi_id' => 'integer',
        'ignore_icu' => 'boolean',
        'ignore_nicu' => 'boolean',
        'jumlah_transaksi' => 'integer',
        'jumlah_pasien' => 'integer',
        'jumlah_jenis_tindakan' => 'integer',
        'jumlah_mapping_premi' => 'integer',
        'jumlah_terabaikan_icu' => 'integer',
        'jumlah_terabaikan_nicu' => 'integer',
        'total_biaya_rawat' => 'decimal:2',
        'total_mapping_premi' => 'decimal:2',
        'total_ugd' => 'decimal:2',
        'total_vk' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'pembagi' => 'integer',
        'total_final' => 'decimal:2',
        'jumlah_penerima' => 'integer',
        'total_dasar_dibagikan' => 'decimal:2',
        'total_tambahan_icu' => 'decimal:2',
        'total_tambahan_nicu' => 'decimal:2',
        'total_dibagikan' => 'decimal:2',
        'config_snapshot' => 'array',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(generateTindakanMedisDetailModel::class, 'generate_tindakan_medis_id');
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(generateTindakanMedisDistributionModel::class, 'generate_tindakan_medis_id');
    }

    public function jnsPremi(): BelongsTo
    {
        return $this->belongsTo(jnsPremiModel::class, 'jnsPremi_id');
    }

    public function ugdPloting(): BelongsTo
    {
        return $this->belongsTo(plotingPremiModel::class, 'ugd_plotingPremi_id');
    }

    public function vkPloting(): BelongsTo
    {
        return $this->belongsTo(plotingPremiModel::class, 'vk_plotingPremi_id');
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
