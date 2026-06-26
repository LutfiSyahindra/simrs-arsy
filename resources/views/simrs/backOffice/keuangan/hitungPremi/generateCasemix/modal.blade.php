<div class="modal fade" id="modalGenerateCasemix" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" id="formGenerateCasemix">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Generate Premi Casemix</h5>
                    <small class="text-muted">Isi nominal BPJS, jawab indikator, lalu cek preview pembagian.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="casemix-simple-head">
                    <div class="casemix-simple-main">
                        <div class="casemix-simple-icon"><i class="mdi mdi-file-chart-outline"></i></div>
                        <div>
                            <div class="casemix-simple-title" id="generateCasemixTitle">Input Periode</div>
                            <div class="casemix-simple-text" id="generateCasemixText">
                                Skor akan dikonversi otomatis menjadi persentase reward.
                            </div>
                        </div>
                    </div>
                    <span class="casemix-simple-badge" id="generateCasemixBadge">Live Preview</span>
                </div>

                <div class="casemix-generate-layout mb-3">
                    <div class="casemix-config-section casemix-claim-section">
                        <h6><i class="mdi mdi-cash-register"></i> Nominal Klaim</h6>
                        <div class="casemix-input-stack">
                            <div>
                                <label class="form-label">Biaya RS</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" min="0" class="form-control" id="generateBiayaRsCasemix"
                                        placeholder="0">
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Jumlah Pengajuan / Tarif BPJS</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" min="0" class="form-control" id="generateTarifBpjsCasemix"
                                        placeholder="0">
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Yang Cair / Verifikasi BPJS</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" min="0" class="form-control"
                                        id="generateVerifikasiBpjsCasemix" placeholder="0">
                                </div>
                            </div>
                        </div>
                        <div class="casemix-field-help mt-2">
                            Pengajuan dipakai sebagai pembanding klaim; yang cair menjadi dasar reward Casemix.
                        </div>
                    </div>

                    <div class="casemix-live-section">
                        <div class="casemix-loss-panel" id="generateCasemixLossInsight"></div>
                        <div class="casemix-score-panel">
                            <div class="casemix-score-head">
                                <div>
                                    <div class="casemix-simple-section-title">Score dan Reward</div>
                                    <div class="casemix-simple-section-subtitle">
                                        Preview berubah langsung saat nominal atau jawaban diubah.
                                    </div>
                                </div>
                                <span class="casemix-simple-badge" id="generateCasemixTierBadge">Tier</span>
                            </div>
                            <div id="generateCasemixScoreMeter"></div>
                            <div class="casemix-mini-grid" id="generateCasemixPreviewCards"></div>
                        </div>
                    </div>
                </div>

                <div class="casemix-simple-section mb-3">
                    <div class="casemix-section-toolbar">
                        <div>
                            <div class="casemix-simple-section-title">Indikator Penilaian Kinerja Tim Casemix</div>
                            <div class="casemix-simple-section-subtitle">
                                Pilih satu jawaban untuk setiap indikator. Nilai jawaban mengikuti konfigurasi aktif.
                            </div>
                        </div>
                        <span class="casemix-simple-badge" id="generateCasemixQuestionProgress">0/0 terjawab</span>
                    </div>
                    <div class="casemix-question-list mt-3" id="generateCasemixQuestions"></div>
                </div>

                <div class="casemix-simple-section mb-3">
                    <div class="casemix-section-toolbar">
                        <div>
                            <div class="casemix-simple-section-title">Preview Pembagian Tim</div>
                            <div class="casemix-simple-section-subtitle">
                                Pool tim dibagi ke pegawai konfigurasi aktif per role.
                            </div>
                        </div>
                        <span class="casemix-simple-badge"><i class="mdi mdi-account-group-outline"></i> Penerima</span>
                    </div>
                    <div class="casemix-recipient-list mt-3" id="generateCasemixRecipientPreview"></div>
                </div>

                <div class="casemix-simple-section mb-0">
                    <div class="casemix-simple-section-title">Rumus Preview</div>
                    <div id="generateCasemixFormulaPreview"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold" id="btnSubmitGenerateCasemix">
                    <i class="mdi mdi-content-save-check-outline me-1"></i>
                    Generate dan Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalConfigCasemix" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" id="formConfigCasemix">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Konfigurasi Premi Casemix</h5>
                    <small class="text-muted">Atur threshold skor, reward BPJS, pembagian tim, nilai indikator, dan pegawai.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="casemix-simple-head">
                    <div class="casemix-simple-main">
                        <div class="casemix-simple-icon"><i class="mdi mdi-tune-variant"></i></div>
                        <div>
                            <div class="casemix-simple-title">Formula Aktif</div>
                            <div class="casemix-simple-text" id="configCasemixRuleText">
                                Konfigurasi menentukan hasil generate periode berikutnya.
                            </div>
                        </div>
                    </div>
                    <span class="casemix-simple-badge">Konfigurasi</span>
                </div>

                <div class="casemix-config-overview mb-3" id="configCasemixOverview">
                    <div class="casemix-config-metric featured">
                        <div class="casemix-config-metric-icon"><i class="mdi mdi-medal-outline"></i></div>
                        <div>
                            <span>Reward Excellent</span>
                            <strong id="configCasemixOverviewExcellent">-</strong>
                            <small id="configCasemixOverviewExcellentNote">Score terbaik</small>
                        </div>
                    </div>
                    <div class="casemix-config-metric">
                        <div class="casemix-config-metric-icon"><i class="mdi mdi-account-group-outline"></i></div>
                        <div>
                            <span>Pool Tim</span>
                            <strong id="configCasemixOverviewPool">-</strong>
                            <small>Dari reward Casemix</small>
                        </div>
                    </div>
                    <div class="casemix-config-metric">
                        <div class="casemix-config-metric-icon"><i class="mdi mdi-keyboard-outline"></i></div>
                        <div>
                            <span>Inputer</span>
                            <strong id="configCasemixOverviewInputer">-</strong>
                            <small>Persen / pembagi</small>
                        </div>
                    </div>
                    <div class="casemix-config-metric">
                        <div class="casemix-config-metric-icon"><i class="mdi mdi-format-list-checks"></i></div>
                        <div>
                            <span>Indikator</span>
                            <strong id="configCasemixOverviewQuestions">-</strong>
                            <small>Questionnaire aktif</small>
                        </div>
                    </div>
                </div>

                <div class="casemix-config-grid mb-3">
                    <div class="casemix-config-section">
                        <h6><i class="mdi mdi-medal-outline"></i> Threshold Reward</h6>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label">Nilai Excellent Di Atas</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                        id="configExcellentMinCasemix">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Reward Excellent</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0" class="form-control"
                                        id="configExcellentRewardCasemix">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Nilai Good Minimal</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                        id="configGoodMinCasemix">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Reward Good</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0" class="form-control"
                                        id="configGoodRewardCasemix">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Reward Low</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0" class="form-control"
                                        id="configLowRewardCasemix">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="casemix-config-section">
                        <h6><i class="mdi mdi-account-cash-outline"></i> Pembagian Tim</h6>
                        <div class="mb-3">
                            <label class="form-label">Pool Tim dari Reward</label>
                            <div class="input-group">
                                <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                    id="configTeamPoolCasemix">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6 col-lg-3">
                                <label class="form-label">Ketua</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                        id="configLeaderPercentCasemix">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <label class="form-label">Kanit</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                        id="configKanitPercentCasemix">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <label class="form-label">Inputer</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0" max="100" class="form-control"
                                        id="configInputerPercentCasemix">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <label class="form-label">Pembagi Inputer</label>
                                <div class="input-group">
                                    <span class="input-group-text">/</span>
                                    <input type="number" min="1" max="999" step="1" class="form-control"
                                        id="configInputerDividerCasemix">
                                </div>
                            </div>
                        </div>
                        <small class="casemix-field-help" id="configCasemixRoleTotal">Total role wajib 100%.</small>
                    </div>

                    <div class="casemix-config-section">
                        <h6><i class="mdi mdi-account-multiple-check-outline"></i> Pegawai Penerima</h6>
                        <div class="mb-3">
                            <label class="form-label">Ketua Tim Casemix</label>
                            <select class="form-select" id="configCasemixLeader" multiple></select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kanit / Coding</label>
                            <select class="form-select" id="configCasemixKanit" multiple></select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Inputer</label>
                            <select class="form-select" id="configCasemixInputer" multiple></select>
                        </div>
                    </div>
                </div>

                <div class="casemix-simple-section">
                    <div class="casemix-simple-section-title">Pilihan dan Nilai Jawaban Questionnaire</div>
                    <div class="casemix-simple-section-subtitle">
                        Teks pilihan jawaban dan nilai setiap pilihan dapat diubah sesuai kebijakan.
                    </div>
                    <div class="casemix-question-list mt-3" id="configCasemixQuestionScores"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold" id="btnSubmitConfigCasemix">
                    <i class="mdi mdi-content-save-outline me-1"></i>
                    Simpan Konfigurasi
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalDetailCasemix" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Detail Premi Casemix</h5>
                    <small class="text-muted">Audit nominal, skor questionnaire, formula, dan penerima.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="detailCasemixLoading" class="text-center py-5">
                    <span class="spinner-border text-primary"></span>
                    <div class="text-muted mt-2">Memuat detail Casemix...</div>
                </div>

                <div id="detailCasemixContent" class="d-none">
                    <div class="casemix-simple-head">
                        <div class="casemix-simple-main">
                            <div class="casemix-simple-icon"><i class="mdi mdi-clipboard-text-search-outline"></i></div>
                            <div>
                                <div class="casemix-simple-title" id="detailCasemixTitle">-</div>
                                <div class="casemix-simple-text" id="detailCasemixMeta">-</div>
                            </div>
                        </div>
                        <span class="casemix-simple-badge" id="detailCasemixStatusBadge">Status</span>
                    </div>

                    <div class="casemix-loss-panel mb-3" id="detailCasemixLossInsight"></div>
                    <div class="casemix-mini-grid mb-3" id="detailCasemixStats"></div>

                    <div class="casemix-simple-section mb-3">
                        <div class="casemix-simple-section-title">Questionnaire Tersimpan</div>
                        <div class="casemix-question-list mt-3" id="detailCasemixQuestions"></div>
                    </div>

                    <div class="casemix-simple-section mb-0">
                        <div class="casemix-simple-section-title">Penerima Premi Tim</div>
                        <div class="casemix-recipient-list mt-3" id="detailCasemixRecipients"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
