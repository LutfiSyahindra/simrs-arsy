@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")
    @include("template.AddOn.select2")
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/css/bootstrap-datepicker.min.css">

    <style>
        body {
            background: #f5f7fb;
        }

        .card {
            border: 0;
            border-radius: 14px;
        }

        .card>.card-body {
            padding: 1.25rem;
        }

        .page-breadcrumb .breadcrumb {
            margin-bottom: .75rem;
        }

        .page-breadcrumb .breadcrumb-item a {
            color: #2563eb;
            font-weight: 600;
        }

        .payroll-filter-panel,
        .payroll-summary-card {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
        }

        .payroll-filter-panel {
            padding: 1rem;
        }

        .payroll-header {
            gap: .75rem;
        }

        .payroll-filter-icon,
        .payroll-summary-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 46px;
        }

        .payroll-filter-icon {
            background: #e0ecff;
            color: #2563eb;
        }

        .payroll-stage-tabs {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .45rem;
            padding: .35rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            min-width: 300px;
        }

        .payroll-stage-tabs .nav-link {
            width: 100%;
            min-height: 42px;
            border-radius: 10px;
            color: #475569;
            font-size: .86rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            white-space: normal;
            line-height: 1.2;
        }

        .payroll-stage-tabs .nav-link.active {
            color: #fff;
            background: #2563eb;
            box-shadow: 0 8px 18px rgba(37, 99, 235, .22);
        }

        .payroll-stage-tabs .nav-link:not(.active):hover {
            color: #2563eb;
            background: #eef6ff;
        }

        .payroll-summary-card {
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .payroll-summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(15, 23, 42, .08);
        }

        .payroll-summary-label {
            color: #64748b;
            font-size: .76rem;
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        .payroll-summary-value {
            color: #0f172a;
            font-weight: 700;
            margin-bottom: 0;
            word-break: break-word;
        }

        .payroll-filter-panel .form-control,
        .payroll-filter-panel .input-group-text {
            border-color: #e2e8f0;
        }

        .payroll-filter-panel .form-control:focus {
            border-color: #93c5fd;
            box-shadow: 0 0 0 .15rem rgba(37, 99, 235, .12);
        }

        .payroll-actions {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: .5rem;
        }

        .payroll-table-wrap,
        .table-responsive {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #fff;
            overflow-x: auto;
            overflow-y: hidden;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .04);
        }

        .table {
            margin-bottom: 0 !important;
        }

        .payroll-table {
            min-width: 1040px;
        }

        .payroll-table thead th,
        .table thead th {
            background: #f8fafc !important;
            color: #475569;
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .03em;
            border-bottom: 1px solid #e5e7eb !important;
            padding: .9rem .85rem;
            white-space: nowrap;
        }

        .payroll-table tbody td,
        .table tbody td {
            color: #334155;
            vertical-align: middle;
            border-color: #eef2f7;
            padding: .85rem;
        }

        .payroll-table tbody tr {
            transition: background-color .15s ease;
        }

        .payroll-table tbody tr:hover>* {
            background: #f8fbff !important;
        }

        .table-striped>tbody>tr:nth-of-type(odd)>* {
            background-color: #fbfdff;
        }

        .payroll-table .currency-cell,
        .currency-cell {
            color: #0f172a;
            font-weight: 800;
            white-space: nowrap;
        }

        .payroll-table .employee-name,
        .employee-name {
            display: block;
            color: #0f172a;
            font-weight: 800;
            line-height: 1.25;
        }

        .payroll-table .employee-subtext,
        .employee-subtext {
            display: block;
            color: #94a3b8;
            font-size: .72rem;
            margin-top: .1rem;
        }

        .payroll-status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 78px;
            padding: .35rem .58rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 800;
            line-height: 1;
            white-space: nowrap;
        }

        .payroll-status-badge.is-tetap {
            color: #047857;
            background: #d1fae5;
        }

        .payroll-status-badge.is-kontrak {
            color: #b45309;
            background: #fef3c7;
        }

        .payroll-status-badge.is-unknown {
            color: #64748b;
            background: #f1f5f9;
        }

        .payroll-action-group {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
        }

        .payroll-action-group .btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border-radius: 9px;
        }

        .btn-sm {
            border-radius: 9px;
        }

        .dataTables_wrapper .row {
            align-items: center;
        }

        .dataTables_wrapper .dataTables_length label,
        .dataTables_wrapper .dataTables_filter label {
            color: #64748b;
            font-size: .82rem;
            margin-bottom: 0;
        }

        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            color: #334155;
            font-size: .82rem;
            outline: 0;
        }

        .dataTables_wrapper .dataTables_length select {
            min-height: 34px;
            padding: .25rem 1.75rem .25rem .55rem;
            margin: 0 .35rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            min-height: 34px;
            padding: .35rem .65rem;
            margin-left: .5rem;
        }

        .dataTables_wrapper .dataTables_info {
            color: #64748b;
            font-size: .82rem;
            padding-top: .85rem;
        }

        .dataTables_wrapper .pagination {
            gap: .25rem;
            margin-bottom: 0;
        }

        .dataTables_wrapper .page-link {
            min-width: 34px;
            border: 0;
            border-radius: 9px;
            color: #475569;
            text-align: center;
        }

        .dataTables_wrapper .page-link:hover {
            color: #2563eb;
            background: #eef6ff;
        }

        .dataTables_wrapper .page-item.active .page-link {
            color: #fff;
            background: #2563eb;
            box-shadow: 0 6px 16px rgba(37, 99, 235, .22);
        }

        .dataTables_wrapper .dataTables_processing {
            border: 0;
            border-radius: 12px;
            color: #2563eb;
            font-weight: 700;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .12);
        }

        @media (max-width: 991.98px) {
            .payroll-stage-tabs {
                width: 100%;
                min-width: 0;
            }
        }

        @media (max-width: 767.98px) {
            .card>.card-body {
                padding: 1rem;
            }

            .payroll-filter-panel {
                padding: .85rem;
            }

            .payroll-header {
                align-items: flex-start !important;
            }

            .payroll-filter-icon,
            .payroll-summary-icon {
                width: 40px;
                height: 40px;
                border-radius: 12px;
                flex-basis: 40px;
            }

            .payroll-stage-tabs,
            .payroll-actions {
                grid-template-columns: 1fr;
            }

            .payroll-actions .btn {
                width: 100%;
            }

            .payroll-toolbar {
                align-items: stretch !important;
            }

            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter,
            .dataTables_wrapper .dataTables_info {
                text-align: left !important;
            }

            .dataTables_wrapper .dataTables_filter input {
                width: 100%;
                margin-top: .35rem;
                margin-left: 0;
            }

            .dataTables_wrapper .pagination {
                justify-content: flex-start;
                flex-wrap: wrap;
            }
        }

        .slip-card {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }

        .slip-info-box {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: .85rem;
            background: #fff;
        }

        .slip-info-box span {
            display: block;
            color: #64748b;
            font-size: .75rem;
            margin-bottom: .25rem;
        }

        .slip-info-box strong {
            color: #0f172a;
        }

        .slip-detail-list {
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
        }

        .slip-detail-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: .9rem 1rem;
            border-bottom: 1px solid #eef2f7;
        }

        .slip-detail-row:last-child {
            border-bottom: 0;
        }

        .slip-detail-row span {
            color: #64748b;
        }

        .slip-detail-row strong {
            color: #0f172a;
            white-space: nowrap;
        }

        .slip-detail-row.total {
            background: #eff6ff;
        }

        .slip-detail-row.total span,
        .slip-detail-row.total strong {
            color: #1d4ed8;
            font-weight: 800;
        }

        .slip-section-title {
            color: #475569;
            font-size: .78rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .03em;
            margin-bottom: .5rem;
        }

        .slip-allowance-box {
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
        }

        .slip-allowance-box .slip-section-title {
            padding: .8rem 1rem 0;
        }

        .slip-empty-row {
            color: #94a3b8;
            padding: .9rem 1rem;
            font-size: .86rem;
        }
    </style>
@endpush

@section("content")
    @include("simrs.backOffice.keuangan.penggajian.modalDetail")
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Keuangan</a></li>
            <li class="breadcrumb-item active" aria-current="page">Penggajian</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3 payroll-filter-panel">
                        <div
                            class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center payroll-header mb-3">
                            <div class="d-flex align-items-center">
                                <div class="payroll-filter-icon me-3">
                                    <i class="mdi mdi-cash-multiple mdi-24px"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold text-primary mb-0">Generate Penggajian</h5>
                                    <small class="text-muted">Data gaji pegawai berdasarkan periode dan tahap
                                        pembayaran</small>
                                </div>
                            </div>

                            <ul class="nav nav-pills payroll-stage-tabs" id="payrollStageTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button type="button" class="nav-link active" id="tabTahap1" data-bs-toggle="pill"
                                        data-bs-target="#paneTahap1" data-tahap="1" role="tab">
                                        <i class="mdi mdi-numeric-1-circle-outline"></i>
                                        Gaji Tahap 1
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button type="button" class="nav-link" id="tabTahap2" data-bs-toggle="pill"
                                        data-bs-target="#paneTahap2" data-tahap="2" role="tab">
                                        <i class="mdi mdi-numeric-2-circle-outline"></i>
                                        Gaji Tahap 2
                                    </button>
                                </li>
                            </ul>

                            <input type="hidden" id="tahapGaji" value="1">
                        </div>

                        <div class="row g-2 g-md-3 align-items-end">
                            <div class="col-12 col-sm-6 col-lg-3">
                                <label class="form-label small text-muted mb-1">Periode</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">
                                        <i class="mdi mdi-calendar-month text-muted"></i>
                                    </span>
                                    <input type="text" id="periodeGaji" class="form-control" value="{{ date("Y-m") }}"
                                        autocomplete="off">
                                </div>
                            </div>

                            <div class="col-12 col-sm-6 col-lg-5">
                                <label class="form-label small text-muted mb-1">Cari</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">
                                        <i class="mdi mdi-magnify text-muted"></i>
                                    </span>
                                    <input type="text" id="searchPenggajian" class="form-control"
                                        placeholder="Cari nama / NIK / jabatan...">
                                </div>
                            </div>

                            <div class="col-12 col-lg-4">
                                <div class="payroll-actions">
                                    <button type="button" id="btnGenerateGaji" class="btn btn-primary btn-sm">
                                        <i class="mdi mdi-calculator-variant-outline me-1"></i>
                                        Generate
                                    </button>

                                    <button type="button" id="btnRefreshPenggajian" class="btn btn-light btn-sm"
                                        title="Refresh">
                                        <i class="mdi mdi-refresh"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-6 col-xl-3">
                            <div class="payroll-summary-card p-3 h-100">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="payroll-summary-label">Total Gaji</div>
                                        <h5 class="payroll-summary-value" id="summaryTotalGaji">Rp 0</h5>
                                        <small class="text-muted" id="summaryPeriode">-</small>
                                    </div>
                                    <div class="payroll-summary-icon bg-primary-subtle text-primary">
                                        <i class="mdi mdi-wallet-outline mdi-24px"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-xl-3">
                            <div class="payroll-summary-card p-3 h-100">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="payroll-summary-label">Pegawai</div>
                                        <h5 class="payroll-summary-value" id="summaryPegawai">0</h5>
                                        <small class="text-muted" id="summaryStatusPegawai">0 tetap / 0 kontrak</small>
                                    </div>
                                    <div class="payroll-summary-icon bg-success-subtle text-success">
                                        <i class="mdi mdi-account-group-outline mdi-24px"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-xl-3">
                            <div class="payroll-summary-card p-3 h-100">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="payroll-summary-label">Gapok Dibayar</div>
                                        <h5 class="payroll-summary-value" id="summaryGapok">Rp 0</h5>
                                        <small class="text-muted">Tahap terpilih</small>
                                    </div>
                                    <div class="payroll-summary-icon bg-warning-subtle text-warning">
                                        <i class="mdi mdi-cash-check mdi-24px"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-xl-3">
                            <div class="payroll-summary-card p-3 h-100">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="payroll-summary-label">Tunjangan</div>
                                        <h5 class="payroll-summary-value" id="summaryTunjangan">Rp 0</h5>
                                        <small class="text-muted">Masuk komponen gaji</small>
                                    </div>
                                    <div class="payroll-summary-icon bg-info-subtle text-info">
                                        <i class="mdi mdi-plus-box-multiple-outline mdi-24px"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2 payroll-toolbar">
                        <div>
                            <h6 class="fw-bold mb-0">Hasil Generate</h6>
                            <small class="text-muted">Data gaji pegawai per periode dan tahap</small>
                        </div>
                    </div>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="paneTahap1" role="tabpanel">
                            <div class="payroll-table-wrap">
                                <table id="tablePenggajianTahap1"
                                    class="table table-hover align-middle w-100 payroll-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">No</th>
                                            <th>Nama</th>
                                            <th>Jabatan</th>
                                            <th>Status</th>
                                            <th class="text-end">Gaji Pokok</th>
                                            <th class="text-end">Gapok Dibayar</th>
                                            <th class="text-end">Tunjangan</th>
                                            <th class="text-end">Total</th>
                                            <th>Formula</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="paneTahap2" role="tabpanel">
                            <div class="payroll-coming-soon text-center py-5">
                                <div class="payroll-summary-icon bg-info-subtle text-info mx-auto mb-3">
                                    <i class="mdi mdi-timer-sand mdi-24px"></i>
                                </div>
                                <h6 class="fw-bold mb-1">Gaji Tahap 2 Coming Soon</h6>
                                <small class="text-muted">Fitur dan database tahap 2 belum tersedia.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("scripts")
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/js/bootstrap-datepicker.min.js">
    </script>
    @include("simrs.backOffice.keuangan.penggajian.jsMain")

    <script>
        $(document).on('click', '#payrollStageTabs .nav-link', function() {
            const tahap = $(this).data('tahap');

            $('#tahapGaji').val(tahap).trigger('change');

            setTimeout(function() {
                $.fn.dataTable
                    .tables({
                        visible: true,
                        api: true
                    })
                    .columns.adjust();
            }, 150);
        });

        function getActivePayrollTableId() {
            return $('#tahapGaji').val() == '2' ?
                '#tablePenggajianTahap2' :
                '#tablePenggajianTahap1';
        }
    </script>
@endpush
