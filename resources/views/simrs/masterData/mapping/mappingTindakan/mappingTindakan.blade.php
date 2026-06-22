@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")
    @include("template.AddOn.select2")
    @include("template.AddOn.dropify")
    @include("simrs.partials.masterFinanceStyle")
    <style>
        .skor-expand-panel {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 14px;
        }

        .skor-expand-head,
        .skor-score-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .skor-expand-head {
            margin-bottom: 12px;
        }

        .skor-panel-title {
            min-width: 0;
        }

        .skor-panel-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .skor-total-pill {
            min-width: 84px;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            background: #fff;
            color: #1d4ed8;
            padding: 6px 10px;
            text-align: right;
            font-weight: 700;
        }

        .skor-inline-editor {
            background: #fff;
            border: 1px dashed #93c5fd;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 12px;
        }

        .skor-list-grid {
            display: grid;
            gap: 10px;
        }

        .skor-score-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
        }

        .skor-score-icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: #fff7ed;
            color: #c2410c;
            flex: 0 0 auto;
            font-size: 20px;
        }

        .skor-score-meta {
            min-width: 0;
            flex: 1;
        }

        .skor-score-title {
            font-weight: 700;
            color: #111827;
            word-break: break-word;
        }

        .skor-score-subtitle {
            color: #6b7280;
            font-size: 12px;
        }

        .skor-bobot-box {
            min-width: 76px;
            text-align: right;
        }

        .skor-bobot-value {
            font-size: 20px;
            line-height: 1;
            font-weight: 800;
            color: #047857;
        }

        .skor-score-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex: 0 0 auto;
        }

        .skor-empty-state {
            border: 1px dashed #d1d5db;
            background: #fff;
            border-radius: 8px;
            padding: 18px;
            text-align: center;
            color: #6b7280;
        }

        .tindakan-detail-tools {
            align-items: center;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            margin-bottom: 12px;
            padding: 10px;
        }

        .tindakan-detail-search {
            width: min(420px, 100%);
        }

        .tindakan-detail-count {
            border: 1px solid #dbeafe;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 700;
            padding: 5px 10px;
            white-space: nowrap;
        }

        .tindakan-expand-scroll {
            max-height: 380px;
            overflow: auto;
            padding-right: 6px;
        }

        .tindakan-expand-scroll::-webkit-scrollbar {
            width: 8px;
        }

        .tindakan-expand-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }

        .tindakan-filter-empty {
            border: 1px dashed #bfdbfe;
            border-radius: 8px;
            background: #eff6ff;
            color: #1d4ed8;
            padding: 16px;
            text-align: center;
        }

        .tindakan-bulk-tools {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .tindakan-bulk-tools .form-check {
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            background: #fff;
            padding: 5px 12px 5px 34px;
        }

        .tindakan-bulk-selected {
            border: 1px solid #bbf7d0;
            border-radius: 999px;
            background: #f0fdf4;
            color: #166534;
            font-size: 12px;
            font-weight: 700;
            padding: 5px 10px;
            white-space: nowrap;
        }

        .tindakan-mapped-check-wrap {
            align-self: center;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f8fafc;
            display: grid;
            flex: 0 0 auto;
            height: 38px;
            place-items: center;
            width: 38px;
        }

        .tindakan-mapped-card.is-selected {
            border-color: #86efac;
            background: #f0fdf4;
            box-shadow: 0 8px 18px rgba(34, 197, 94, .1);
        }

        @media (max-width: 767.98px) {

            .skor-expand-head,
            .skor-score-card {
                align-items: stretch;
                flex-direction: column;
            }

            .skor-panel-actions,
            .skor-score-actions {
                justify-content: flex-start;
                width: 100%;
            }

            .skor-bobot-box {
                text-align: left;
                width: 100%;
            }

            .tindakan-detail-tools {
                align-items: stretch;
                flex-direction: column;
            }

            .tindakan-bulk-tools {
                justify-content: flex-start;
            }

            .tindakan-detail-search {
                width: 100%;
            }
        }
    </style>
@endpush

@section("content")
    <div class="finance-master-page">
        @include("simrs.masterData.mapping.mappingTindakan.modalMain")
        <nav class="page-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Mapping Tindakan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Data Tindakan</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card master-card">
                    <div class="card-body p-0">
                        <div class="master-panel">
                            <div class="master-title-group">
                                <div class="master-icon master-icon-primary">
                                    <i class="mdi mdi-medical-bag"></i>
                                </div>

                                <div>
                                    <div class="master-eyebrow">Master Keuangan</div>
                                    <h5 class="master-title">Data Tindakan</h5>
                                    <p class="master-subtitle mb-0">
                                        Kelola mapping tindakan dari data master Tindakan.
                                    </p>
                                </div>
                            </div>

                            <div class="master-actions">
                                <div class="input-group input-group-sm master-search">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="mdi mdi-magnify text-muted"></i>
                                    </span>
                                    <input type="text" id="searchTindakan" class="form-control border-start-0"
                                        placeholder="Cari tindakan...">
                                </div>

                                <button type="button" id="btnTambahTindakan"
                                    class="btn master-action-btn master-action-primary" data-bs-toggle="modal"
                                    data-bs-target="#tindakanModal">
                                    <i class="mdi mdi-plus"></i>
                                    <span>Tambah</span>
                                </button>
                            </div>
                        </div>

                        <div class="master-table-wrap">
                            <div class="table-responsive">
                                <table id="tableTindakan" class="table table-hover align-middle master-table">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="30"></th>
                                            <th>No</th>
                                            <th>Kode</th>
                                            <th>Jenis Tindakan</th>
                                            <th class="text-end">Jumlah Tindakan</th>
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
    @include("simrs.masterData.mapping.mappingTindakan.jsMain")
@endpush
