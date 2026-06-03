@push("style")
    <style>
        .tunjangan-excel-modal .modal-content {
            background: #f8fafc;
        }

        .tunjangan-excel-hero {
            background:
                linear-gradient(135deg, rgba(15, 23, 42, .96), rgba(30, 64, 175, .9)),
                linear-gradient(45deg, rgba(20, 184, 166, .18), rgba(249, 115, 22, .14));
        }

        .tunjangan-excel-icon {
            width: 52px;
            height: 52px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .18);
            flex: 0 0 auto;
        }

        .tunjangan-excel-icon svg {
            width: 28px;
            height: 28px;
        }

        .tunjangan-meta-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .tunjangan-meta-item {
            border: 1px solid rgba(255, 255, 255, .18);
            background: rgba(255, 255, 255, .1);
            border-radius: 8px;
            padding: 10px 12px;
        }

        .tunjangan-step {
            display: grid;
            grid-template-columns: 40px minmax(0, 1fr);
            gap: 10px;
            align-items: start;
            height: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            padding: 14px;
        }

        .tunjangan-step-number {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            font-weight: 800;
        }

        .tunjangan-step.is-template .tunjangan-step-number {
            background: #e0f2fe;
            color: #0369a1;
        }

        .tunjangan-step.is-fill .tunjangan-step-number {
            background: #fef3c7;
            color: #92400e;
        }

        .tunjangan-step.is-upload .tunjangan-step-number {
            background: #dcfce7;
            color: #166534;
        }

        .tunjangan-action-panel,
        .tunjangan-info-panel {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
        }

        .tunjangan-template-panel {
            background: linear-gradient(135deg, #fff, #f0fdfa);
        }

        .tunjangan-excel-modal .dropify-wrapper {
            min-height: 188px;
            height: 188px;
            border: 2px dashed #60a5fa;
            border-radius: 8px;
            background: #eff6ff;
            transition: border-color .2s ease, background .2s ease;
        }

        .tunjangan-excel-modal .dropify-wrapper:hover {
            border-color: #2563eb;
            background: #dbeafe;
        }

        .tunjangan-excel-modal .dropify-wrapper .dropify-message p {
            font-size: 15px;
            color: #1f2937;
            font-weight: 700;
        }

        .tunjangan-excel-modal .dropify-wrapper .dropify-message span.file-icon {
            color: #2563eb;
        }

        .tunjangan-rule-list {
            display: grid;
            gap: 10px;
        }

        .tunjangan-rule-item {
            display: grid;
            grid-template-columns: 32px minmax(0, 1fr);
            gap: 10px;
            align-items: start;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f8fafc;
            padding: 12px;
        }

        .tunjangan-rule-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: #ecfdf5;
            color: #047857;
        }

        .tunjangan-reference-table {
            max-height: 265px;
            overflow: auto;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
        }

        .tunjangan-reference-table.is-small {
            max-height: 160px;
        }

        .tunjangan-reference-table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #f8fafc;
        }

        .tunjangan-code-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 68px;
            border-radius: 8px;
            background: #eef2ff;
            color: #3730a3;
            font-weight: 800;
            padding: 4px 8px;
        }

        .tunjangan-type-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 82px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 800;
            padding: 5px 8px;
        }

        .tunjangan-type-reference {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .tunjangan-type-qty {
            background: #fef3c7;
            color: #92400e;
        }

        .tunjangan-type-percent {
            background: #cffafe;
            color: #0e7490;
        }

        .tunjangan-type-year {
            background: #dcfce7;
            color: #166534;
        }

        .tunjangan-example-table th,
        .tunjangan-example-table td {
            white-space: nowrap;
        }

        .tunjangan-required-cell {
            background: #fff7ed !important;
            color: #9a3412;
            font-weight: 800;
        }

        .tunjangan-ref-cell {
            background: #eff6ff !important;
            color: #1d4ed8;
            font-weight: 800;
        }

        .tunjangan-qty-cell {
            background: #ecfdf5 !important;
            color: #047857;
            font-weight: 800;
        }

        .tunjangan-soft-badge {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #bbf7d0;
        }

        @media (max-width: 767.98px) {
            .tunjangan-excel-modal .modal-body {
                padding-left: 18px !important;
                padding-right: 18px !important;
            }

            .tunjangan-meta-grid {
                grid-template-columns: 1fr;
            }

            .tunjangan-excel-hero .d-flex,
            .tunjangan-action-panel .d-flex,
            .tunjangan-excel-footer {
                align-items: stretch !important;
                flex-direction: column;
            }

            .tunjangan-excel-footer .btn {
                width: 100%;
            }
        }
    </style>
@endpush

<!-- Modal Upload Excel Tunjangan Pegawai -->
<div class="modal fade tunjangan-excel-modal" id="tunjanganPegawaiModalExcell" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

            <!-- HEADER -->
            <div class="tunjangan-excel-hero px-4 px-md-5 py-4 text-white">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="tunjangan-excel-icon">
                            <i data-feather="file-text"></i>
                        </div>

                        <div>
                            <div class="text-uppercase small fw-semibold opacity-75 mb-1">Import Excel</div>
                            <h4 class="fw-bold mb-1">Upload Tunjangan Pegawai</h4>
                            <p class="mb-0 opacity-75">
                                Masukkan data tunjangan pegawai dengan format template resmi sistem.
                            </p>
                        </div>
                    </div>

                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Tutup"></button>
                </div>

                <div class="tunjangan-meta-grid mt-4">
                    <div class="tunjangan-meta-item">
                        <div class="small opacity-75">Format file</div>
                        <div class="fw-bold">XLS / XLSX</div>
                    </div>
                    <div class="tunjangan-meta-item">
                        <div class="small opacity-75">Ukuran maksimal</div>
                        <div class="fw-bold">5 MB</div>
                    </div>
                    <div class="tunjangan-meta-item">
                        <div class="small opacity-75">Kolom utama</div>
                        <div class="fw-bold">Tunjangan_id, referensi_id, qty</div>
                    </div>
                </div>
            </div>

            <div class="modal-body px-5 py-4">

                <form id="tunjanganPegawaiExcellForm" enctype="multipart/form-data">
                    @csrf

                    <!-- STEP -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="tunjangan-step is-template">
                                <div class="tunjangan-step-number">1</div>
                                <div>
                                    <div class="fw-bold">Download Template</div>
                                    <small class="text-muted">Gunakan file resmi agar struktur kolom tetap valid.</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="tunjangan-step is-fill">
                                <div class="tunjangan-step-number">2</div>
                                <div>
                                    <div class="fw-bold">Isi Data Tunjangan</div>
                                    <small class="text-muted">Satu baris mewakili satu tunjangan untuk satu pegawai.</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="tunjangan-step is-upload">
                                <div class="tunjangan-step-number">3</div>
                                <div>
                                    <div class="fw-bold">Upload File</div>
                                    <small class="text-muted">Sistem membaca kode lalu mengonversinya ke ID.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DOWNLOAD + UPLOAD -->
                    <div class="row g-3 mb-4">
                        <div class="col-lg-5">
                            <div class="tunjangan-action-panel tunjangan-template-panel h-100 p-4">
                                <div class="d-flex align-items-start justify-content-between gap-3">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge tunjangan-soft-badge">
                                                Disarankan
                                            </span>
                                            <span class="small text-muted">Template terbaru</span>
                                        </div>

                                        <h6 class="fw-bold mb-1">Gunakan Template Resmi</h6>
                                        <p class="small text-muted mb-3">
                                            Jangan mengubah nama kolom. Isi data pegawai, kode tunjangan,
                                            referensi, dan qty sesuai kebutuhan.
                                        </p>
                                    </div>
                                </div>

                                <button type="button" id="downloadTemplateBtn"
                                    class="btn btn-dark px-3 shadow-sm w-100">
                                    <i data-feather="download" class="me-1"></i>
                                    Download Template
                                </button>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="tunjangan-action-panel h-100 p-4">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <div>
                                        <h6 class="fw-bold mb-1">Upload File Excel</h6>
                                        <small class="text-muted">Pilih file .xls atau .xlsx yang sudah diisi.</small>
                                    </div>
                                    <i data-feather="upload-cloud" class="text-primary"></i>
                                </div>

                                <input type="file" id="myDropify" name="file" class="dropify"
                                    accept=".xls,.xlsx" data-height="188" data-max-file-size="5M"
                                    data-allowed-file-extensions="xls xlsx"
                                    data-messages-default="Klik atau drag file Excel ke sini"
                                    data-messages-replace="Klik atau drag untuk mengganti file"
                                    data-messages-remove="Hapus" data-messages-error="File tidak valid" />

                                <div class="small text-primary fw-semibold mt-2">
                                    File akan divalidasi saat tombol Upload Data ditekan.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- INFO -->
                    <div class="row g-3 mb-4">

                        <!-- PANDUAN -->
                        <div class="col-lg-5">
                            <div class="tunjangan-info-panel h-100 p-4">

                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <i data-feather="check-circle" class="text-success"></i>
                                    <h6 class="fw-bold mb-0 text-dark">Panduan Pengisian</h6>
                                </div>

                                <div class="tunjangan-rule-list">
                                    <div class="tunjangan-rule-item">
                                        <div class="tunjangan-rule-icon">
                                            <i data-feather="users"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">Pegawai dengan banyak tunjangan</div>
                                            <small class="text-muted">
                                                Buat beberapa baris dengan NIK yang sama.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="tunjangan-rule-item">
                                        <div class="tunjangan-rule-icon">
                                            <i data-feather="hash"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">Isi Tunjangan_id dengan kode</div>
                                            <small class="text-muted">
                                                Gunakan kode seperti TJ001 atau TJ005, bukan ID database.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="tunjangan-rule-item">
                                        <div class="tunjangan-rule-icon">
                                            <i data-feather="briefcase"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">Referensi untuk TJ001 dan TJ005</div>
                                            <small class="text-muted">
                                                TJ001 memakai kode jabatan, TJ005 memakai kode profesi.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="tunjangan-rule-item">
                                        <div class="tunjangan-rule-icon">
                                            <i data-feather="plus-circle"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">Qty hanya untuk TJ002</div>
                                            <small class="text-muted">
                                                Isi jumlah anak pada kolom qty. TJ003 dan TJ004 dihitung otomatis.
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-warning small mb-0 mt-3">
                                    <strong>Penting:</strong> baris dengan kode tunjangan kosong tidak akan ikut diimport.
                                </div>

                            </div>
                        </div>

                        <!-- REFERENSI -->
                        <div class="col-lg-7">
                            <div class="tunjangan-info-panel h-100 p-4">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <i data-feather="list" class="text-primary"></i>
                                        <h6 class="fw-bold mb-0 text-dark">Referensi Tunjangan</h6>
                                    </div>
                                    <span class="badge bg-light text-secondary border">
                                        {{ count($tunjangan) }} kode
                                    </span>
                                </div>

                                <div class="tunjangan-reference-table mb-3">
                                    <table class="table table-sm table-hover align-middle text-center mb-0">
                                        <thead>
                                            <tr>
                                                <th>Kode</th>
                                                <th class="text-start">Tunjangan</th>
                                                <th>Tipe</th>
                                                <th class="text-start">Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($tunjangan as $t)
                                                <tr>
                                                    <td>
                                                        <span class="tunjangan-code-pill">{{ $t->kode }}</span>
                                                    </td>
                                                    <td class="text-start">{{ $t->nama }}</td>
                                                    <td>
                                                        @if (in_array($t->kode, ["TJ001", "TJ005"]))
                                                            <span class="tunjangan-type-badge tunjangan-type-reference">
                                                                Referensi
                                                            </span>
                                                        @elseif($t->kode == "TJ002")
                                                            <span class="tunjangan-type-badge tunjangan-type-qty">
                                                                Qty
                                                            </span>
                                                        @elseif($t->kode == "TJ003")
                                                            <span class="tunjangan-type-badge tunjangan-type-percent">
                                                                Persen
                                                            </span>
                                                        @elseif($t->kode == "TJ004")
                                                            <span class="tunjangan-type-badge tunjangan-type-year">
                                                                Per Tahun
                                                            </span>
                                                        @else
                                                            <span class="badge bg-light text-secondary border">
                                                                Otomatis
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="text-start">
                                                        @if ($t->kode == "TJ001")
                                                            Gunakan kode jabatan
                                                        @elseif($t->kode == "TJ005")
                                                            Gunakan kode profesi
                                                        @elseif($t->kode == "TJ002")
                                                            Isi jumlah anak
                                                        @else
                                                            Otomatis
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-muted py-4">
                                                        Referensi tunjangan belum tersedia.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <h6 class="fw-bold text-primary mb-0">Referensi Jabatan</h6>
                                            <span class="badge bg-light text-secondary border">TJ001</span>
                                        </div>

                                        <div class="tunjangan-reference-table is-small">
                                            <table class="table table-sm table-bordered text-center mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Kode</th>
                                                        <th>Nama Jabatan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($jabatan as $j)
                                                        <tr>
                                                            <td class="fw-semibold">{{ $j->kode }}</td>
                                                            <td>{{ $j->nama }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="2" class="text-muted py-3">
                                                                Data jabatan kosong.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <h6 class="fw-bold text-success mb-0">Referensi Profesi</h6>
                                            <span class="badge bg-light text-secondary border">TJ005</span>
                                        </div>

                                        <div class="tunjangan-reference-table is-small">
                                            <table class="table table-sm table-bordered text-center mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Kode</th>
                                                        <th>Nama Profesi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($profesi as $p)
                                                        <tr>
                                                            <td class="fw-semibold">{{ $p->kode }}</td>
                                                            <td>{{ $p->nama }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="2" class="text-muted py-3">
                                                                Data profesi kosong.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>

                    <!-- CONTOH -->
                    <div class="tunjangan-info-panel p-4 mb-4">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <i data-feather="grid" class="text-success"></i>
                                <h6 class="fw-bold mb-0 text-dark">Contoh Isi Excel</h6>
                            </div>
                            <span class="small text-muted">Ulangi NIK untuk setiap tunjangan tambahan.</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm text-center align-middle mb-0 tunjangan-example-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>NIK</th>
                                        <th>Nama</th>
                                        <th>Jabatan</th>
                                        <th>Status</th>
                                        <th>Masa Kerja</th>
                                        <th>Tunjangan_id</th>
                                        <th>referensi_id</th>
                                        <th>qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>123456</td>
                                        <td>Ahmad</td>
                                        <td>Kepala</td>
                                        <td>T</td>
                                        <td>10 Tahun</td>
                                        <td class="tunjangan-required-cell">TJ001</td>
                                        <td class="tunjangan-ref-cell">JBT001</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>123456</td>
                                        <td>Ahmad</td>
                                        <td>Kepala</td>
                                        <td>T</td>
                                        <td>10 Tahun</td>
                                        <td class="tunjangan-required-cell">TJ002</td>
                                        <td></td>
                                        <td class="tunjangan-qty-cell">2</td>
                                    </tr>
                                    <tr>
                                        <td>123456</td>
                                        <td>Ahmad</td>
                                        <td>Kepala</td>
                                        <td>T</td>
                                        <td>10 Tahun</td>
                                        <td class="tunjangan-required-cell">TJ003</td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>123456</td>
                                        <td>Ahmad</td>
                                        <td>Kepala</td>
                                        <td>T</td>
                                        <td>10 Tahun</td>
                                        <td class="tunjangan-required-cell">TJ004</td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>123456</td>
                                        <td>Ahmad</td>
                                        <td>Kepala</td>
                                        <td>T</td>
                                        <td>10 Tahun</td>
                                        <td class="tunjangan-required-cell">TJ005</td>
                                        <td class="tunjangan-ref-cell">PRF001</td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div class="tunjangan-excel-footer d-flex justify-content-between gap-2">
                        <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">
                            Tutup
                        </button>

                        <button type="button" id="submitFormExcell" class="btn btn-dark px-4 shadow">
                            <i data-feather="upload" class="me-1"></i>
                            Upload Data
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>
