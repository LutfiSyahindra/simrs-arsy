<?php

namespace App\Models\dbSimrs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generatePremiBersamaModel extends Model
{
    protected $table = 'generate_premi_bersama';

    protected $fillable = [
        'periode',
        'source_periode',
        'bpjs_source_mode',
        'bpjs_source_periode',
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
        'kamar_plotingPremi_id',
        'kamar_kode_ploting',
        'kamar_nama_ploting',
        'bhp_plotingPremi_id',
        'bhp_kode_ploting',
        'bhp_nama_ploting',
        'ignore_icu',
        'ignore_nicu',
        'jumlah_transaksi',
        'jumlah_pasien',
        'jumlah_jenis_tindakan',
        'jumlah_mapping_premi',
        'jumlah_terabaikan_icu',
        'jumlah_terabaikan_nicu',
        'total_biaya_rawat',
        'total_tindakan_rawat',
        'jumlah_sumber_generator',
        'jumlah_sumber_terkunci',
        'total_generator_sumber',
        'grand_total',
        'jumlah_penerima',
        'total_skor',
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
        'kamar_plotingPremi_id' => 'integer',
        'bhp_plotingPremi_id' => 'integer',
        'ignore_icu' => 'boolean',
        'ignore_nicu' => 'boolean',
        'jumlah_transaksi' => 'integer',
        'jumlah_pasien' => 'integer',
        'jumlah_jenis_tindakan' => 'integer',
        'jumlah_mapping_premi' => 'integer',
        'jumlah_terabaikan_icu' => 'integer',
        'jumlah_terabaikan_nicu' => 'integer',
        'total_biaya_rawat' => 'decimal:2',
        'total_tindakan_rawat' => 'decimal:2',
        'jumlah_sumber_generator' => 'integer',
        'jumlah_sumber_terkunci' => 'integer',
        'total_generator_sumber' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'jumlah_penerima' => 'integer',
        'total_skor' => 'decimal:2',
        'total_dibagikan' => 'decimal:2',
        'config_snapshot' => 'array',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function sources(): HasMany
    {
        return $this->hasMany(generatePremiBersamaSourceModel::class, 'generate_premi_bersama_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(generatePremiBersamaDetailModel::class, 'generate_premi_bersama_id');
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(generatePremiBersamaDistributionModel::class, 'generate_premi_bersama_id');
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
