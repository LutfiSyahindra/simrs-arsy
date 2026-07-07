<div class="modal fade" id="modalGenerateOperasi" tabindex="-1" aria-labelledby="modalGenerateOperasiLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" id="formGenerateOperasi">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="modalGenerateOperasiLabel">Generate Premi Operasi</h5>
                    <small class="text-muted">Preview konfigurasi wajib dimuat sebelum generate disimpan.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="operasi-modal-hero">
                    <div class="operasi-modal-hero-icon">
                        <i class="mdi mdi-calculator-variant-outline"></i>
                    </div>
                    <div>
                        <div class="operasi-modal-hero-title" id="generateModalHeroTitle">
                            Generate berbasis konfigurasi aktif
                        </div>
                        <div class="operasi-modal-hero-text">
                            Sistem menampilkan alur pembagian, total pool, dan penerima sebelum data disimpan.
                        </div>
                    </div>
                    <div class="operasi-modal-hero-side">
                        <span class="operasi-config-badge" id="generateHeroPeriodBadge">Periode</span>
                        <span class="operasi-config-badge" id="generateHeroTypeBadge">UMUM</span>
                    </div>
                </div>

                <div class="operasi-generate-layout">
                    <div class="operasi-modal-card">
                        <div class="operasi-modal-card-head">
                            <div>
                                <div class="operasi-modal-card-title">Data Generate</div>
                                <div class="operasi-modal-card-subtitle">Periode, jenis, dan total pendapatan</div>
                            </div>
                            <span class="operasi-config-badge" id="generateConfigBadge">UMUM</span>
                        </div>
                        <div class="operasi-modal-card-body">
                            <div class="mb-3">
                                <label class="form-label">Periode</label>
                                <input type="month" class="form-control" id="periodeGenerateOperasi" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Jenis</label>
                                <input type="text" class="form-control" id="jenisGenerateOperasiLabel" readonly>
                                <input type="hidden" id="jenisGenerateOperasi">
                            </div>
                            <div class="mb-3" id="totalGenerateOperasiGroup">
                                <label class="form-label">Total Nominal Pendapatan Operasi</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control text-end fw-bold"
                                        id="totalGenerateOperasi" inputmode="numeric" autocomplete="off"
                                        placeholder="0">
                                </div>
                            </div>
                            <div class="d-none" id="bpjsGenerateOperasiGroup">
                                <div class="mb-3">
                                    <label class="form-label">Jumlah PX BPJS</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="mdi mdi-account-injury-outline"></i></span>
                                        <input type="text" class="form-control text-end fw-bold"
                                            id="jumlahPasienGenerateOperasi" inputmode="numeric" autocomplete="off"
                                            placeholder="0">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nominal</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" class="form-control text-end fw-bold"
                                            id="nominalPengaliGenerateOperasi" inputmode="numeric" autocomplete="off"
                                            placeholder="0">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Grand Total BPJS (PX x Nominal)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" class="form-control text-end fw-bold"
                                            id="grandTotalBpjsGenerateOperasi" readonly value="0">
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-info text-white fw-bold w-100" id="btnPreviewOperasi">
                                <i class="mdi mdi-eye-outline me-1"></i>
                                Hitung dan Tampilkan Preview
                            </button>

                            <div class="operasi-checklist">
                                <div class="operasi-checklist-item">
                                    <i class="mdi mdi-check-circle-outline"></i>
                                    Konfigurasi aktif dibaca otomatis sesuai jenis.
                                </div>
                                <div class="operasi-checklist-item">
                                    <i class="mdi mdi-check-circle-outline"></i>
                                    Preview menandai penerima yang belum lengkap.
                                </div>
                                <div class="operasi-checklist-item">
                                    <i class="mdi mdi-check-circle-outline"></i>
                                    Hasil generate menyimpan snapshot rumus.
                                </div>
                            </div>

                            <div class="alert alert-info mt-3 mb-0" id="previewHintOperasi">
                                Masukkan total nominal, lalu tampilkan preview konfigurasi sebelum generate.
                            </div>
                        </div>
                    </div>

                    <div class="operasi-modal-card">
                        <div class="operasi-modal-card-head">
                            <div>
                                <div class="operasi-modal-card-title">Alur Pembagian Aktif</div>
                                <div class="operasi-modal-card-subtitle" id="generateFormulaSubtitle">
                                    Mengikuti konfigurasi jenis yang dipilih.
                                </div>
                            </div>
                        </div>
                        <div class="operasi-modal-card-body">
                            <div class="operasi-mini-stat-grid" id="generateConfigStats">
                                <div class="operasi-mini-stat">
                                    <div class="operasi-mini-stat-label">Penerima</div>
                                    <div class="operasi-mini-stat-value">0</div>
                                </div>
                                <div class="operasi-mini-stat">
                                    <div class="operasi-mini-stat-label">Instrumen</div>
                                    <div class="operasi-mini-stat-value">0</div>
                                </div>
                                <div class="operasi-mini-stat">
                                    <div class="operasi-mini-stat-label">Anastesi</div>
                                    <div class="operasi-mini-stat-value">0</div>
                                </div>
                            </div>
                            <div class="operasi-flow" id="generateFormulaFlow">
                                <div class="operasi-flow-row">
                                    <div class="operasi-flow-icon"><i class="mdi mdi-cog-outline"></i></div>
                                    <div>
                                        <div class="operasi-flow-title">Konfigurasi belum dimuat</div>
                                        <div class="operasi-flow-subtitle">Klik preview untuk membaca konfigurasi terbaru.</div>
                                    </div>
                                    <div class="operasi-flow-value">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="previewGenerateOperasiWrap" class="mt-3 d-none">
                    <div class="operasi-modal-card mb-3">
                        <div class="operasi-modal-card-head">
                            <div>
                                <div class="operasi-modal-card-title">Preview Pool Premi</div>
                                <div class="operasi-modal-card-subtitle">Nominal yang akan disimpan ke hasil generate</div>
                            </div>
                        </div>
                        <div class="operasi-modal-card-body">
                            <div class="operasi-preview-insight" id="previewInsightOperasi"></div>
                            <div class="operasi-preview-grid" id="previewPoolsOperasi"></div>
                            <div id="previewQualityOperasi"></div>
                        </div>
                    </div>

                    <div class="operasi-modal-card">
                        <div class="operasi-modal-card-head">
                            <div>
                                <div class="operasi-modal-card-title">Preview Penerima Premi</div>
                                <div class="operasi-modal-card-subtitle" id="previewRecipientSubtitle">
                                    Pembagian rata per kelompok penerima.
                                </div>
                            </div>
                        </div>
                        <div class="operasi-modal-card-body">
                            <div class="operasi-preview-grid" id="previewRecipientsOperasi"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold" id="btnSubmitGenerateOperasi" disabled>
                    <i class="mdi mdi-calculator-variant-outline me-1"></i>
                    Generate dan Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalDetailOperasi" tabindex="-1" aria-labelledby="modalDetailOperasiLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="modalDetailOperasiLabel">Detail Premi Operasi</h5>
                    <small class="text-muted">Audit pembagian berdasarkan snapshot saat generate.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="detailOperasiLoading" class="text-center py-5">
                    <span class="spinner-border text-info"></span>
                    <div class="text-muted mt-2">Memuat detail operasi...</div>
                </div>

                <div id="detailOperasiContent" class="d-none">
                    <div class="operasi-detail-simple">
                        <section class="operasi-detail-hero">
                            <div>
                                <div class="operasi-detail-eyebrow" id="detailOperasiEyebrow">Generate Operasi</div>
                                <div class="operasi-detail-title" id="detailOperasiTitle">-</div>
                                <div class="operasi-detail-meta" id="detailOperasiMeta">-</div>
                            </div>
                            <div class="operasi-detail-status" id="detailOperasiStatus">-</div>
                        </section>

                        <div class="operasi-detail-kpi-grid">
                            <div class="operasi-detail-kpi primary">
                                <div class="operasi-detail-kpi-label">Total Operasi</div>
                                <div class="operasi-detail-kpi-value" id="detailTotalOperasi">Rp 0</div>
                            </div>
                            <div class="operasi-detail-kpi">
                                <div class="operasi-detail-kpi-label">Dibagikan ke Penerima</div>
                                <div class="operasi-detail-kpi-value" id="detailTotalDibagikan">Rp 0</div>
                            </div>
                            <div class="operasi-detail-kpi">
                                <div class="operasi-detail-kpi-label">Premi Bersama</div>
                                <div class="operasi-detail-kpi-value" id="detailTotalBersama">Rp 0</div>
                            </div>
                            <div class="operasi-detail-kpi">
                                <div class="operasi-detail-kpi-label">Sisa</div>
                                <div class="operasi-detail-kpi-value" id="detailTotalSisa">Rp 0</div>
                            </div>
                        </div>

                        <div class="operasi-detail-note">
                            <strong>Cara baca:</strong> mulai dari total operasi, sistem mengambil bagian instrumen dan
                            anastesi, lalu membagi nominal ke premi bersama dan penerima yang dipilih.
                        </div>

                        <div class="operasi-detail-simple-grid">
                            <div class="operasi-modal-card">
                                <div class="operasi-modal-card-head">
                                    <div>
                                        <div class="operasi-modal-card-title">Alur Hitung</div>
                                        <div class="operasi-modal-card-subtitle">Urutan rumus dalam bahasa sederhana.</div>
                                    </div>
                                </div>
                                <div class="operasi-modal-card-body">
                                    <div class="operasi-detail-steps" id="detailFormulaFlow"></div>
                                </div>
                            </div>

                            <div class="operasi-modal-card">
                                <div class="operasi-modal-card-head">
                                    <div>
                                        <div class="operasi-modal-card-title">Rincian Nominal</div>
                                        <div class="operasi-modal-card-subtitle">Nominal penting hasil perhitungan.</div>
                                    </div>
                                </div>
                                <div class="operasi-modal-card-body">
                                    <div class="operasi-detail-simple-table" id="detailPoolOperasi"></div>
                                </div>
                            </div>
                        </div>

                        <div class="operasi-modal-card">
                            <div class="operasi-modal-card-head">
                                <div>
                                    <div class="operasi-modal-card-title">Penerima Premi</div>
                                    <div class="operasi-modal-card-subtitle" id="detailRecipientSubtitle">
                                        Rincian nominal per kelompok dan per penerima.
                                    </div>
                                </div>
                            </div>
                            <div class="operasi-modal-card-body">
                                <div class="operasi-detail-recipient-list" id="detailRecipientsOperasi"></div>
                            </div>
                        </div>

                        <div class="operasi-modal-card">
                            <div class="operasi-modal-card-head">
                                <div>
                                    <div class="operasi-modal-card-title">Info Generate</div>
                                    <div class="operasi-modal-card-subtitle">User, waktu, dan status kunci.</div>
                                </div>
                            </div>
                            <div class="operasi-modal-card-body">
                                <div class="operasi-detail-timeline" id="detailAuditOperasi"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfigOperasi" tabindex="-1" aria-labelledby="modalConfigOperasiLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" id="formConfigOperasi">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="modalConfigOperasiLabel">Konfigurasi Premi Operasi</h5>
                    <small class="text-muted">Persentase dan penerima disimpan terpisah untuk UMUM dan BPJS.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="jenisConfigOperasi">
                <div class="operasi-modal-hero">
                    <div class="operasi-modal-hero-icon">
                        <i class="mdi mdi-cog-outline"></i>
                    </div>
                    <div>
                        <div class="operasi-modal-hero-title">Atur rumus dan penerima premi operasi</div>
                        <div class="operasi-modal-hero-text">
                            Konfigurasi ini menjadi sumber hitung saat generate. Hasil generate tetap menyimpan snapshot
                            agar histori tidak berubah ketika konfigurasi diedit lagi.
                        </div>
                    </div>
                </div>

                <div class="alert alert-info d-none" id="configBpjsFlowHint">
                    <strong>Alur BPJS:</strong> grand total berasal dari jumlah PX x nominal.
                    Anastesi mengambil 80% untuk pegawai anastesi dan 20% ke premi bersama. Instrumen mengambil
                    80% dari grand total, lalu dipecah 80% untuk pegawai instrumen dan 20% untuk pegawai khusus.
                    Sisa 20% dari grand total masuk premi bersama.
                </div>

                <div class="operasi-config-flow">
                    <div class="operasi-config-step">
                        <div class="operasi-config-step-number">1</div>
                        <div class="operasi-config-step-title">Tetapkan persentase</div>
                        <div class="operasi-config-step-text">Atur porsi instrumen, premi bersama, dan anastesi.</div>
                    </div>
                    <div class="operasi-config-step">
                        <div class="operasi-config-step-number">2</div>
                        <div class="operasi-config-step-title">Pilih penerima</div>
                        <div class="operasi-config-step-text">Penerima bisa berbeda antara UMUM dan BPJS.</div>
                    </div>
                    <div class="operasi-config-step">
                        <div class="operasi-config-step-number">3</div>
                        <div class="operasi-config-step-title">Preview sebelum generate</div>
                        <div class="operasi-config-step-text">Periksa nominal pembagian sebelum disimpan.</div>
                    </div>
                </div>

                <div class="operasi-config-overview">
                    <div class="operasi-config-overview-card">
                        <div class="operasi-config-overview-title">Kesiapan Penerima</div>
                        <div class="operasi-config-overview-text">
                            Ringkasan jumlah penerima yang akan dipakai saat generate.
                        </div>
                        <div class="operasi-mini-stat-grid mt-3" id="configRecipientStats">
                            <div class="operasi-mini-stat">
                                <div class="operasi-mini-stat-label">Total</div>
                                <div class="operasi-mini-stat-value">0</div>
                            </div>
                            <div class="operasi-mini-stat">
                                <div class="operasi-mini-stat-label">Instrumen</div>
                                <div class="operasi-mini-stat-value">0</div>
                            </div>
                            <div class="operasi-mini-stat">
                                <div class="operasi-mini-stat-label">Anastesi</div>
                                <div class="operasi-mini-stat-value">0</div>
                            </div>
                        </div>
                    </div>
                    <div class="operasi-config-overview-card">
                        <div class="operasi-config-overview-title">Keseimbangan Rumus</div>
                        <div class="operasi-config-overview-text">
                            Pantau apakah pecahan persentase sudah proporsional sebelum disimpan.
                        </div>
                        <div class="operasi-config-health" id="configFormulaHealth"></div>
                    </div>
                </div>

                <div class="operasi-config-grid">
                    <div class="operasi-config-section">
                        <h6><i class="mdi mdi-medical-bag me-1"></i> Persentase Instrumen</h6>
                        <div class="operasi-config-field">
                            <div>
                                <label class="form-label">Instrumen dari total operasi</label>
                                <small>UMUM mengikuti konfigurasi. BPJS memakai 80% dari grand total.</small>
                            </div>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" class="form-control"
                                    id="configInstrumenPercent">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="operasi-config-field">
                            <div>
                                <label class="form-label">Premi bersama instrumen</label>
                                <small>UMUM dari pool instrumen. BPJS memakai 20% dari grand total.</small>
                            </div>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" class="form-control"
                                    id="configPremiBersamaPercent">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="operasi-config-field" id="configInstrumenPetugasField">
                            <div>
                                <label class="form-label">Petugas instrumen dari instrumen</label>
                                <small>Default 80% dari pool instrumen.</small>
                            </div>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" class="form-control"
                                    id="configInstrumenPetugasPercent">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="operasi-config-field">
                            <div>
                                <label class="form-label">Pegawai khusus instrumen 20%</label>
                                <small>Dibagi rata ke pegawai khusus yang dipilih.</small>
                            </div>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" class="form-control"
                                    id="configInstrumen20Percent">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="operasi-config-field">
                            <div>
                                <label class="form-label">Pegawai instrumen 80%</label>
                                <small>Dibagi rata ke pegawai instrumen yang dipilih.</small>
                            </div>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" class="form-control"
                                    id="configInstrumen80Percent">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>

                    <div class="operasi-config-section">
                        <h6><i class="mdi mdi-stethoscope me-1"></i> Persentase Anastesi</h6>
                        <div class="operasi-config-field" id="configDokterAnastesiPercentField">
                            <div>
                                <label class="form-label">Pool anastesi dari total operasi</label>
                                <small>Default 40% dari total pendapatan operasi.</small>
                            </div>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" class="form-control"
                                    id="configDokterAnastesiPercent">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="operasi-config-field" id="configPerawatAnastesiPercentField">
                            <div>
                                <label class="form-label">Perawat anastesi dari pool anastesi</label>
                                <small>Default 10%; bagian ini dipecah lagi untuk petugas dan premi bersama.</small>
                            </div>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" class="form-control"
                                    id="configPerawatAnastesiPercent">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="operasi-config-field">
                            <div>
                                <label class="form-label">Pegawai anastesi</label>
                                <small>BPJS memakai 80% dari grand total. UMUM memakai bagian perawat anastesi.</small>
                            </div>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" class="form-control"
                                    id="configPerawatAnastesiPetugasPercent">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="operasi-config-field">
                            <div>
                                <label class="form-label">Premi bersama anastesi</label>
                                <small>BPJS memakai 20% dari grand total. UMUM memakai bagian perawat anastesi.</small>
                            </div>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" class="form-control"
                                    id="configPerawatAnastesiBersamaPercent">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="alert alert-light border mb-0">
                            <strong>Catatan:</strong> untuk BPJS, anastesi langsung memakai grand total dengan pola
                            80% pegawai dan 20% premi bersama. Untuk UMUM, dokter anastesi menerima sisa pool anastesi
                            setelah bagian perawat dihitung.
                        </div>
                    </div>

                    <div class="operasi-config-section">
                        <h6><i class="mdi mdi-account-group-outline me-1"></i> Penerima Instrumen</h6>
                        <div class="mb-3">
                            <label class="form-label">Pegawai Khusus Instrumen 20%</label>
                            <select class="form-select operasi-pegawai-select" id="configInstrumen20Recipients"
                                multiple></select>
                            <div class="operasi-recipient-counter" id="countInstrumen20Recipients">0 penerima dipilih</div>
                        </div>
                        <div>
                            <label class="form-label">Pegawai Instrumen 80%</label>
                            <select class="form-select operasi-pegawai-select" id="configInstrumen80Recipients"
                                multiple></select>
                            <div class="operasi-recipient-counter" id="countInstrumen80Recipients">0 penerima dipilih</div>
                        </div>
                    </div>

                    <div class="operasi-config-section">
                        <h6><i class="mdi mdi-doctor me-1"></i> Penerima Anastesi</h6>
                        <div class="mb-3" id="configDokterAnastesiRecipientsGroup">
                            <label class="form-label">Dokter Anastesi</label>
                            <select class="form-select operasi-dokter-select" id="configDokterAnastesiRecipients"
                                multiple></select>
                            <div class="operasi-recipient-counter" id="countDokterAnastesiRecipients">0 penerima dipilih</div>
                        </div>
                        <div>
                            <label class="form-label" id="configPerawatAnastesiRecipientsLabel">Perawat Anastesi</label>
                            <select class="form-select operasi-pegawai-select" id="configPerawatAnastesiRecipients"
                                multiple></select>
                            <div class="operasi-recipient-counter" id="countPerawatAnastesiRecipients">0 penerima dipilih</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold" id="btnSubmitConfigOperasi">
                    <i class="mdi mdi-content-save-outline me-1"></i>
                    Simpan Konfigurasi
                </button>
            </div>
        </form>
    </div>
</div>
