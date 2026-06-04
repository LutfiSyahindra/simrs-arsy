@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")
    @include("template.AddOn.select2")
    @include("template.AddOn.dropify")
    @include("simrs.partials.masterFinanceStyle")
    <style>
        .finance-master-page .mapping-content {
            padding: 18px;
        }

        .finance-master-page .mapping-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .finance-master-page .mapping-summary-item {
            border: 1px solid var(--fm-line);
            border-radius: 8px;
            background: #fff;
            padding: 14px 15px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .finance-master-page .mapping-summary-icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 38px;
            color: #fff;
        }

        .finance-master-page .mapping-summary-icon.primary {
            background: #2563eb;
        }

        .finance-master-page .mapping-summary-icon.success {
            background: #16a34a;
        }

        .finance-master-page .mapping-summary-icon.warning {
            background: #f59e0b;
        }

        .finance-master-page .mapping-summary-value {
            color: var(--fm-ink);
            font-size: 20px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 3px;
        }

        .finance-master-page .mapping-summary-label {
            color: var(--fm-muted);
            font-size: 12px;
            font-weight: 600;
        }

        .finance-master-page .mapping-section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .finance-master-page .mapping-section-title h6 {
            margin-bottom: 0;
            color: var(--fm-ink);
            font-size: 14px;
            font-weight: 800;
        }

        .finance-master-page .mapping-count {
            color: var(--fm-muted);
            font-size: 12px;
            font-weight: 700;
        }

        .finance-master-page .mapping-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .finance-master-page .mapping-menu-card {
            min-height: 178px;
            border: 1px solid var(--fm-line);
            border-radius: 8px;
            background: #fff;
            padding: 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 14px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.04);
            transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
        }

        .finance-master-page .mapping-menu-card:hover {
            border-color: #bfdbfe;
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
            transform: translateY(-2px);
        }

        .finance-master-page .mapping-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .finance-master-page .mapping-card-icon {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 42px;
            color: #fff;
        }

        .finance-master-page .mapping-card-icon.blue {
            background: #2563eb;
        }

        .finance-master-page .mapping-card-icon.green {
            background: #16a34a;
        }

        .finance-master-page .mapping-card-icon.cyan {
            background: #0891b2;
        }

        .finance-master-page .mapping-card-icon.orange {
            background: #f59e0b;
        }

        .finance-master-page .mapping-card-icon.red {
            background: #dc2626;
        }

        .finance-master-page .mapping-card-icon.slate {
            background: #475569;
        }

        .finance-master-page .mapping-card-icon i,
        .finance-master-page .mapping-summary-icon i {
            font-size: 21px;
            line-height: 1;
        }

        .finance-master-page .mapping-status {
            border-radius: 6px;
            padding: 5px 8px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .finance-master-page .mapping-status.ready {
            background: #ecfdf3;
            color: #166534;
        }

        .finance-master-page .mapping-status.draft {
            background: #fff7ed;
            color: #c2410c;
        }

        .finance-master-page .mapping-status.review {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .finance-master-page .mapping-card-title {
            color: var(--fm-ink);
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .finance-master-page .mapping-card-text {
            color: var(--fm-muted);
            font-size: 12px;
            line-height: 1.45;
            margin-bottom: 0;
        }

        .finance-master-page .mapping-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .finance-master-page .mapping-meta span {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: #f8fafc;
            color: #475569;
            padding: 5px 8px;
            font-size: 11px;
            font-weight: 700;
        }

        .finance-master-page .mapping-card-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            width: 100%;
            min-height: 36px;
            border-radius: 6px;
            border: 1px solid #dbeafe;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
        }

        .finance-master-page .mapping-card-action:hover {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
            text-decoration: none;
        }

        .finance-master-page .mapping-card-action.is-draft {
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #64748b;
        }

        .finance-master-page .mapping-empty {
            display: none;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            background: #f8fafc;
            color: var(--fm-muted);
            padding: 24px;
            text-align: center;
            font-weight: 700;
        }

        @media (max-width: 1199.98px) {
            .finance-master-page .mapping-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {

            .finance-master-page .mapping-summary,
            .finance-master-page .mapping-grid {
                grid-template-columns: 1fr;
            }

            .finance-master-page .mapping-section-title {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
@endpush

@section("content")
    <div class="finance-master-page">
        <nav class="page-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Mapping</a></li>
                <li class="breadcrumb-item active" aria-current="page">Data Mapping</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card master-card">
                    <div class="card-body p-0">
                        <div class="master-panel">
                            <div class="master-title-group">
                                <div class="master-icon master-icon-warning">
                                    <i class="mdi mdi-map"></i>
                                </div>

                                <div>
                                    <div class="master-eyebrow">Master Keuangan</div>
                                    <h5 class="master-title">Menu Mapping</h5>
                                    <p class="master-subtitle mb-0">
                                        Kelola penghubung data skor, tunjangan, dan parameter keuangan lainnya.
                                    </p>
                                </div>
                            </div>

                            <div class="master-actions">
                                <div class="input-group input-group-sm master-search">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="mdi mdi-magnify text-muted"></i>
                                    </span>
                                    <input type="text" id="searchMapping" class="form-control border-start-0"
                                        placeholder="Cari mapping...">
                                </div>
                            </div>
                        </div>

                        <div class="mapping-content">

                            <div class="mapping-grid" id="mappingGrid">
                                <div class="mapping-menu-card"
                                    data-search="mapping skor profesi masa kerja jabatan bobot penilaian">
                                    <div>
                                        <div class="mapping-card-head">
                                            <span class="mapping-card-icon blue">
                                                <i class="mdi mdi-star-circle-outline"></i>
                                            </span>
                                            <span class="mapping-status ready">Siap</span>
                                        </div>
                                        <div class="mt-3">
                                            <div class="mapping-card-title">Mapping Skor</div>
                                            <p class="mapping-card-text">
                                                Hubungkan profesi, masa kerja, dan jabatan ke bobot skor.
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="mapping-meta mb-3">
                                            <span>Profesi</span>
                                            <span>Masa kerja</span>
                                            <span>Jabatan</span>
                                        </div>
                                        <a href="{{ route("masterData.mapping.mappingSkor.mappingSkor") }}"
                                            class="mapping-card-action">
                                            <span>Buka mapping</span>
                                            <i class="mdi mdi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>

                                <div class="mapping-menu-card"
                                    data-search="mapping tunjangan pegawai jenis tunjangan nominal distribusi">
                                    <div>
                                        <div class="mapping-card-head">
                                            <span class="mapping-card-icon green">
                                                <i class="mdi mdi-wallet"></i>
                                            </span>
                                            <span class="mapping-status ready">Siap</span>
                                        </div>
                                        <div class="mt-3">
                                            <div class="mapping-card-title">Mapping Tunjangan</div>
                                            <p class="mapping-card-text">
                                                Sambungkan pegawai dengan jenis tunjangan dan nominalnya.
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="mapping-meta mb-3">
                                            <span>Pegawai</span>
                                            <span>Jenis</span>
                                            <span>Nominal</span>
                                        </div>
                                        <a href="{{ route("masterData.keuangan.tunjanganPegawai") }}"
                                            class="mapping-card-action">
                                            <span>Buka mapping</span>
                                            <i class="mdi mdi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>

                                <div class="mapping-menu-card" data-search="mapping pegawai gapok gaji pokok status kerja">
                                    <div>
                                        <div class="mapping-card-head">
                                            <span class="mapping-card-icon red">
                                                <i class="mdi mdi-cash-multiple"></i>
                                            </span>
                                            <span class="mapping-status ready">Siap</span>
                                        </div>
                                        <div class="mt-3">
                                            <div class="mapping-card-title">Mapping Gaji Pokok</div>
                                            <p class="mapping-card-text">
                                                Hubungkan pegawai dengan status kerja, masa kerja, dan gaji pokok.
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="mapping-meta mb-3">
                                            <span>NIK</span>
                                            <span>Status</span>
                                            <span>Gapok</span>
                                        </div>
                                        <a href="{{ route("masterData.keuangan.gapok") }}" class="mapping-card-action">
                                            <span>Buka mapping</span>
                                            <i class="mdi mdi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>

                                <div class="mapping-menu-card" data-search="mapping unit pegawai">
                                    <div>
                                        <div class="mapping-card-head">
                                            <span class="mapping-card-icon orange">
                                                <i class="mdi mdi-office-building"></i>
                                            </span>
                                            <span class="mapping-status ready">Siap</span>
                                        </div>
                                        <div class="mt-3">
                                            <div class="mapping-card-title">Mapping Unit Pegawai</div>
                                            <p class="mapping-card-text">
                                                Hubungkan pegawai dengan unit kerja dan posisi.
                                            </p>
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="mapping-meta mb-3">
                                            <span>NIK</span>
                                            <span>Status</span>
                                            <span>Unit</span>
                                        </div>
                                        <a href="{{ route("masterData.mapping.mappingUnit.mappingUnit") }}"
                                            class="mapping-card-action">
                                            <span>Buka mapping</span>
                                            <i class="mdi mdi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>

                                <div class="mapping-menu-card" data-search="mapping tindakan">
                                    <div>
                                        <div class="mapping-card-head">
                                            <span class="mapping-card-icon cyan">
                                                <i class="mdi mdi-medical-bag"></i>
                                            </span>
                                            <span class="mapping-status draft">Draft</span>
                                        </div>
                                        <div class="mt-3">
                                            <div class="mapping-card-title">Mapping Tindakan</div>
                                            <p class="mapping-card-text">
                                                Hubungkan Tindakan dengan Kategori Tindakan.
                                            </p>
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="mapping-meta mb-3">
                                            <span>Tindakan</span>
                                            <span>Jenis tindakan</span>
                                            <span>Unit</span>
                                        </div>
                                        <a href="{{ route("masterData.mapping.mappingTindakan") }}"
                                            class="mapping-card-action">
                                            <span>Buka mapping</span>
                                            <i class="mdi mdi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>

                                <div class="mapping-menu-card"
                                    data-search="mapping lainnya potongan periode pajak akun coa">
                                    <div>
                                        <div class="mapping-card-head">
                                            <span class="mapping-card-icon slate">
                                                <i class="mdi mdi-dots-grid"></i>
                                            </span>
                                            <span class="mapping-status draft">Draft</span>
                                        </div>
                                        <div class="mt-3">
                                            <div class="mapping-card-title">Mapping Lainnya</div>
                                            <p class="mapping-card-text">
                                                Area mapping untuk potongan, periode, pajak, dan akun keuangan.
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="mapping-meta mb-3">
                                            <span>Potongan</span>
                                            <span>Periode</span>
                                            <span>Akun</span>
                                        </div>
                                        <a href="#" class="mapping-card-action is-draft" data-mapping-draft="true">
                                            <span>Draft</span>
                                            <i class="mdi mdi-lock-outline"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="mapping-empty mt-3" id="mappingEmpty">
                                Data mapping tidak ditemukan.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("scripts")
    @include("simrs.masterData.mapping.jsMain")
@endpush
