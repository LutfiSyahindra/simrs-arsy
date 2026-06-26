<?php

namespace App\Models\dbSimrs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generateGiziModel extends Model
{
    protected $table = 'generate_gizi';

    protected $fillable = [
        'periode',
        'source_periode',
        'source_period_mode',
        'source_tgl_awal',
        'source_tgl_akhir',
        'jenis_gizi',
        'jnsTindakan_id',
        'kode_jenis_tindakan',
        'nama_jenis_tindakan',
        'jumlah_data_sumber',
        'jumlah_pasien_sumber',
        'jumlah_pasien',
        'jumlah_tindakan',
        'jumlah_tindakan_konsul',
        'jumlah_tindakan_diit',
        'grand_total',
        'grand_total_konsul',
        'grand_total_diit',
        'total_konsul_pegawai',
        'total_konsul_premi_bersama',
        'total_diit_petugas_pool',
        'diit_petugas_per_orang',
        'total_diit_premi_bersama',
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
        'jumlah_pasien' => 'integer',
        'jumlah_tindakan' => 'integer',
        'jumlah_tindakan_konsul' => 'integer',
        'jumlah_tindakan_diit' => 'integer',
        'grand_total' => 'integer',
        'grand_total_konsul' => 'integer',
        'grand_total_diit' => 'integer',
        'total_konsul_pegawai' => 'integer',
        'total_konsul_premi_bersama' => 'integer',
        'total_diit_petugas_pool' => 'integer',
        'diit_petugas_per_orang' => 'integer',
        'total_diit_premi_bersama' => 'integer',
        'total_premi_bersama' => 'integer',
        'total_dibagikan' => 'integer',
        'config_snapshot' => 'array',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(generateGiziDetailModel::class, 'generate_gizi_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(generateGiziRecipientModel::class, 'generate_gizi_id');
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
