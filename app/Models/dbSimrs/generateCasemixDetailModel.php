<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateCasemixDetailModel extends Model
{
    protected $table = 'generate_casemix_detail';

    protected $fillable = [
        'generate_casemix_id',
        'row_type',
        'question_key',
        'question_label',
        'answer_key',
        'answer_label',
        'score',
        'max_score',
        'role',
        'role_label',
        'pegawai_id',
        'pegawai_name',
        'pegawai_position',
        'allocation_percent',
        'pool_total',
        'amount_per_recipient',
        'total_received',
    ];

    protected $casts = [
        'score' => 'integer',
        'max_score' => 'integer',
        'allocation_percent' => 'float',
        'pool_total' => 'integer',
        'amount_per_recipient' => 'integer',
        'total_received' => 'integer',
    ];

    public function result(): BelongsTo
    {
        return $this->belongsTo(generateCasemixModel::class, 'generate_casemix_id');
    }
}
