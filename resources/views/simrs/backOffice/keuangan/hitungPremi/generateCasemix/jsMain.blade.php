<script>
    $(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const roleConfig = {
            leader: {
                label: 'Ketua Tim Casemix',
                select: '#configCasemixLeader',
                icon: 'mdi-account-star-outline'
            },
            kanit: {
                label: 'Kanit / Coding',
                select: '#configCasemixKanit',
                icon: 'mdi-account-tie-outline'
            },
            inputer: {
                label: 'Inputer',
                select: '#configCasemixInputer',
                icon: 'mdi-keyboard-outline'
            }
        };
        let casemixConfig = null;

        const generateModal = modalInstance('modalGenerateCasemix');
        const configModal = modalInstance('modalConfigCasemix');
        const detailModal = modalInstance('modalDetailCasemix');

        function modalInstance(id) {
            const el = document.getElementById(id);
            if (window.bootstrap && bootstrap.Modal && el) {
                return bootstrap.Modal.getOrCreateInstance ?
                    bootstrap.Modal.getOrCreateInstance(el) :
                    new bootstrap.Modal(el);
            }

            return {
                show: () => $('#' + id).modal('show'),
                hide: () => $('#' + id).modal('hide')
            };
        }

        function formatNumber(value) {
            return new Intl.NumberFormat('id-ID').format(Number(value) || 0);
        }

        function formatPercent(value) {
            return formatNumber(Number(value) || 0) + '%';
        }

        function formatRupiah(value) {
            return 'Rp ' + formatNumber(value);
        }

        function formatSignedRupiah(value) {
            const amount = Number(value || 0);
            const sign = amount < 0 ? '- ' : amount > 0 ? '+ ' : '';

            return sign + formatRupiah(Math.abs(amount));
        }

        function escapeHtml(value) {
            return $('<div>').text(value == null ? '' : value).html();
        }

        function flattenErrors(errors) {
            const messages = [];
            Object.keys(errors || {}).forEach(function(key) {
                (Array.isArray(errors[key]) ? errors[key] : [errors[key]]).forEach(function(message) {
                    messages.push(message);
                });
            });
            return messages;
        }

        function errorMessage(xhr) {
            const response = xhr.responseJSON || {};
            return response.errors ? flattenErrors(response.errors).join('<br>') :
                (response.message || 'Terjadi kesalahan saat memproses data.');
        }

        function numberInput(selector) {
            return Number($(selector).val() || 0);
        }

        function setDefaultPeriod() {
            const now = new Date();
            $('#periodeCasemix').val(now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'));
        }

        function periodFromInput() {
            const parts = String($('#periodeCasemix').val() || '').split('-').map(Number);
            return parts.length === 2 && parts[0] && parts[1] ?
                new Date(parts[0], parts[1] - 1, 1) :
                new Date();
        }

        function setPeriodFromDate(date) {
            $('#periodeCasemix')
                .val(date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0'))
                .trigger('change');
        }

        function movePeriod(offset) {
            const date = periodFromInput();
            date.setMonth(date.getMonth() + offset);
            setPeriodFromDate(date);
        }

        function portion(amount, percent) {
            return Math.round(Number(amount || 0) * Number(percent || 0) / 100);
        }

        function currentAnswers() {
            const answers = {};
            (casemixConfig?.questions || []).forEach(function(question) {
                answers[question.key] = $('input[name="casemix_answer_' + question.key + '"]:checked').val() || '';
            });
            return answers;
        }

        function scoreAnswers(answers) {
            let totalScore = 0;
            let maxScore = 0;
            const items = (casemixConfig?.questions || []).map(function(question) {
                const options = question.options || [];
                const option = options.find(item => item.key === answers[question.key]);
                const questionMax = Math.max(...options.map(item => Number(item.score || 0)), 0);
                const score = Number(option?.score || 0);
                totalScore += score;
                maxScore += questionMax;

                return {
                    question: question,
                    option: option,
                    score: score,
                    max_score: questionMax
                };
            });

            return {
                items: items,
                total_score: totalScore,
                max_score: maxScore,
                score_percent: maxScore > 0 ? Math.round(totalScore * 10000 / maxScore) / 100 : 0
            };
        }

        function rewardPercent(scorePercent) {
            if (scorePercent > Number(casemixConfig?.excellent_min_percent || 90)) {
                return Number(casemixConfig?.excellent_reward_percent || 1.25);
            }

            if (scorePercent >= Number(casemixConfig?.good_min_percent || 80)) {
                return Number(casemixConfig?.good_reward_percent || 1);
            }

            return Number(casemixConfig?.low_reward_percent || .75);
        }

        function rewardTier(scorePercent) {
            if (scorePercent > Number(casemixConfig?.excellent_min_percent || 90)) {
                return {
                    label: 'Excellent',
                    tone: 'excellent',
                    note: 'Melewati batas score tertinggi'
                };
            }

            if (scorePercent >= Number(casemixConfig?.good_min_percent || 80)) {
                return {
                    label: 'Good',
                    tone: 'good',
                    note: 'Masuk batas score menengah'
                };
            }

            return {
                label: 'Low',
                tone: 'low',
                note: 'Di bawah batas score menengah'
            };
        }

        function lossSummary(biayaRs, pengajuan, cair, kerugianAwalPercent) {
            const selisihPengajuan = Number(pengajuan || 0) - Number(cair || 0);
            const marginCair = Number(cair || 0) - Number(biayaRs || 0);

            return {
                biaya_rs: Number(biayaRs || 0),
                jumlah_pengajuan: Number(pengajuan || 0),
                yang_cair: Number(cair || 0),
                selisih_pengajuan_cair: selisihPengajuan,
                selisih_pengajuan_cair_abs: Math.abs(selisihPengajuan),
                rasio_cair_percent: Number(pengajuan || 0) > 0 ?
                    Math.round(Number(cair || 0) * 10000 / Number(pengajuan || 0)) / 100 : 0,
                margin_cair_biaya_rs: marginCair,
                margin_cair_biaya_rs_abs: Math.abs(marginCair),
                margin_cair_biaya_rs_percent: Number(cair || 0) > 0 ?
                    Math.round(marginCair * 10000 / Number(cair || 0)) / 100 : 0,
                kerugian_awal_percent: Number(kerugianAwalPercent || 0)
            };
        }

        function calculatePreview() {
            const answers = currentAnswers();
            const score = scoreAnswers(answers);
            const verifikasi = numberInput('#generateVerifikasiBpjsCasemix');
            const reward = rewardPercent(score.score_percent);
            const totalReward = portion(verifikasi, reward);
            const teamPool = portion(totalReward, casemixConfig?.team_pool_percent || 70);
            const biayaRs = numberInput('#generateBiayaRsCasemix');
            const tarifBpjs = numberInput('#generateTarifBpjsCasemix');
            const lossPercent = tarifBpjs > 0 ? Math.round(((tarifBpjs - biayaRs) / tarifBpjs) * 10000) / 100 : 0;
            const rolePools = {
                leader: portion(teamPool, casemixConfig?.leader_percent || 68),
                kanit: portion(teamPool, casemixConfig?.kanit_percent || 12),
                inputer: portion(teamPool, casemixConfig?.inputer_percent || 20)
            };
            const loss = lossSummary(biayaRs, tarifBpjs, verifikasi, lossPercent);

            return {
                answers: answers,
                score: score,
                ...loss,
                reward_percent: reward,
                total_reward: totalReward,
                team_pool: teamPool,
                non_team_pool: Math.max(0, totalReward - teamPool),
                loss_percent: lossPercent,
                role_pools: rolePools
            };
        }

        function miniCard(label, value, primary, icon, note) {
            return '<div class="casemix-mini-card ' + (primary ? 'total' : '') + '">' +
                '<div class="d-flex align-items-center justify-content-between gap-2">' +
                '<div class="casemix-mini-label">' + escapeHtml(label) + '</div>' +
                '<i class="mdi ' + escapeHtml(icon || 'mdi-chart-box-outline') + '"></i>' +
                '</div>' +
                '<div class="casemix-mini-value">' + escapeHtml(value) + '</div>' +
                (note ? '<div class="casemix-panel-subtitle">' + escapeHtml(note) + '</div>' : '') +
                '</div>';
        }

        function moneyState(value, positiveIsGood) {
            const amount = Number(value || 0);
            if (amount === 0) {
                return 'neutral';
            }

            return (amount > 0) === positiveIsGood ? 'good' : 'bad';
        }

        function lossInsightHtml(loss) {
            loss = loss || lossSummary(0, 0, 0, 0);
            const selisihTone = moneyState(loss.selisih_pengajuan_cair, false);
            const marginTone = moneyState(loss.margin_cair_biaya_rs, true);
            const selisihLabel = Number(loss.selisih_pengajuan_cair || 0) >= 0 ?
                'Belum cair dari pengajuan' :
                'Cair melebihi pengajuan';
            const marginLabel = Number(loss.margin_cair_biaya_rs || 0) >= 0 ?
                'Surplus cair terhadap biaya RS' :
                'Kerugian cair terhadap biaya RS';

            return '<div class="casemix-loss-head">' +
                '<div>' +
                '<div class="casemix-loss-title"><i class="mdi mdi-scale-balance"></i> Analisis Pengajuan dan Cair</div>' +
                '<div class="casemix-simple-section-subtitle">Membandingkan jumlah pengajuan BPJS, nominal yang cair, dan biaya RS.</div>' +
                '</div>' +
                '<span class="casemix-loss-ratio">' + escapeHtml(formatPercent(loss.rasio_cair_percent || 0)) + ' cair</span>' +
                '</div>' +
                '<div class="casemix-loss-grid">' +
                lossItem('Jumlah Pengajuan', formatRupiah(loss.jumlah_pengajuan || 0), 'mdi-file-send-outline') +
                lossItem('Yang Cair', formatRupiah(loss.yang_cair || 0), 'mdi-cash-check', 'good') +
                lossItem(selisihLabel, formatSignedRupiah(loss.selisih_pengajuan_cair || 0), 'mdi-chart-timeline-variant', selisihTone) +
                lossItem(marginLabel, formatSignedRupiah(loss.margin_cair_biaya_rs || 0), 'mdi-hospital-building', marginTone) +
                '</div>';
        }

        function lossItem(label, value, icon, state) {
            return '<div class="casemix-loss-item ' + escapeHtml(state || 'neutral') + '">' +
                '<i class="mdi ' + escapeHtml(icon || 'mdi-information-outline') + '"></i>' +
                '<div>' +
                '<span>' + escapeHtml(label) + '</span>' +
                '<strong>' + escapeHtml(value) + '</strong>' +
                '</div>' +
                '</div>';
        }

        function scoreMeterHtml(preview, answered, questionCount) {
            const tier = rewardTier(preview.score.score_percent);
            const percent = Math.max(0, Math.min(100, Number(preview.score.score_percent || 0)));

            return '<div class="casemix-meter">' +
                '<div class="casemix-meter-head">' +
                '<span>' + escapeHtml(answered + ' dari ' + questionCount + ' indikator terjawab') + '</span>' +
                '<strong>' + escapeHtml(formatPercent(preview.score.score_percent || 0)) + '</strong>' +
                '</div>' +
                '<div class="casemix-meter-track">' +
                '<div class="casemix-meter-fill ' + escapeHtml(tier.tone) + '" style="width:' + percent + '%"></div>' +
                '</div>' +
                '<div class="casemix-meter-foot">' +
                '<span>' + escapeHtml(tier.label + ' / ' + tier.note) + '</span>' +
                '<span>' + escapeHtml('Reward ' + formatPercent(preview.reward_percent || 0)) + '</span>' +
                '</div>' +
                '</div>';
        }

        function formulaHtml(config) {
            config = config || casemixConfig || {};
            return '<div class="casemix-formula-head">' +
                '<div>' +
                '<div class="casemix-formula-title">Formula Aktif Casemix</div>' +
                '<div class="casemix-panel-subtitle">' +
                'Score > ' + formatPercent(config.excellent_min_percent) + ' mendapat ' +
                formatPercent(config.excellent_reward_percent) + ', score ' +
                formatPercent(config.good_min_percent) + ' sampai ' +
                formatPercent(config.excellent_min_percent) + ' mendapat ' +
                formatPercent(config.good_reward_percent) + ', sisanya ' +
                formatPercent(config.low_reward_percent) + '.' +
                '</div>' +
                '</div>' +
                '<span class="casemix-formula-badge"><i class="mdi mdi-account-cash-outline"></i> ' +
                escapeHtml(formatPercent(config.team_pool_percent || 70) + ' untuk tim') +
                '</span>' +
                '</div>' +
                '<div class="casemix-formula-grid">' +
                formulaCard('Reward Casemix', 'Verifikasi BPJS x reward score', 'mdi-medal-outline') +
                formulaCard('Pool Tim', 'Reward Casemix x ' + formatPercent(config.team_pool_percent || 70), 'mdi-account-group-outline') +
                formulaCard('Pembagian Tim', 'Ketua ' + formatPercent(config.leader_percent || 68) +
                    ' / Kanit ' + formatPercent(config.kanit_percent || 12) +
                    ' / Inputer ' + formatPercent(config.inputer_percent || 20), 'mdi-vector-arrange-below') +
                '</div>';
        }

        function formulaCard(title, value, icon) {
            return '<div class="casemix-formula-card">' +
                '<i class="mdi ' + escapeHtml(icon) + '"></i>' +
                '<span>' + escapeHtml(title) + '</span>' +
                '<strong>' + escapeHtml(value) + '</strong>' +
                '</div>';
        }

        function questionInputsHtml(prefix, questions, answers) {
            return (questions || []).map(function(question) {
                const name = prefix + '_' + question.key;
                return '<div class="casemix-question-card">' +
                    '<div class="casemix-question-title">' + escapeHtml(question.label) + '</div>' +
                    '<div class="casemix-answer-grid">' +
                    (question.options || []).map(function(option) {
                        const checked = answers && answers[question.key] === option.key ? ' checked' : '';
                        return '<label class="casemix-answer-option">' +
                            '<input type="radio" class="form-check-input" name="' + escapeHtml(name) + '" value="' + escapeHtml(option.key) + '"' + checked + '>' +
                            '<span><strong>' + escapeHtml(option.label) + '</strong>' +
                            '<small>Nilai ' + formatNumber(option.score || 0) + '</small></span>' +
                            '</label>';
                    }).join('') +
                    '</div>' +
                    '</div>';
            }).join('');
        }

        function configQuestionScoresHtml(questions) {
            return (questions || []).map(function(question) {
                return '<div class="casemix-question-card">' +
                    '<div class="casemix-question-title">' + escapeHtml(question.label) + '</div>' +
                    '<div class="casemix-config-answer-list">' +
                    (question.options || []).map(function(option) {
                        return '<div class="casemix-config-answer-row" data-question="' + escapeHtml(question.key) + '" data-option="' + escapeHtml(option.key) + '">' +
                            '<div class="casemix-config-answer-icon"><i class="mdi mdi-format-list-checks"></i></div>' +
                            '<div>' +
                            '<label class="form-label">Pilihan Jawaban</label>' +
                            '<input type="text" maxlength="120" class="form-control config-question-label" value="' + escapeHtml(option.label || '') + '">' +
                            '</div>' +
                            '<div>' +
                            '<label class="form-label">Nilai</label>' +
                            '<input type="number" min="0" max="100" class="form-control config-question-score" value="' + escapeHtml(option.score || 0) + '">' +
                            '</div>' +
                            '</div>';
                    }).join('') +
                    '</div>' +
                    '</div>';
            }).join('');
        }

        function recipientListHtml(items, showAmount) {
            if (!items || !items.length) {
                return '<div class="casemix-empty-state">Belum ada pegawai dipilih.</div>';
            }

            return items.map(function(item) {
                const name = item.pegawai_name || item.text || '-';
                return '<div class="casemix-recipient-item">' +
                    '<div>' +
                    '<div class="casemix-recipient-name">' + escapeHtml(name) + '</div>' +
                    '<div class="casemix-recipient-meta">' +
                    escapeHtml(item.pegawai_id || '-') + ' / ' + escapeHtml(item.pegawai_position || '-') +
                    (item.role_label ? ' / ' + escapeHtml(item.role_label) : '') +
                    '</div>' +
                    '</div>' +
                    '<div class="casemix-recipient-total">' +
                    (showAmount ? formatRupiah(item.total_received || 0) : '<span class="casemix-grade">Dipilih</span>') +
                    '</div>' +
                    '</div>';
            }).join('');
        }

        function recipientGroupsHtml(groups) {
            if (!groups || !groups.length) {
                return '<div class="casemix-empty-state">Belum ada pembagian penerima.</div>';
            }

            return groups.map(function(group) {
                return '<div class="casemix-simple-section">' +
                    '<div class="casemix-simple-section-title">' + escapeHtml(group.role_label || '-') + '</div>' +
                    '<div class="casemix-simple-section-subtitle mb-2">' +
                    formatPercent(group.allocation_percent || 0) + ' / ' +
                    formatNumber(group.recipient_count || 0) + ' penerima / ' +
                    formatRupiah(group.amount_per_recipient || 0) + ' per penerima / total ' +
                    formatRupiah(group.total_received || 0) +
                    '</div>' +
                    recipientListHtml(group.items || [], true) +
                    '</div>';
            }).join('');
        }

        function updateGeneratePreview() {
            if (!casemixConfig) {
                return;
            }

            const preview = calculatePreview();
            const answered = Object.values(preview.answers).filter(Boolean).length;
            const questionCount = (casemixConfig.questions || []).length;
            const tier = rewardTier(preview.score.score_percent);

            $('#generateCasemixLossInsight').html(lossInsightHtml(preview));
            $('#generateCasemixScoreMeter').html(scoreMeterHtml(preview, answered, questionCount));
            $('#generateCasemixQuestionProgress').text(answered + '/' + questionCount + ' terjawab');
            $('#generateCasemixTierBadge')
                .removeClass('casemix-tier-excellent casemix-tier-good casemix-tier-low')
                .addClass('casemix-tier-' + tier.tone)
                .text(tier.label + ' / ' + formatPercent(preview.reward_percent));
            $('#generateCasemixPreviewCards').html([
                miniCard('Kerugian Awal', formatPercent(preview.loss_percent), false, 'mdi-chart-line-variant', '(Pengajuan - Biaya RS) / Pengajuan'),
                miniCard('Score', formatPercent(preview.score.score_percent), true, 'mdi-star-check-outline', preview.score.total_score + ' dari ' + preview.score.max_score + ' nilai'),
                miniCard('Reward Casemix', formatRupiah(preview.total_reward), false, 'mdi-medal-outline', formatPercent(preview.reward_percent)),
                miniCard('Pool Tim', formatRupiah(preview.team_pool), true, 'mdi-account-group-outline', formatPercent(casemixConfig.team_pool_percent || 70))
            ].join(''));
            $('#generateCasemixFormulaPreview').html(formulaHtml(casemixConfig));
            $('#generateCasemixText').text(
                answered + ' dari ' + questionCount + ' indikator terjawab / cair ' +
                formatRupiah(preview.yang_cair) + ' / pool tim ' + formatRupiah(preview.team_pool) + '.'
            );

            const recipientPreview = Object.entries(roleConfig).map(function([role, roleInfo]) {
                const pool = preview.role_pools[role] || 0;
                const count = (casemixConfig.recipients?.[role] || []).length;
                const amount = count > 0 ? Math.round(pool / count) : 0;
                return {
                    role_label: roleInfo.label,
                    recipient_count: count,
                    allocation_percent: casemixConfig[role + '_percent'] || 0,
                    pool_total: pool,
                    amount_per_recipient: amount,
                    total_received: amount * count,
                    items: (casemixConfig.recipients?.[role] || []).map(item => ({
                        ...item,
                        role_label: roleInfo.label,
                        total_received: amount
                    }))
                };
            });
            $('#generateCasemixRecipientPreview').html(recipientGroupsHtml(recipientPreview));
        }

        function selectedItemsFromSelect(selector, role) {
            return $(selector + ' option:selected').map(function() {
                const text = $(this).text();
                const parts = text.split(' - ');

                return {
                    role: role,
                    role_label: roleConfig[role] ? roleConfig[role].label : '',
                    pegawai_id: this.value,
                    pegawai_name: parts.length > 1 ? parts.slice(1).join(' - ') : text,
                    pegawai_position: 'Penerima aktif'
                };
            }).get();
        }

        function fillSelect(selector, items) {
            const select = $(selector);
            select.empty();
            (items || []).forEach(function(item) {
                const value = item.pegawai_id || item.id;
                const text = item.text || (value + ' - ' + (item.pegawai_name || item.nama || '-'));
                select.append(new Option(text, value, true, true));
            });
            select.trigger('change');
        }

        function initSelect2() {
            if (!$.fn.select2) {
                return;
            }

            Object.values(roleConfig).forEach(function(role) {
                $(role.select).select2({
                    dropdownParent: $('#modalConfigCasemix'),
                    width: '100%',
                    placeholder: 'Pilih pegawai',
                    ajax: {
                        url: "{{ route("backOffice.keuangan.hitungPremi.generateCasemix.pegawaiOptions") }}",
                        dataType: 'json',
                        delay: 250,
                        data: params => ({ q: params.term || '' }),
                        processResults: response => ({
                            results: (response.data || []).map(item => ({ id: item.id, text: item.text }))
                        })
                    }
                });
            });
        }

        function configPayload() {
            const questions = {};
            $('.casemix-config-answer-row').each(function() {
                const row = $(this);
                const question = row.data('question');
                const option = row.data('option');
                if (!questions[question]) {
                    questions[question] = {};
                }
                questions[question][option] = {
                    label: row.find('.config-question-label').val(),
                    score: row.find('.config-question-score').val()
                };
            });

            return {
                excellent_min_percent: $('#configExcellentMinCasemix').val(),
                excellent_reward_percent: $('#configExcellentRewardCasemix').val(),
                good_min_percent: $('#configGoodMinCasemix').val(),
                good_reward_percent: $('#configGoodRewardCasemix').val(),
                low_reward_percent: $('#configLowRewardCasemix').val(),
                team_pool_percent: $('#configTeamPoolCasemix').val(),
                leader_percent: $('#configLeaderPercentCasemix').val(),
                kanit_percent: $('#configKanitPercentCasemix').val(),
                inputer_percent: $('#configInputerPercentCasemix').val(),
                questions: questions,
                recipients: {
                    leader: $('#configCasemixLeader').val() || [],
                    kanit: $('#configCasemixKanit').val() || [],
                    inputer: $('#configCasemixInputer').val() || []
                }
            };
        }

        function generatePayload() {
            return {
                periode: $('#periodeCasemix').val(),
                biaya_rs: $('#generateBiayaRsCasemix').val(),
                tarif_bpjs: $('#generateTarifBpjsCasemix').val(),
                verifikasi_hasil_bpjs: $('#generateVerifikasiBpjsCasemix').val(),
                answers: currentAnswers()
            };
        }

        function updateConfigRoleTotal() {
            const total = numberInput('#configLeaderPercentCasemix') +
                numberInput('#configKanitPercentCasemix') +
                numberInput('#configInputerPercentCasemix');
            $('#configCasemixRoleTotal').text('Total role: ' + formatPercent(total) + ' / wajib 100%.');
        }

        function loadConfig(callback) {
            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateCasemix.config") }}",
                success: function(response) {
                    casemixConfig = response.data || {};
                    $('#formulaCasemixStrip').html(formulaHtml(casemixConfig));
                    if (callback) {
                        callback(casemixConfig);
                    }
                },
                error: function(xhr) {
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                }
            });
        }

        function loadSummary() {
            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateCasemix.summary") }}",
                data: { periode: $('#periodeCasemix').val() },
                success: function(response) {
                    const data = response.data || {};
                    $('#summaryCasemixTeamPool').text(formatRupiah(data.team_pool || 0));
                    $('#summaryCasemixReward').text(formatRupiah(data.total_reward || 0));
                    $('#summaryCasemixVerifikasi').text(formatRupiah(data.verifikasi_hasil_bpjs || 0));
                    $('#summaryCasemixLocked').text(formatNumber(data.locked_count || 0));
                    $('#summaryCasemixScore').text(formatPercent(data.score_percent || 0));
                    $('#summaryCasemixRewardPercent').text(formatPercent(data.reward_percent || 0));
                    $('#summaryCasemixLeader').text(formatRupiah(data.leader_total || 0));
                    $('#summaryCasemixKanit').text(formatRupiah(data.kanit_total || 0));
                    $('#summaryCasemixInputer').text(formatRupiah(data.inputer_total || 0));
                    $('#summaryCasemixCount').html('<i class="mdi mdi-file-check-outline"></i> ' + formatNumber(data.generated_count || 0) + ' data generate');
                    $('#summaryCasemixNote').text(
                        formatRupiah(data.team_pool || 0) + ' pool tim dari ' +
                        formatRupiah(data.total_reward || 0) + ' total reward Casemix.'
                    );
                    $('#summaryCasemixSubtitle').text(
                        'Periode ' + ($('#periodeCasemix').val() || '-') + ' / ' +
                        formatNumber(data.generated_count || 0) + ' data generate.'
                    );
                }
            });
        }

        const table = $('#tableGenerateCasemix').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            lengthChange: false,
            pageLength: 10,
            order: [],
            ajax: {
                url: "{{ route("backOffice.keuangan.hitungPremi.generateCasemix.table") }}",
                data: function(data) {
                    data.periode = $('#periodeCasemix').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'periode' },
                {
                    data: 'score_percent',
                    className: 'text-center',
                    render: data => '<span class="casemix-grade">' + escapeHtml(formatPercent(data || 0)) + '</span>'
                },
                { data: 'reward_percent', className: 'text-center', render: formatPercent },
                { data: 'verifikasi_hasil_bpjs', className: 'text-end', render: formatRupiah },
                { data: 'total_reward', className: 'text-end', render: formatRupiah },
                { data: 'team_pool', className: 'text-end', render: formatRupiah },
                { data: 'total_dibagikan', className: 'text-end', render: formatRupiah },
                {
                    data: 'is_locked',
                    render: function(data, type, row) {
                        if (data) {
                            return '<span class="casemix-lock locked" title="' +
                                escapeHtml([row.locked_by_name, row.locked_at].filter(Boolean).join(' / ')) +
                                '"><i class="mdi mdi-lock"></i>Terkunci</span>';
                        }
                        return '<span class="casemix-lock open"><i class="mdi mdi-lock-open-variant-outline"></i>Terbuka</span>';
                    }
                },
                { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
            ],
            language: {
                processing: 'Memuat data Casemix...',
                emptyTable: 'Belum ada hasil generate Casemix.',
                zeroRecords: 'Data tidak ditemukan.',
                info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                infoEmpty: 'Belum ada data',
                paginate: { previous: 'Sebelumnya', next: 'Berikutnya' }
            }
        });

        function refreshAll() {
            loadConfig();
            loadSummary();
            table.ajax.reload(null, false);
        }

        function openGenerateModal() {
            loadConfig(function(config) {
                $('#generateCasemixTitle').text('Generate Casemix / ' + ($('#periodeCasemix').val() || '-'));
                $('#generateBiayaRsCasemix, #generateTarifBpjsCasemix, #generateVerifikasiBpjsCasemix').val('');
                $('#generateCasemixQuestions').html(questionInputsHtml('casemix_answer', config.questions || [], {}));
                updateGeneratePreview();
                generateModal.show();
            });
        }

        function openConfigModal() {
            loadConfig(function(config) {
                $('#configExcellentMinCasemix').val(config.excellent_min_percent ?? 90);
                $('#configExcellentRewardCasemix').val(config.excellent_reward_percent ?? 1.25);
                $('#configGoodMinCasemix').val(config.good_min_percent ?? 80);
                $('#configGoodRewardCasemix').val(config.good_reward_percent ?? 1);
                $('#configLowRewardCasemix').val(config.low_reward_percent ?? .75);
                $('#configTeamPoolCasemix').val(config.team_pool_percent ?? 70);
                $('#configLeaderPercentCasemix').val(config.leader_percent ?? 68);
                $('#configKanitPercentCasemix').val(config.kanit_percent ?? 12);
                $('#configInputerPercentCasemix').val(config.inputer_percent ?? 20);
                $('#configCasemixQuestionScores').html(configQuestionScoresHtml(config.questions || []));
                fillSelect('#configCasemixLeader', config.recipients?.leader || []);
                fillSelect('#configCasemixKanit', config.recipients?.kanit || []);
                fillSelect('#configCasemixInputer', config.recipients?.inputer || []);
                $('#configCasemixRuleText').text(
                    'Reward ' + formatPercent(config.excellent_reward_percent) + ' jika score > ' +
                    formatPercent(config.excellent_min_percent) + ', pool tim ' +
                    formatPercent(config.team_pool_percent) + '.'
                );
                updateConfigRoleTotal();
                configModal.show();
            });
        }

        function renderDetail(data) {
            const loss = data.loss_summary || lossSummary(
                data.biaya_rs || 0,
                data.tarif_bpjs || 0,
                data.verifikasi_hasil_bpjs || 0,
                data.kerugian_awal_percent || 0
            );
            loss.kerugian_awal_percent = data.kerugian_awal_percent || loss.kerugian_awal_percent || 0;

            $('#detailCasemixTitle').text((data.periode || '-') + ' / Score ' + formatPercent(data.score_percent || 0));
            $('#detailCasemixMeta').text(
                'Generated oleh ' + (data.generate_by_name || '-') + ' pada ' + (data.generated_at || '-') +
                ' / Status: ' + (data.is_locked ? 'Terkunci' : 'Terbuka')
            );
            $('#detailCasemixStatusBadge').text(data.is_locked ? 'Terkunci' : 'Terbuka');
            $('#detailCasemixLossInsight').html(lossInsightHtml(loss));
            $('#detailCasemixStats').html([
                miniCard('Biaya RS', formatRupiah(data.biaya_rs || 0), false, 'mdi-hospital-building', 'Modal klaim'),
                miniCard('Jumlah Pengajuan', formatRupiah(data.tarif_bpjs || 0), false, 'mdi-file-send-outline', 'Dasar pembanding klaim'),
                miniCard('Yang Cair', formatRupiah(data.verifikasi_hasil_bpjs || 0), true, 'mdi-cash-check', 'Verifikasi hasil BPJS'),
                miniCard('Kerugian Awal', formatPercent(data.kerugian_awal_percent || 0), false, 'mdi-chart-line-variant', '(Pengajuan - Biaya RS) / Pengajuan'),
                miniCard('Reward', formatPercent(data.reward_percent || 0), false, 'mdi-medal-outline', formatRupiah(data.total_reward || 0)),
                miniCard('Pool Tim', formatRupiah(data.team_pool || 0), true, 'mdi-account-group-outline', formatPercent(data.team_pool_percent || 0)),
                miniCard('Dibagikan', formatRupiah(data.total_dibagikan || 0), false, 'mdi-bank-transfer-out', 'Total penerima')
            ].join(''));
            $('#detailCasemixQuestions').html((data.questionnaire_details || []).map(function(item) {
                return '<div class="casemix-question-card">' +
                    '<div class="casemix-question-title">' + escapeHtml(item.question_label || '-') + '</div>' +
                    '<div class="casemix-simple-section-subtitle">' +
                    'Jawaban: ' + escapeHtml(item.answer_label || '-') + ' / nilai ' +
                    formatNumber(item.score || 0) + ' dari ' + formatNumber(item.max_score || 0) +
                    '</div>' +
                    '</div>';
            }).join('') || '<div class="casemix-empty-state">Tidak ada questionnaire tersimpan.</div>');
            $('#detailCasemixRecipients').html(recipientGroupsHtml(data.recipient_groups || []));
        }

        function openDetailModal(id) {
            const template =
                "{{ route("backOffice.keuangan.hitungPremi.generateCasemix.detail", ["id" => "__ID__"]) }}";

            $('#detailCasemixLoading').removeClass('d-none');
            $('#detailCasemixContent').addClass('d-none');
            detailModal.show();

            $.ajax({
                url: template.replace('__ID__', id),
                success: function(response) {
                    renderDetail(response.data || {});
                    $('#detailCasemixLoading').addClass('d-none');
                    $('#detailCasemixContent').removeClass('d-none');
                },
                error: function(xhr) {
                    detailModal.hide();
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                }
            });
        }

        function changeLock(id, action) {
            const isLock = action === 'lock';
            const template = isLock ?
                "{{ route("backOffice.keuangan.hitungPremi.generateCasemix.lock", ["id" => "__ID__"]) }}" :
                "{{ route("backOffice.keuangan.hitungPremi.generateCasemix.unlock", ["id" => "__ID__"]) }}";

            Swal.fire({
                icon: isLock ? 'warning' : 'question',
                title: isLock ? 'Kunci data Casemix?' : 'Buka kunci data Casemix?',
                text: isLock ? 'Data yang dikunci tidak bisa digenerate ulang.' :
                    'Data dapat digenerate ulang setelah kunci dibuka.',
                showCancelButton: true,
                confirmButtonText: isLock ? 'Kunci Data' : 'Buka Kunci',
                cancelButtonText: 'Batal',
                showLoaderOnConfirm: true,
                preConfirm: function() {
                    return $.ajax({
                        url: template.replace('__ID__', id),
                        method: 'POST'
                    }).catch(function(xhr) {
                        Swal.showValidationMessage(errorMessage(xhr));
                    });
                }
            }).then(function(result) {
                if (result.isConfirmed && result.value) {
                    Swal.fire('Berhasil', result.value.message, 'success');
                    refreshAll();
                }
            });
        }

        function deleteResult(id) {
            const template =
                "{{ route("backOffice.keuangan.hitungPremi.generateCasemix.delete", ["id" => "__ID__"]) }}";

            Swal.fire({
                icon: 'warning',
                title: 'Hapus data Casemix?',
                text: 'Data yang dihapus tidak bisa dikembalikan.',
                showCancelButton: true,
                confirmButtonText: 'Hapus Data',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc3545',
                showLoaderOnConfirm: true,
                preConfirm: function() {
                    return $.ajax({
                        url: template.replace('__ID__', id),
                        method: 'DELETE'
                    }).catch(function(xhr) {
                        Swal.showValidationMessage(errorMessage(xhr));
                    });
                }
            }).then(function(result) {
                if (result.isConfirmed && result.value) {
                    Swal.fire('Berhasil', result.value.message, 'success');
                    refreshAll();
                }
            });
        }

        setDefaultPeriod();
        initSelect2();
        refreshAll();

        $('#periodeCasemix').on('change', refreshAll);
        $('#btnPrevPeriodCasemix').on('click', () => movePeriod(-1));
        $('#btnNextPeriodCasemix').on('click', () => movePeriod(1));
        $('#btnCurrentPeriodCasemix').on('click', function() {
            const now = new Date();
            setPeriodFromDate(new Date(now.getFullYear(), now.getMonth(), 1));
        });
        $('#btnRefreshCasemix').on('click', refreshAll);
        $('#btnOpenGenerateCasemix').on('click', openGenerateModal);
        $('#btnOpenConfigCasemix').on('click', openConfigModal);
        $('#searchGenerateCasemix').on('input', function() {
            table.search(this.value).draw();
        });
        $('#formGenerateCasemix').on('input change', 'input', updateGeneratePreview);
        $('#formConfigCasemix').on('input change', 'input, select', updateConfigRoleTotal);

        $('#formConfigCasemix').on('submit', function(event) {
            event.preventDefault();
            const button = $('#btnSubmitConfigCasemix');
            const originalHtml = button.html();

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateCasemix.updateConfig") }}",
                method: 'PUT',
                data: configPayload(),
                beforeSend: function() {
                    button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');
                },
                success: function(response) {
                    casemixConfig = response.data || casemixConfig;
                    configModal.hide();
                    Swal.fire('Berhasil', response.message, 'success');
                    refreshAll();
                },
                error: function(xhr) {
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                },
                complete: function() {
                    button.prop('disabled', false).html(originalHtml);
                }
            });
        });

        $('#formGenerateCasemix').on('submit', function(event) {
            event.preventDefault();
            const button = $('#btnSubmitGenerateCasemix');
            const originalHtml = button.html();

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateCasemix.store") }}",
                method: 'POST',
                data: generatePayload(),
                beforeSend: function() {
                    button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Generate...');
                },
                success: function(response) {
                    generateModal.hide();
                    Swal.fire('Berhasil', response.message, 'success');
                    refreshAll();
                },
                error: function(xhr) {
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                },
                complete: function() {
                    button.prop('disabled', false).html(originalHtml);
                }
            });
        });

        $('#tableGenerateCasemix').on('click', '.btn-detail-casemix', function() {
            openDetailModal($(this).data('id'));
        });
        $('#tableGenerateCasemix').on('click', '.btn-lock-casemix', function() {
            changeLock($(this).data('id'), 'lock');
        });
        $('#tableGenerateCasemix').on('click', '.btn-unlock-casemix', function() {
            changeLock($(this).data('id'), 'unlock');
        });
        $('#tableGenerateCasemix').on('click', '.btn-delete-casemix', function() {
            deleteResult($(this).data('id'));
        });
    });
</script>
