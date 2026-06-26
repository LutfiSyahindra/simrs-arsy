<?php

namespace App\Services\keuangan\premi;

use App\Models\User;
use App\Repositories\keuangan\premi\generateCasemixRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class generateCasemixService
{
    private const ROLE_LABELS = [
        'leader' => 'Ketua Tim Casemix',
        'kanit' => 'Kanit / Coding Tim Casemix',
        'inputer' => 'Inputer Casemix',
    ];

    private const DEFAULT_QUESTIONS = [
        [
            'key' => 'regular_claim_billing',
            'label' => 'Penagihan klaim reguler setiap tanggal 10 bulan berikutnya',
            'options' => [
                ['key' => 'lt_10', 'label' => '<10', 'score' => 2],
                ['key' => 'eq_10', 'label' => '10', 'score' => 1],
                ['key' => 'gt_10', 'label' => '>10', 'score' => 0],
            ],
        ],
        [
            'key' => 'pending_claim_billing',
            'label' => 'Penagihan klaim pending sebelum tanggal 15',
            'options' => [
                ['key' => 'lt_15', 'label' => '<15', 'score' => 2],
                ['key' => 'eq_15', 'label' => '15', 'score' => 1],
                ['key' => 'gt_15', 'label' => '>15', 'score' => 0],
            ],
        ],
        [
            'key' => 'initial_loss',
            'label' => 'Kerugian awal (klaim - modal) / klaim',
            'options' => [
                ['key' => 'lte_minus_15', 'label' => '<= -15%', 'score' => 2],
                ['key' => 'minus_15_to_minus_20', 'label' => '-15% sampai -20%', 'score' => 1],
                ['key' => 'gt_20', 'label' => '>20%', 'score' => 0],
            ],
        ],
        [
            'key' => 'pending_claim_ratio',
            'label' => 'Pending klaim dari total klaim',
            'options' => [
                ['key' => 'lt_10', 'label' => '<10%', 'score' => 2],
                ['key' => '10_to_15', 'label' => '10% sampai 15%', 'score' => 1],
                ['key' => 'gt_15', 'label' => '>15%', 'score' => 0],
            ],
        ],
        [
            'key' => 'pending_claim_completion',
            'label' => 'Penyelesaian klaim pending dari yang diajukan',
            'options' => [
                ['key' => 'gt_80', 'label' => '>80%', 'score' => 2],
                ['key' => '70_to_80', 'label' => '70% sampai 80%', 'score' => 1],
                ['key' => 'lt_70', 'label' => '<70%', 'score' => 0],
            ],
        ],
    ];

    public function __construct(
        protected generateCasemixRepository $repository
    ) {}

    public function getResults(?string $periode = null)
    {
        return $this->repository
            ->getResults($periode)
            ->map(fn ($row) => $this->resultPayload($row));
    }

    public function getSummary(?string $periode): array
    {
        return [
            'periode' => $periode,
            ...$this->repository->getSummary($periode),
        ];
    }

    public function getConfig(): array
    {
        return $this->configPayload($this->repository->getConfig());
    }

    public function updateConfig(array $data): array
    {
        $recipients = $this->hydrateRecipients($data['recipients'] ?? []);
        $payload = $this->configFormPayload($data);

        return $this->configPayload(
            $this->repository->saveConfig(
                $payload,
                $recipients
            )
        );
    }

    public function pegawaiOptions(?string $keyword = null)
    {
        return $this->repository
            ->searchPegawai($keyword)
            ->map(fn ($item) => [
                'id' => $item->nik,
                'nik' => $item->nik,
                'nama' => $item->nama,
                'jbtn' => $item->jbtn,
                'text' => trim($item->nik.' - '.$item->nama.' ('.($item->jbtn ?: '-').')'),
            ])
            ->values();
    }

    public function preview(array $data): array
    {
        return $this->calculate($data);
    }

    public function generate(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $existing = $this->repository->findExistingForUpdate($data['periode']);

            if ($existing?->is_locked) {
                throw ValidationException::withMessages([
                    'periode' => 'Data Casemix periode ini sudah dikunci.',
                ]);
            }

            $calculation = $this->calculate($data, true);

            return $this->resultPayload(
                $this->repository->saveResult($data['periode'], $calculation)
            );
        });
    }

    public function detail(int $id): array
    {
        $result = $this->repository->findWithDetails($id);

        if (! $result) {
            abort(404, 'Data generate Casemix tidak ditemukan.');
        }

        $payload = $this->resultPayload($result);
        $payload['questionnaire_details'] = collect($payload['details'])
            ->where('row_type', 'questionnaire')
            ->values();
        $payload['recipient_details'] = collect($payload['details'])
            ->where('row_type', 'recipient')
            ->values();
        $storedInputerDivider = data_get($payload, 'config_snapshot.pools.inputer_divider')
            ?? data_get($payload, 'config_snapshot.inputer_divider');
        $payload['recipient_groups'] = collect($payload['recipient_details'])
            ->groupBy('role')
            ->map(function ($items) use ($storedInputerDivider) {
                $role = $items->first()['role'];
                $divider = $role === 'inputer'
                    ? max(1, (int) ($storedInputerDivider ?: $items->count()))
                    : $items->count();

                return [
                    'role' => $role,
                    'role_label' => $items->first()['role_label'],
                    'pool_total' => $items->first()['pool_total'],
                    'allocation_percent' => $items->first()['allocation_percent'],
                    'amount_per_recipient' => $items->first()['amount_per_recipient'],
                    'divider' => $divider,
                    'recipient_count' => $items->count(),
                    'total_received' => $items->sum('total_received'),
                    'items' => $items->values(),
                ];
            })
            ->values();

        return $payload;
    }

    public function lock(int $id, User $user): array
    {
        return DB::transaction(function () use ($id, $user) {
            $result = $this->repository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data generate Casemix tidak ditemukan.');
            }

            if ($result->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Data sudah dalam keadaan terkunci.',
                ]);
            }

            return $this->lockPayload(
                $this->repository->updateLock($result, true, $user->id)
            );
        });
    }

    public function unlock(int $id, User $user): array
    {
        if (! $user->hasRole('Admin')) {
            throw new AuthorizationException(
                'Hanya user dengan role Admin yang dapat membuka kunci data.'
            );
        }

        return DB::transaction(function () use ($id) {
            $result = $this->repository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data generate Casemix tidak ditemukan.');
            }

            if (! $result->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Data tidak sedang terkunci.',
                ]);
            }

            return $this->lockPayload(
                $this->repository->updateLock($result, false)
            );
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $result = $this->repository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data generate Casemix tidak ditemukan.');
            }

            if ($result->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Data Casemix yang sudah terkunci tidak dapat dihapus.',
                ]);
            }

            $this->repository->deleteResult($result);
        });
    }

    private function calculate(array $data, bool $strict = false): array
    {
        $config = $this->configPayload($this->repository->getConfig());
        $answers = $data['answers'] ?? [];
        $questionnaire = $this->questionnairePayload($config['questions'], $answers, $strict);
        $scorePercent = $questionnaire['score']['score_percent'];
        $rewardPercent = $this->rewardPercent($scorePercent, $config);
        $verifikasi = max(0, (int) ($data['verifikasi_hasil_bpjs'] ?? 0));
        $totalReward = $this->portion($verifikasi, $rewardPercent);
        $teamPool = $this->portion($totalReward, $config['team_pool_percent']);
        $leaderPool = $this->portion($teamPool, $config['leader_percent']);
        $kanitPool = $this->portion($teamPool, $config['kanit_percent']);
        $inputerPool = $this->portion($teamPool, $config['inputer_percent']);
        $inputerDivider = max(1, (int) ($config['inputer_divider'] ?? 4));
        $rolePools = [
            'leader' => $leaderPool,
            'kanit' => $kanitPool,
            'inputer' => $inputerPool,
        ];
        $recipients = $this->recipientDetails($rolePools, $config, $strict);
        $input = [
            'periode' => $data['periode'],
            'biaya_rs' => max(0, (int) ($data['biaya_rs'] ?? 0)),
            'tarif_bpjs' => max(0, (int) ($data['tarif_bpjs'] ?? 0)),
            'verifikasi_hasil_bpjs' => $verifikasi,
        ];
        $kerugianAwal = $input['tarif_bpjs'] > 0
            ? round((($input['tarif_bpjs'] - $input['biaya_rs']) / $input['tarif_bpjs']) * 100, 4)
            : 0.0;
        $pools = [
            'total_reward' => $totalReward,
            'team_pool_percent' => $config['team_pool_percent'],
            'team_pool' => $teamPool,
            'non_team_pool' => max(0, $totalReward - $teamPool),
            'leader_total' => $leaderPool,
            'kanit_total' => $kanitPool,
            'inputer_total' => $inputerPool,
            'inputer_divider' => $inputerDivider,
            'inputer_per_orang' => (int) round($inputerPool / $inputerDivider),
        ];
        $configSnapshot = [
            ...$config,
            'pools' => $pools,
            'role_pools' => $rolePools,
        ];

        return [
            'periode' => $data['periode'],
            'input' => $input,
            'loss_summary' => $this->lossSummary(
                $input['biaya_rs'],
                $input['tarif_bpjs'],
                $input['verifikasi_hasil_bpjs']
            ),
            'kerugian_awal_percent' => $kerugianAwal,
            'score' => $questionnaire['score'],
            'reward_percent' => $rewardPercent,
            'pools' => $pools,
            'questions' => $questionnaire['items'],
            'recipients' => $recipients,
            'details' => [
                ...$questionnaire['items'],
                ...$recipients,
            ],
            'total_dibagikan' => collect($recipients)->sum('total_received'),
            'config' => $configSnapshot,
            'config_snapshot' => $configSnapshot,
            'can_generate' => true,
        ];
    }

    private function questionnairePayload(array $questions, array $answers, bool $strict): array
    {
        $items = [];
        $totalScore = 0;
        $maxScore = 0;

        foreach ($questions as $question) {
            $answerKey = $answers[$question['key']] ?? null;
            $options = collect($question['options']);
            $option = $options->firstWhere('key', $answerKey);
            $questionMaxScore = (int) $options->max('score');

            if ($strict && ! $option) {
                throw ValidationException::withMessages([
                    "answers.{$question['key']}" => 'Semua indikator penilaian Casemix wajib dijawab.',
                ]);
            }

            $score = (int) ($option['score'] ?? 0);
            $totalScore += $score;
            $maxScore += $questionMaxScore;
            $items[] = [
                'row_type' => 'questionnaire',
                'question_key' => $question['key'],
                'question_label' => $question['label'],
                'answer_key' => $option['key'] ?? null,
                'answer_label' => $option['label'] ?? null,
                'score' => $score,
                'max_score' => $questionMaxScore,
                'role' => null,
                'role_label' => null,
                'pegawai_id' => null,
                'pegawai_name' => null,
                'pegawai_position' => null,
                'allocation_percent' => null,
                'pool_total' => 0,
                'amount_per_recipient' => 0,
                'total_received' => 0,
            ];
        }

        return [
            'items' => $items,
            'score' => [
                'total_score' => $totalScore,
                'max_score' => $maxScore,
                'score_percent' => $maxScore > 0 ? round($totalScore * 100 / $maxScore, 2) : 0.0,
            ],
        ];
    }

    private function recipientDetails(array $rolePools, array $config, bool $strict): array
    {
        $rows = [];

        foreach (self::ROLE_LABELS as $role => $roleLabel) {
            $items = $config['recipients'][$role] ?? [];
            $pool = (int) ($rolePools[$role] ?? 0);

            if ($strict && $pool > 0 && empty($items)) {
                throw ValidationException::withMessages([
                    "recipients.{$role}" => $roleLabel.' wajib dipilih sebelum generate.',
                ]);
            }

            if (empty($items)) {
                continue;
            }

            $divider = $role === 'inputer'
                ? max(1, (int) ($config['inputer_divider'] ?? 4))
                : max(1, count($items));

            $amount = (int) round($pool / $divider);
            foreach ($items as $item) {
                $rows[] = [
                    'row_type' => 'recipient',
                    'question_key' => null,
                    'question_label' => null,
                    'answer_key' => null,
                    'answer_label' => null,
                    'score' => null,
                    'max_score' => null,
                    'role' => $role,
                    'role_label' => $roleLabel,
                    'pegawai_id' => $item['pegawai_id'],
                    'pegawai_name' => $item['pegawai_name'],
                    'pegawai_position' => $item['pegawai_position'] ?? null,
                    'allocation_percent' => (float) ($config[$role.'_percent'] ?? 0),
                    'pool_total' => $pool,
                    'amount_per_recipient' => $amount,
                    'total_received' => $amount,
                ];
            }
        }

        return $rows;
    }

    private function rewardPercent(float $scorePercent, array $config): float
    {
        if ($scorePercent > $config['excellent_min_percent']) {
            return (float) $config['excellent_reward_percent'];
        }

        if ($scorePercent >= $config['good_min_percent']) {
            return (float) $config['good_reward_percent'];
        }

        return (float) $config['low_reward_percent'];
    }

    private function portion(int $amount, float $percent): int
    {
        return (int) round($amount * $percent / 100);
    }

    private function hydrateRecipients(array $input): array
    {
        $result = [
            'leader' => [],
            'kanit' => [],
            'inputer' => [],
        ];

        foreach (array_keys(self::ROLE_LABELS) as $role) {
            $ids = collect($input[$role] ?? [])
                ->filter()
                ->map(fn ($id) => trim((string) $id))
                ->unique()
                ->values();

            $result[$role] = $ids
                ->map(function ($id) use ($role) {
                    $row = $this->repository->findPegawai($id);

                    if (! $row) {
                        throw ValidationException::withMessages([
                            "recipients.{$role}" => self::ROLE_LABELS[$role].' tidak valid.',
                        ]);
                    }

                    return [
                        'pegawai_id' => $row->nik,
                        'pegawai_name' => $row->nama,
                        'pegawai_position' => $row->jbtn,
                    ];
                })
                ->all();
        }

        return $result;
    }

    private function configFormPayload(array $data): array
    {
        $roleTotal = (float) ($data['leader_percent'] ?? 68)
            + (float) ($data['kanit_percent'] ?? 12)
            + (float) ($data['inputer_percent'] ?? 20);

        if (round($roleTotal, 4) !== 100.0) {
            throw ValidationException::withMessages([
                'inputer_percent' => 'Total persentase Ketua, Kanit/Coding, dan Inputer wajib 100%.',
            ]);
        }

        return [
            'excellent_min_percent' => (float) ($data['excellent_min_percent'] ?? 90),
            'excellent_reward_percent' => (float) ($data['excellent_reward_percent'] ?? 1.25),
            'good_min_percent' => (float) ($data['good_min_percent'] ?? 80),
            'good_reward_percent' => (float) ($data['good_reward_percent'] ?? 1),
            'low_reward_percent' => (float) ($data['low_reward_percent'] ?? 0.75),
            'team_pool_percent' => (float) ($data['team_pool_percent'] ?? 70),
            'leader_percent' => (float) ($data['leader_percent'] ?? 68),
            'kanit_percent' => (float) ($data['kanit_percent'] ?? 12),
            'inputer_percent' => (float) ($data['inputer_percent'] ?? 20),
            'inputer_divider' => max(1, (int) ($data['inputer_divider'] ?? 4)),
            'question_config' => $this->questionConfigPayload($data['questions'] ?? []),
        ];
    }

    private function questionConfigPayload(array $input): array
    {
        $questions = $this->defaultQuestions();

        return collect($questions)
            ->map(function ($question) use ($input) {
                foreach ($question['options'] as &$option) {
                    $label = data_get($input, $question['key'].'.'.$option['key'].'.label', $option['label']);
                    $score = data_get($input, $question['key'].'.'.$option['key'].'.score', $option['score']);
                    $option['label'] = trim((string) $label) ?: $option['label'];
                    $option['score'] = max(0, (int) $score);
                }

                return $question;
            })
            ->values()
            ->all();
    }

    private function configPayload($config): array
    {
        $questions = $this->mergeQuestions($config->question_config ?? null);
        $recipients = [
            'leader' => [],
            'kanit' => [],
            'inputer' => [],
        ];

        if ($config->relationLoaded('pegawai')) {
            foreach ($config->pegawai as $pegawai) {
                if (! isset($recipients[$pegawai->role])) {
                    continue;
                }

                $recipients[$pegawai->role][] = [
                    'pegawai_id' => $pegawai->pegawai_id,
                    'pegawai_name' => $pegawai->pegawai_name,
                    'pegawai_position' => $pegawai->pegawai_position,
                    'text' => trim($pegawai->pegawai_id.' - '.$pegawai->pegawai_name),
                ];
            }
        }

        return [
            'id' => $config->id,
            'excellent_min_percent' => (float) $config->excellent_min_percent,
            'excellent_reward_percent' => (float) $config->excellent_reward_percent,
            'good_min_percent' => (float) $config->good_min_percent,
            'good_reward_percent' => (float) $config->good_reward_percent,
            'low_reward_percent' => (float) $config->low_reward_percent,
            'team_pool_percent' => (float) $config->team_pool_percent,
            'leader_percent' => (float) $config->leader_percent,
            'kanit_percent' => (float) $config->kanit_percent,
            'inputer_percent' => (float) $config->inputer_percent,
            'inputer_divider' => max(1, (int) ($config->inputer_divider ?: 4)),
            'questions' => $questions,
            'recipients' => $recipients,
            'role_labels' => self::ROLE_LABELS,
        ];
    }

    private function resultPayload($row): array
    {
        $configSnapshot = $row->config_snapshot ?: [];
        $inputerDivider = max(1, (int) (data_get($configSnapshot, 'pools.inputer_divider')
            ?? data_get($configSnapshot, 'inputer_divider')
            ?? 1));

        return [
            'id' => $row->id,
            'periode' => $row->periode,
            'biaya_rs' => $row->biaya_rs,
            'tarif_bpjs' => $row->tarif_bpjs,
            'verifikasi_hasil_bpjs' => $row->verifikasi_hasil_bpjs,
            'loss_summary' => $this->lossSummary(
                $row->biaya_rs,
                $row->tarif_bpjs,
                $row->verifikasi_hasil_bpjs
            ),
            'kerugian_awal_percent' => $row->kerugian_awal_percent,
            'total_score' => $row->total_score,
            'max_score' => $row->max_score,
            'score_percent' => $row->score_percent,
            'reward_percent' => $row->reward_percent,
            'total_reward' => $row->total_reward,
            'team_pool_percent' => $row->team_pool_percent,
            'team_pool' => $row->team_pool,
            'non_team_pool' => $row->non_team_pool,
            'leader_total' => $row->leader_total,
            'kanit_total' => $row->kanit_total,
            'inputer_total' => $row->inputer_total,
            'inputer_divider' => $inputerDivider,
            'inputer_per_orang' => (int) round($row->inputer_total / $inputerDivider),
            'total_dibagikan' => $row->total_dibagikan,
            'details_count' => $row->details_count ?? $row->details?->count() ?? 0,
            'config_snapshot' => $configSnapshot,
            'details' => $row->relationLoaded('details')
                ? $row->details->map(fn ($detail) => $this->detailPayload($detail))->values()
                : [],
            'is_locked' => $row->is_locked,
            'locked_at' => optional($row->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $row->lockedBy?->name,
            'generate_by_name' => $row->generateBy?->name,
            'generated_at' => optional($row->updated_at)->format('d-m-Y H:i'),
        ];
    }

    private function detailPayload($detail): array
    {
        return [
            'row_type' => $detail->row_type,
            'question_key' => $detail->question_key,
            'question_label' => $detail->question_label,
            'answer_key' => $detail->answer_key,
            'answer_label' => $detail->answer_label,
            'score' => $detail->score,
            'max_score' => $detail->max_score,
            'role' => $detail->role,
            'role_label' => $detail->role_label,
            'pegawai_id' => $detail->pegawai_id,
            'pegawai_name' => $detail->pegawai_name,
            'pegawai_position' => $detail->pegawai_position,
            'allocation_percent' => $detail->allocation_percent,
            'pool_total' => $detail->pool_total,
            'amount_per_recipient' => $detail->amount_per_recipient,
            'total_received' => $detail->total_received,
        ];
    }

    private function lossSummary(int $biayaRs, int $tarifBpjs, int $verifikasi): array
    {
        $selisihPengajuan = $tarifBpjs - $verifikasi;
        $marginCair = $verifikasi - $biayaRs;

        return [
            'biaya_rs' => $biayaRs,
            'jumlah_pengajuan' => $tarifBpjs,
            'yang_cair' => $verifikasi,
            'selisih_pengajuan_cair' => $selisihPengajuan,
            'selisih_pengajuan_cair_abs' => abs($selisihPengajuan),
            'rasio_cair_percent' => $tarifBpjs > 0 ? round($verifikasi * 100 / $tarifBpjs, 2) : 0.0,
            'margin_cair_biaya_rs' => $marginCair,
            'margin_cair_biaya_rs_abs' => abs($marginCair),
            'margin_cair_biaya_rs_percent' => $verifikasi > 0 ? round($marginCair * 100 / $verifikasi, 2) : 0.0,
        ];
    }

    private function lockPayload($result): array
    {
        return [
            'id' => $result->id,
            'is_locked' => $result->is_locked,
            'locked_at' => optional($result->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $result->lockedBy?->name,
        ];
    }

    private function mergeQuestions(?array $configured): array
    {
        if (! $configured) {
            return $this->defaultQuestions();
        }

        $configuredByKey = collect($configured)->keyBy('key');

        return collect($this->defaultQuestions())
            ->map(function ($question) use ($configuredByKey) {
                $stored = $configuredByKey->get($question['key']);
                $storedOptions = collect($stored['options'] ?? [])->keyBy('key');

                $question['options'] = collect($question['options'])
                    ->map(function ($option) use ($storedOptions) {
                        $stored = $storedOptions->get($option['key']);
                        $option['label'] = trim((string) ($stored['label'] ?? $option['label'])) ?: $option['label'];
                        $option['score'] = (int) ($stored['score'] ?? $option['score']);

                        return $option;
                    })
                    ->values()
                    ->all();

                return $question;
            })
            ->values()
            ->all();
    }

    private function defaultQuestions(): array
    {
        return self::DEFAULT_QUESTIONS;
    }
}
