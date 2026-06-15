@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")
    @include("template.AddOn.select2")
    @include("simrs.partials.masterFinanceStyle")
    <style>
        .premi-expand-panel {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 14px;
        }

        .premi-expand-head,
        .premi-mapping-card {
            align-items: center;
            display: flex;
            gap: 12px;
            justify-content: space-between;
        }

        .premi-expand-head {
            margin-bottom: 12px;
        }

        .premi-panel-actions,
        .premi-card-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .premi-total-pill {
            background: #fff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            color: #1d4ed8;
            min-width: 90px;
            padding: 6px 10px;
            text-align: right;
        }

        .premi-list-grid {
            display: grid;
            gap: 10px;
        }

        .premi-employee-summary {
            background: #fff;
            border: 1px solid #dbeafe;
            border-radius: 12px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .05);
            margin-bottom: 12px;
            overflow: hidden;
        }

        .premi-employee-summary-head {
            align-items: center;
            background: linear-gradient(135deg, #eff6ff, #f8fafc);
            border-bottom: 1px solid #dbeafe;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 14px 16px;
        }

        .premi-employee-heading {
            align-items: center;
            display: flex;
            gap: 10px;
            min-width: 0;
        }

        .premi-employee-heading-icon {
            background: #2563eb;
            border-radius: 10px;
            color: #fff;
            display: grid;
            flex: 0 0 auto;
            font-size: 20px;
            height: 42px;
            place-items: center;
            width: 42px;
        }

        .premi-employee-summary-body {
            padding: 14px 16px 16px;
        }

        .premi-employee-stats {
            display: grid;
            gap: 9px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-bottom: 12px;
        }

        .premi-employee-stat {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            display: flex;
            gap: 9px;
            min-width: 0;
            padding: 9px 11px;
        }

        .premi-employee-stat i {
            color: #2563eb;
            font-size: 20px;
        }

        .premi-employee-stat-value {
            color: #0f172a;
            font-size: 16px;
            font-weight: 800;
            line-height: 1.1;
        }

        .premi-employee-stat-label {
            color: #64748b;
            font-size: 11px;
            margin-top: 2px;
        }

        .premi-employee-summary-tools {
            display: grid;
            gap: 8px;
            grid-template-columns: minmax(0, 1fr) 180px;
            margin-bottom: 12px;
        }

        .premi-employee-summary-tools .input-group-text,
        .premi-employee-summary-tools .form-control,
        .premi-employee-summary-tools .form-select {
            height: 38px;
        }

        .premi-employee-list {
            display: grid;
            gap: 9px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            max-height: 360px;
            overflow-y: auto;
            padding: 1px 4px 1px 1px;
        }

        .premi-employee-card {
            align-items: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            display: flex;
            gap: 10px;
            min-width: 0;
            padding: 10px;
            transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
        }

        .premi-employee-card:hover {
            border-color: #93c5fd;
            box-shadow: 0 6px 16px rgba(37, 99, 235, .08);
            transform: translateY(-1px);
        }

        .premi-employee-avatar {
            align-items: center;
            background: linear-gradient(135deg, #dbeafe, #ede9fe);
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            color: #1d4ed8;
            display: flex;
            flex: 0 0 auto;
            font-size: 13px;
            font-weight: 900;
            height: 42px;
            justify-content: center;
            letter-spacing: .3px;
            width: 42px;
        }

        .premi-employee-info {
            flex: 1;
            min-width: 0;
        }

        .premi-employee-name {
            color: #0f172a;
            font-size: 13px;
            font-weight: 800;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .premi-employee-nik {
            color: #64748b;
            font-size: 11px;
            margin-top: 1px;
        }

        .premi-employee-meta {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 6px;
        }

        .premi-employee-job {
            color: #475569;
            font-size: 11px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .premi-status-badge {
            border-radius: 999px;
            display: inline-flex;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .2px;
            padding: 3px 6px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .premi-status-badge.tetap {
            background: #dcfce7;
            color: #166534;
        }

        .premi-status-badge.kontrak {
            background: #fef3c7;
            color: #92400e;
        }

        .premi-status-badge.casual {
            background: #f3e8ff;
            color: #7e22ce;
        }

        .premi-status-badge.netral {
            background: #e2e8f0;
            color: #475569;
        }

        .premi-employee-empty {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            color: #64748b;
            grid-column: 1 / -1;
            padding: 22px;
            text-align: center;
        }

        .premi-employee-picker-overview {
            display: grid;
            gap: 9px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-bottom: 12px;
        }

        .premi-picker-stat {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            padding: 10px 12px;
        }

        .premi-picker-stat strong {
            color: #0f172a;
            display: block;
            font-size: 18px;
            line-height: 1;
        }

        .premi-picker-stat span {
            color: #64748b;
            display: block;
            font-size: 11px;
            margin-top: 4px;
        }

        .premi-employee-picker-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(0, 1.4fr) minmax(270px, .8fr);
        }

        .premi-picker-pane {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            min-width: 0;
            overflow: hidden;
        }

        .premi-picker-pane-head {
            align-items: center;
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 8px;
            justify-content: space-between;
            padding: 10px 12px;
        }

        .premi-picker-pane-title {
            color: #334155;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .premi-picker-toolbar {
            display: grid;
            gap: 8px;
            grid-template-columns: minmax(0, 1fr) 145px;
            padding: 10px;
        }

        .premi-picker-list,
        .premi-picker-selected-list {
            max-height: 370px;
            overflow-y: auto;
            padding: 0 10px 10px;
        }

        .premi-picker-row {
            align-items: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            cursor: pointer;
            display: grid;
            gap: 9px;
            grid-template-columns: auto 38px minmax(0, 1fr) auto;
            margin-bottom: 7px;
            padding: 8px 9px;
            transition: background .15s ease, border-color .15s ease;
        }

        .premi-picker-row:hover,
        .premi-picker-row.selected {
            background: #eff6ff;
            border-color: #93c5fd;
        }

        .premi-picker-row .form-check-input {
            cursor: pointer;
            margin: 0;
        }

        .premi-picker-row .premi-employee-avatar {
            border-radius: 8px;
            height: 38px;
            width: 38px;
        }

        .premi-picker-selected-item {
            align-items: center;
            background: #fff;
            border: 1px solid #dbeafe;
            border-radius: 9px;
            display: flex;
            gap: 8px;
            margin-bottom: 7px;
            min-width: 0;
            padding: 8px;
        }

        .premi-picker-selected-item .premi-employee-avatar {
            border-radius: 8px;
            height: 36px;
            width: 36px;
        }

        .premi-picker-remove {
            align-items: center;
            background: #fff1f2;
            border: 0;
            border-radius: 7px;
            color: #be123c;
            display: flex;
            flex: 0 0 auto;
            height: 30px;
            justify-content: center;
            width: 30px;
        }

        .premi-mapping-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
        }

        .premi-card-icon {
            background: #f3e8ff;
            border-radius: 8px;
            color: #7e22ce;
            display: grid;
            flex: 0 0 auto;
            font-size: 20px;
            height: 38px;
            place-items: center;
            width: 38px;
        }

        .premi-card-meta {
            flex: 1;
            min-width: 0;
        }

        .premi-card-title {
            color: #111827;
            font-weight: 700;
            word-break: break-word;
        }

        .premi-value-grid {
            display: grid;
            flex: 0 0 270px;
            gap: 7px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .premi-value-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            min-width: 0;
            padding: 7px 9px;
            text-align: right;
        }

        .premi-value-item.umum {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .premi-value-item.bpjs {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .premi-value-label {
            color: #64748b;
            display: block;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .premi-value-number {
            color: #0f172a;
            display: block;
            font-size: 14px;
            font-weight: 900;
            margin-top: 2px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .premi-kind-badge {
            border-radius: 999px;
            display: inline-flex;
            font-size: 10px;
            font-weight: 800;
            margin-top: 5px;
            padding: 3px 7px;
            text-transform: uppercase;
        }

        .premi-kind-badge.persen {
            background: #dcfce7;
            color: #166534;
        }

        .premi-kind-badge.nominal {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .premi-inline-editor {
            background: #fff;
            border: 1px solid #93c5fd;
            border-radius: 12px;
            box-shadow: 0 12px 28px rgba(37, 99, 235, .1);
            margin-bottom: 12px;
            overflow: visible;
        }

        .premi-inline-head {
            align-items: center;
            background: linear-gradient(135deg, #eff6ff, #f5f3ff);
            border-bottom: 1px solid #dbeafe;
            border-radius: 11px 11px 0 0;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 12px 14px;
        }

        .premi-inline-heading {
            align-items: center;
            display: flex;
            gap: 10px;
            min-width: 0;
        }

        .premi-inline-icon {
            background: #2563eb;
            border-radius: 8px;
            color: #fff;
            display: grid;
            flex: 0 0 auto;
            font-size: 18px;
            height: 36px;
            place-items: center;
            width: 36px;
        }

        .premi-inline-title {
            color: #1e3a8a;
            font-weight: 800;
            line-height: 1.2;
        }

        .premi-inline-subtitle {
            color: #64748b;
            font-size: 12px;
            margin-top: 2px;
        }

        .premi-inline-mode {
            background: #fff;
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 9px;
            white-space: nowrap;
        }

        .premi-inline-body {
            padding: 12px 14px;
        }

        .premi-inline-field {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .premi-inline-field .form-label {
            color: #334155;
            display: block !important;
            font-size: 12px;
            font-weight: 800;
            line-height: 1.2;
            margin: 0 0 7px !important;
            position: static !important;
            text-transform: uppercase;
            width: 100%;
        }

        .premi-inline-control {
            display: block;
            min-width: 0;
            width: 100%;
        }

        .premi-inline-field > .select2-container,
        .premi-inline-control > .select2-container,
        .premi-inline-field .input-group {
            display: block;
            margin: 0 !important;
            max-width: 100%;
            position: relative !important;
            vertical-align: top;
            width: 100% !important;
        }

        .premi-inline-field .input-group {
            display: flex;
        }

        .premi-inline-field .select2-container .select2-selection--single {
            align-items: center;
            background: #fff;
            border-color: #cbd5e1;
            display: flex;
            height: 40px !important;
            min-height: 40px;
            width: 100%;
        }

        .premi-employee-tools {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 9px;
        }

        .premi-inline-field .select2-selection__rendered {
            line-height: 38px !important;
            max-width: calc(100% - 34px);
            overflow: hidden;
            padding-left: 12px !important;
            padding-right: 8px !important;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .premi-inline-field .select2-selection__arrow {
            height: 38px !important;
            right: 5px !important;
        }

        .premi-inline-field .form-control,
        .premi-inline-field .input-group-text {
            height: 40px;
        }

        .premi-inline-help {
            color: #64748b;
            display: block;
            font-size: 11px;
            line-height: 1.35;
            margin-top: 7px;
        }

        .premi-inline-actions {
            align-items: center;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            padding: 11px 14px;
        }

        .premi-inline-actions .btn {
            align-items: center;
            display: inline-flex;
            gap: 6px;
            justify-content: center;
            min-width: 96px;
        }

        .premi-empty-state {
            background: #fff;
            border: 1px dashed #d1d5db;
            border-radius: 8px;
            color: #6b7280;
            padding: 18px;
            text-align: center;
        }

        @media (max-width: 991.98px) {
            .premi-employee-picker-grid {
                grid-template-columns: 1fr;
            }

            .premi-picker-selected-list {
                max-height: 280px;
            }
        }

        @media (max-width: 767.98px) {
            .premi-expand-head,
            .premi-mapping-card {
                align-items: stretch;
                flex-direction: column;
            }

            .premi-panel-actions,
            .premi-card-actions {
                justify-content: flex-start;
            }

            .premi-inline-head {
                align-items: flex-start;
            }

            .premi-inline-actions {
                align-items: stretch;
                flex-direction: column-reverse;
            }

            .premi-inline-actions .btn {
                width: 100%;
            }

            .premi-employee-summary-head {
                align-items: flex-start;
                flex-direction: column;
            }

            .premi-employee-stats,
            .premi-employee-picker-overview {
                grid-template-columns: 1fr;
            }

            .premi-employee-summary-tools,
            .premi-employee-picker-grid,
            .premi-picker-toolbar {
                grid-template-columns: 1fr;
            }

            .premi-employee-list {
                grid-template-columns: 1fr;
            }

            .premi-value-grid {
                flex-basis: auto;
                grid-template-columns: 1fr;
                text-align: left;
                width: 100%;
            }

            .premi-value-item {
                text-align: left;
            }
        }
    </style>
@endpush

@section("content")
    <div class="finance-master-page">
        @include("simrs.masterData.mapping.mappingPremi.modalMain")

        <nav class="page-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route("masterData.mapping") }}">Mapping</a></li>
                <li class="breadcrumb-item active" aria-current="page">Mapping Premi</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card master-card">
                    <div class="card-body p-0">
                        <div class="master-panel">
                            <div class="master-title-group">
                                <div class="master-icon master-icon-primary">
                                    <i class="mdi mdi-shield-check"></i>
                                </div>

                                <div>
                                    <div class="master-eyebrow">Master Keuangan</div>
                                    <h5 class="master-title">Mapping Premi</h5>
                                    <p class="master-subtitle mb-0">
                                        Hubungkan premi dengan pegawai penerima, tindakan, serta nilai yang berlaku.
                                    </p>
                                </div>
                            </div>

                            <div class="master-actions">
                                <div class="input-group input-group-sm master-search">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="mdi mdi-magnify text-muted"></i>
                                    </span>
                                    <input type="text" id="searchPremi" class="form-control border-start-0"
                                        placeholder="Cari jenis premi...">
                                </div>

                                <button type="button" id="btnTambahPremi"
                                    class="btn master-action-btn master-action-primary" data-bs-toggle="modal"
                                    data-bs-target="#premiModal">
                                    <i class="mdi mdi-plus"></i>
                                    <span>Tambah</span>
                                </button>
                            </div>
                        </div>

                        <div class="master-table-wrap">
                            <div class="table-responsive">
                                <table id="tablePremi" class="table table-hover align-middle master-table">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="30"></th>
                                            <th>No</th>
                                            <th>Kode</th>
                                            <th>Jenis Premi</th>
                                            <th class="text-end">Mapping</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("scripts")
    @include("simrs.masterData.mapping.mappingPremi.jsMain")
@endpush
