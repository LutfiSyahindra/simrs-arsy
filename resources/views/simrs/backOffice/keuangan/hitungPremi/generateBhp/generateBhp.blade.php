@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/css/bootstrap-datepicker.min.css">

    <style>
        body {
            background: #f5f7fb;
        }

        .bhp-card {
            border: 0;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .06);
        }

        .bhp-filter-panel,
        .bhp-summary-card {
            background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            border-radius: 14px;
        }

        .bhp-filter-panel {
            padding: 1rem;
        }

        .bhp-icon {
            align-items: center;
            background: #e0ecff;
            border-radius: 14px;
            color: #2563eb;
            display: flex;
            flex: 0 0 46px;
            height: 46px;
            justify-content: center;
            width: 46px;
        }

        .bhp-type-badge {
            align-items: center;
            background: #dcfce7;
            border-radius: 999px;
            color: #15803d;
            display: inline-flex;
            font-size: .72rem;
            font-weight: 800;
            gap: .3rem;
            padding: .4rem .65rem;
            text-transform: uppercase;
        }

        .bhp-type-badge.is-bpjs {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .bhp-type-switch {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            display: inline-grid;
            gap: .35rem;
            grid-template-columns: repeat(2, minmax(110px, 1fr));
            padding: .3rem;
        }

        .bhp-type-switch .btn {
            border: 0;
            border-radius: 9px;
            color: #64748b;
            font-weight: 700;
        }

        .bhp-type-switch .btn.active {
            background: #2563eb;
            box-shadow: 0 6px 14px rgba(37, 99, 235, .2);
            color: #fff;
        }

        .bhp-summary-card {
            height: 100%;
            padding: 1rem;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .bhp-summary-card:hover {
            box-shadow: 0 12px 28px rgba(15, 23, 42, .08);
            transform: translateY(-2px);
        }

        .bhp-summary-label {
            color: #64748b;
            font-size: .74rem;
            font-weight: 700;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .bhp-summary-value {
            color: #0f172a;
            font-weight: 800;
            margin: .25rem 0 0;
        }

        .bhp-table-wrap {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow-x: auto;
        }

        .bhp-table {
            margin-bottom: 0 !important;
            min-width: 860px;
        }

        .bhp-table thead th {
            background: #f8fafc !important;
            border-bottom: 1px solid #e2e8f0 !important;
            color: #475569;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .03em;
            padding: .9rem .8rem;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .bhp-table tbody td {
            border-color: #eef2f7;
            color: #334155;
            padding: .85rem .8rem;
            vertical-align: middle;
        }

        .bhp-table tbody tr:hover>* {
            background: #f8fbff !important;
        }

        .bhp-currency {
            color: #0f172a;
            font-weight: 800;
            white-space: nowrap;
        }

        .bhp-actions {
            display: flex;
            gap: .5rem;
            justify-content: flex-end;
        }

        .bhp-actions .btn {
            min-height: 34px;
        }

        .btn-detail-bhp {
            align-items: center;
            border-radius: 9px;
            display: inline-flex;
            height: 34px;
            justify-content: center;
            padding: 0;
            width: 34px;
        }

        .bhp-row-actions {
            align-items: center;
            display: inline-flex;
            gap: .35rem;
            justify-content: center;
        }

        .bhp-row-actions .btn {
            align-items: center;
            border-radius: 9px;
            display: inline-flex;
            height: 34px;
            justify-content: center;
            padding: 0;
            width: 34px;
        }

        .bhp-lock-status {
            align-items: center;
            border-radius: 999px;
            display: inline-flex;
            font-size: .7rem;
            font-weight: 800;
            gap: .3rem;
            padding: .36rem .58rem;
            white-space: nowrap;
        }

        .bhp-lock-status.is-open {
            background: #ecfdf5;
            color: #047857;
        }

        .bhp-lock-status.is-locked {
            background: #fef2f2;
            color: #b91c1c;
        }

        .bhp-users {
            align-items: center;
            border-radius: 999px;
            display: inline-flex;
            font-size: .7rem;
            font-weight: 800;
            gap: .3rem;
            padding: .36rem .58rem;
            white-space: nowrap;
        }

        .bhp-users.users {
            background: #ecfdf5;
            color: #769603;
        }

        .bhp-page {
            color: #172033;
        }

        .bhp-hero {
            background:
                radial-gradient(circle at 88% 12%, rgba(45, 212, 191, .3), transparent 28%),
                radial-gradient(circle at 74% 115%, rgba(96, 165, 250, .22), transparent 34%),
                linear-gradient(135deg, #0f2942 0%, #164e63 55%, #0f766e 100%);
            border-radius: 20px;
            box-shadow: 0 20px 45px rgba(15, 42, 67, .18);
            color: #fff;
            overflow: hidden;
            padding: 1.45rem;
            position: relative;
        }

        .bhp-hero::after {
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 50%;
            content: "";
            height: 230px;
            position: absolute;
            right: -65px;
            top: -120px;
            width: 230px;
        }

        .bhp-hero-content {
            position: relative;
            z-index: 1;
        }

        .bhp-hero-kicker {
            align-items: center;
            background: rgba(255, 255, 255, .11);
            border: 1px solid rgba(255, 255, 255, .16);
            border-radius: 999px;
            display: inline-flex;
            font-size: .7rem;
            font-weight: 800;
            gap: .35rem;
            letter-spacing: .06em;
            padding: .35rem .65rem;
            text-transform: uppercase;
        }

        .bhp-hero-title {
            font-size: clamp(1.35rem, 2.5vw, 2rem);
            font-weight: 800;
            letter-spacing: -.025em;
            margin: .75rem 0 .35rem;
        }

        .bhp-hero-description {
            color: rgba(255, 255, 255, .72);
            font-size: .88rem;
            margin: 0;
            max-width: 650px;
        }

        .bhp-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            margin-top: 1rem;
        }

        .bhp-hero-meta-item {
            align-items: center;
            background: rgba(255, 255, 255, .1);
            border: 1px solid rgba(255, 255, 255, .13);
            border-radius: 10px;
            display: inline-flex;
            font-size: .74rem;
            gap: .4rem;
            padding: .45rem .65rem;
        }

        .bhp-hero-status {
            background: rgba(255, 255, 255, .11);
            border: 1px solid rgba(255, 255, 255, .15);
            border-radius: 16px;
            min-width: 235px;
            padding: .9rem;
            position: relative;
            z-index: 1;
        }

        .bhp-hero-status-label {
            color: rgba(255, 255, 255, .62);
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .bhp-hero-status-value {
            font-size: 1.05rem;
            font-weight: 800;
            margin-top: .2rem;
        }

        .bhp-workspace-card,
        .bhp-results-card {
            background: #fff;
            border: 1px solid #e5eaf1;
            border-radius: 17px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .05);
        }

        .bhp-workspace-card {
            padding: 1.1rem;
        }

        .bhp-section-eyebrow {
            color: #2563eb;
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .bhp-section-title {
            color: #0f172a;
            font-size: .98rem;
            font-weight: 800;
            margin: .15rem 0 0;
        }

        .bhp-type-cards {
            display: grid;
            gap: .65rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .bhp-type-option {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            color: #475569;
            display: flex;
            gap: .65rem;
            min-height: 64px;
            padding: .7rem;
            text-align: left;
            transition: all .17s ease;
        }

        .bhp-type-option:hover {
            border-color: #93c5fd;
            transform: translateY(-1px);
        }

        .bhp-type-option.active {
            background: #eff6ff;
            border-color: #60a5fa;
            box-shadow: 0 7px 18px rgba(37, 99, 235, .1);
            color: #1d4ed8;
        }

        .bhp-type-option-icon {
            align-items: center;
            background: #fff;
            border-radius: 10px;
            display: flex;
            flex: 0 0 38px;
            font-size: 19px;
            height: 38px;
            justify-content: center;
            width: 38px;
        }

        .bhp-type-option strong,
        .bhp-type-option small {
            display: block;
        }

        .bhp-type-option strong {
            font-size: .82rem;
        }

        .bhp-type-option small {
            color: #94a3b8;
            font-size: .66rem;
            margin-top: .1rem;
        }

        .bhp-control-label {
            color: #64748b;
            font-size: .72rem;
            font-weight: 700;
            margin-bottom: .35rem;
        }

        .bhp-control-input .form-control,
        .bhp-control-input .input-group-text {
            background: #fff;
            border-color: #dbe3ee;
            min-height: 40px;
        }

        .bhp-criteria-box {
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            padding: .8rem;
        }

        .bhp-criteria-title {
            align-items: center;
            color: #334155;
            display: flex;
            font-size: .72rem;
            font-weight: 800;
            gap: .35rem;
            margin-bottom: .5rem;
            text-transform: uppercase;
        }

        .bhp-criteria-list {
            display: grid;
            gap: .38rem;
        }

        .bhp-criteria-item {
            align-items: flex-start;
            color: #64748b;
            display: flex;
            font-size: .72rem;
            gap: .4rem;
            line-height: 1.35;
        }

        .bhp-criteria-item i {
            color: #10b981;
            margin-top: .08rem;
        }

        .bhp-action-panel {
            background:
                radial-gradient(circle at 100% 0, rgba(59, 130, 246, .15), transparent 32%),
                linear-gradient(145deg, #f8fafc 0%, #eff6ff 100%);
            border: 1px solid #dbeafe;
            border-radius: 15px;
            height: 100%;
            padding: 1rem;
        }

        .bhp-action-period {
            color: #0f172a;
            font-size: 1.15rem;
            font-weight: 800;
            margin: .15rem 0;
        }

        .bhp-action-state {
            align-items: center;
            color: #64748b;
            display: flex;
            font-size: .74rem;
            gap: .35rem;
        }

        .bhp-action-button {
            border: 0;
            border-radius: 11px;
            box-shadow: 0 10px 22px rgba(37, 99, 235, .2);
            font-weight: 800;
            min-height: 44px;
            width: 100%;
        }

        .bhp-action-button:disabled {
            box-shadow: none;
        }

        .bhp-summary-grid {
            display: grid;
            gap: .8rem;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .bhp-summary-card {
            align-items: center;
            background: #fff;
            border: 1px solid #e5eaf1;
            border-radius: 15px;
            display: flex;
            gap: .75rem;
            min-height: 94px;
            padding: .9rem;
        }

        .bhp-summary-icon {
            align-items: center;
            background: #eff6ff;
            border-radius: 12px;
            color: #2563eb;
            display: flex;
            flex: 0 0 44px;
            font-size: 21px;
            height: 44px;
            justify-content: center;
            width: 44px;
        }

        .bhp-summary-card.is-success .bhp-summary-icon {
            background: #ecfdf5;
            color: #059669;
        }

        .bhp-summary-card.is-warning .bhp-summary-icon {
            background: #fffbeb;
            color: #d97706;
        }

        .bhp-summary-card.is-total {
            background: linear-gradient(145deg, #eff6ff 0%, #eef2ff 100%);
            border-color: #bfdbfe;
        }

        .bhp-summary-card.is-lock .bhp-summary-icon {
            background: #f1f5f9;
            color: #64748b;
        }

        .bhp-summary-card.is-lock.is-locked .bhp-summary-icon {
            background: #fef2f2;
            color: #dc2626;
        }

        .bhp-summary-copy {
            min-width: 0;
        }

        .bhp-summary-label {
            font-size: .68rem;
            margin: 0;
        }

        .bhp-summary-value {
            font-size: .98rem;
            margin: .15rem 0 0;
            overflow-wrap: anywhere;
        }

        .bhp-summary-note {
            color: #94a3b8;
            display: block;
            font-size: .67rem;
            margin-top: .15rem;
        }

        .bhp-results-card {
            overflow: hidden;
        }

        .bhp-results-header {
            align-items: center;
            border-bottom: 1px solid #edf1f5;
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            padding: 1rem 1.1rem;
        }

        .bhp-results-toolbar {
            align-items: center;
            display: flex;
            gap: .5rem;
        }

        .bhp-results-search {
            width: min(320px, 42vw);
        }

        .bhp-results-search .form-control,
        .bhp-results-search .input-group-text {
            border-color: #dbe3ee;
            min-height: 37px;
        }

        .bhp-refresh-button {
            align-items: center;
            border-radius: 10px;
            display: inline-flex;
            height: 37px;
            justify-content: center;
            padding: 0;
            width: 37px;
        }

        .bhp-table-wrap {
            border: 0;
            border-radius: 0;
        }

        .bhp-table {
            min-width: 1120px;
        }

        .bhp-table-empty-note {
            align-items: center;
            background: #f8fafc;
            border-top: 1px solid #edf1f5;
            color: #94a3b8;
            display: flex;
            font-size: .7rem;
            gap: .35rem;
            padding: .65rem 1.1rem;
        }

        .bhp-modal-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            color: #1e40af;
            font-size: .82rem;
            padding: .8rem;
        }

        .bhp-detail-modal {
            border-radius: 18px;
            overflow: hidden;
        }

        .bhp-detail-header {
            align-items: center;
            background:
                radial-gradient(circle at 90% 5%, rgba(255, 255, 255, .2), transparent 30%),
                linear-gradient(135deg, #0f766e 0%, #0f4c81 100%);
            color: #fff;
            display: flex;
            justify-content: space-between;
            padding: 1.25rem 1.4rem;
        }

        .bhp-detail-header.is-bpjs {
            background:
                radial-gradient(circle at 90% 5%, rgba(255, 255, 255, .2), transparent 30%),
                linear-gradient(135deg, #1d4ed8 0%, #312e81 100%);
        }

        .bhp-detail-header-main {
            align-items: center;
            display: flex;
            gap: .85rem;
        }

        .bhp-detail-header-icon {
            align-items: center;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 13px;
            display: flex;
            flex: 0 0 46px;
            font-size: 23px;
            height: 46px;
            justify-content: center;
            width: 46px;
        }

        .bhp-detail-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: .85;
        }

        .bhp-detail-type-badge {
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 999px;
            display: inline-flex;
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .04em;
            margin-top: .35rem;
            padding: .3rem .55rem;
            text-transform: uppercase;
        }

        .bhp-detail-body {
            background: #f8fafc;
            padding: 1.15rem 1.4rem 1.3rem;
        }

        .bhp-detail-summary {
            align-items: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            display: flex;
            gap: .75rem;
            min-height: 82px;
            padding: .85rem;
            transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
        }

        .bhp-detail-summary:hover {
            border-color: #bfdbfe;
            box-shadow: 0 9px 22px rgba(15, 23, 42, .07);
            transform: translateY(-2px);
        }

        .bhp-detail-summary-icon {
            align-items: center;
            background: #eff6ff;
            border-radius: 11px;
            color: #2563eb;
            display: flex;
            flex: 0 0 40px;
            font-size: 20px;
            height: 40px;
            justify-content: center;
            width: 40px;
        }

        .bhp-detail-summary.is-success .bhp-detail-summary-icon {
            background: #ecfdf5;
            color: #059669;
        }

        .bhp-detail-summary.is-warning .bhp-detail-summary-icon {
            background: #fffbeb;
            color: #d97706;
        }

        .bhp-detail-summary.is-total {
            background: linear-gradient(135deg, #eff6ff 0%, #eef2ff 100%);
            border-color: #bfdbfe;
        }

        .bhp-detail-summary span {
            color: #64748b;
            display: block;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .03em;
            margin-bottom: .15rem;
            text-transform: uppercase;
        }

        .bhp-detail-summary strong {
            color: #0f172a;
            display: block;
            font-size: .92rem;
            line-height: 1.25;
        }

        .bhp-detail-formula {
            align-items: center;
            background: #fff;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            color: #475569;
            display: flex;
            font-size: .78rem;
            gap: .55rem;
            padding: .65rem .8rem;
        }

        .bhp-detail-toolbar {
            align-items: center;
            display: flex;
            gap: .65rem;
            justify-content: space-between;
        }

        .bhp-detail-search {
            max-width: 440px;
            width: 100%;
        }

        .bhp-detail-search .input-group-text,
        .bhp-detail-search .form-control,
        .bhp-detail-search .btn {
            border-color: #dbe3ee;
            min-height: 38px;
        }

        .bhp-detail-result-count {
            background: #e0ecff;
            border-radius: 999px;
            color: #1d4ed8;
            flex: 0 0 auto;
            font-size: .74rem;
            font-weight: 800;
            padding: .4rem .65rem;
        }

        .bhp-penjamin-filters {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }

        .bhp-penjamin-filter {
            background: #fff;
            border: 1px solid #dbe3ee;
            border-radius: 999px;
            color: #64748b;
            font-size: .72rem;
            font-weight: 700;
            padding: .38rem .65rem;
            transition: all .15s ease;
        }

        .bhp-penjamin-filter:hover,
        .bhp-penjamin-filter.active {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }

        .bhp-detail-table-wrap {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            box-shadow: 0 7px 20px rgba(15, 23, 42, .04);
            max-height: 390px;
            overflow: auto;
        }

        .bhp-detail-table {
            margin-bottom: 0;
        }

        .bhp-detail-table thead {
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .bhp-detail-table thead th {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            color: #64748b;
            font-size: .69rem;
            letter-spacing: .035em;
            padding: .8rem;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .bhp-detail-table tbody td {
            border-color: #eef2f7;
            color: #334155;
            padding: .7rem .8rem;
        }

        .bhp-detail-table tbody tr:hover>* {
            background: #f8fbff;
        }

        .bhp-detail-number {
            align-items: center;
            background: #f1f5f9;
            border-radius: 8px;
            color: #64748b;
            display: inline-flex;
            font-size: .72rem;
            font-weight: 800;
            height: 28px;
            justify-content: center;
            width: 28px;
        }

        .bhp-penjamin-name {
            color: #0f172a;
            display: block;
            font-weight: 700;
        }

        .bhp-penjamin-code {
            background: #f1f5f9;
            border-radius: 5px;
            color: #64748b;
            display: inline-flex;
            font-size: .66rem;
            font-weight: 800;
            margin-top: .2rem;
            padding: .14rem .35rem;
        }

        .btn-copy-rawat {
            align-items: center;
            border-radius: 8px;
            display: inline-flex;
            height: 30px;
            justify-content: center;
            padding: 0;
            width: 30px;
        }

        .bhp-detail-empty {
            color: #94a3b8;
            padding: 2.5rem 1rem !important;
            text-align: center;
        }

        .dataTables_wrapper .dataTables_filter {
            display: none;
        }

        .dataTables_wrapper .page-link {
            border: 0;
            border-radius: 8px;
            color: #475569;
            margin: 0 .1rem;
        }

        .dataTables_wrapper .page-item.active .page-link {
            background: #2563eb;
        }

        @media (max-width: 767.98px) {
            .bhp-actions {
                display: grid;
                grid-template-columns: 1fr auto;
            }

            .bhp-actions .btn:first-child {
                width: 100%;
            }

            .bhp-detail-header,
            .bhp-detail-body {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .bhp-detail-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .bhp-detail-search {
                max-width: none;
            }

            .bhp-hero {
                padding: 1.1rem;
            }

            .bhp-hero-status {
                min-width: 0;
                width: 100%;
            }

            .bhp-type-cards,
            .bhp-summary-grid {
                grid-template-columns: 1fr;
            }

            .bhp-results-header,
            .bhp-results-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .bhp-results-search {
                width: 100%;
            }
        }

        @media (min-width: 768px) and (max-width: 1199.98px) {
            .bhp-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
@endpush

@section("content")
    @include("simrs.backOffice.keuangan.hitungPremi.generateBhp.modal")

    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route("backOffice.keuangan.hitungPremi") }}">Hitung Premi</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Generate BHP</li>
        </ol>
    </nav>

    <div class="bhp-page">
        <section class="bhp-hero mb-3">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <div class="bhp-hero-content">
                    <span class="bhp-hero-kicker">
                        <i class="mdi mdi-chart-box-outline"></i>
                        Back Office Keuangan
                    </span>
                    <h1 class="bhp-hero-title">Generator Premi BHP Rawat Inap</h1>
                    <p class="bhp-hero-description">
                        Hitung, tinjau, dan kunci hasil BHP pasien Umum maupun BPJS dalam satu alur kerja.
                    </p>
                    <div class="bhp-hero-meta">
                        <span class="bhp-hero-meta-item">
                            <i class="mdi mdi-account-group-outline"></i>
                            Sumber: Reg Periksa
                        </span>
                        <span class="bhp-hero-meta-item">
                            <i class="mdi mdi-database-check-outline"></i>
                            Snapshot Detail Pasien
                        </span>
                        <span class="bhp-hero-meta-item">
                            <i class="mdi mdi-shield-lock-outline"></i>
                            Penguncian Hasil
                        </span>
                    </div>
                </div>

                <div class="bhp-hero-status">
                    <div class="bhp-hero-status-label">Konteks Aktif</div>
                    <div class="bhp-hero-status-value">
                        <span id="heroActiveType">Umum</span>
                        <span class="text-white-50 mx-1">/</span>
                        <span id="heroActivePeriod">{{ date("Y-m") }}</span>
                    </div>
                    <small class="text-white-50" id="heroActiveStatus">Memuat status periode...</small>
                </div>
            </div>
        </section>

        <section class="bhp-workspace-card mb-3">
            <div class="row g-3">
                <div class="col-12 col-xl-8">
                    <div class="bhp-section-eyebrow">Konfigurasi Perhitungan</div>
                    <h2 class="bhp-section-title mb-3">Pilih jenis dan periode BHP</h2>

                    <div class="row g-3">
                        <div class="col-12 col-md-7">
                            <div class="bhp-control-label">Jenis BHP</div>
                            <div class="bhp-type-cards" role="group" aria-label="Jenis BHP">
                                <button type="button" class="bhp-type-option active btn-type-bhp" data-type="umum">
                                    <span class="bhp-type-option-icon">
                                        <i class="mdi mdi-account-cash-outline"></i>
                                    </span>
                                    <span>
                                        <strong>Pasien Umum</strong>
                                        <small>Penjamin Umum & All Asuransi Kecuali BPJS Kesehatan</small>
                                    </span>
                                </button>
                                <button type="button" class="bhp-type-option btn-type-bhp" data-type="bpjs">
                                    <span class="bhp-type-option-icon">
                                        <i class="mdi mdi-shield-check-outline"></i>
                                    </span>
                                    <span>
                                        <strong>Pasien BPJS</strong>
                                        <small>penjamin BPJS Kesehatan</small>
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div class="col-12 col-md-5">
                            <label class="bhp-control-label d-block" for="periodeBhp">Periode Generate</label>
                            <div class="input-group bhp-control-input">
                                <span class="input-group-text">
                                    <i class="mdi mdi-calendar-month-outline text-primary"></i>
                                </span>
                                <input type="text" id="periodeBhp" class="form-control fw-semibold"
                                    value="{{ date("Y-m") }}" autocomplete="off">
                            </div>
                            <small class="text-muted d-block mt-2" id="periodeSourceHelp">
                                Data dibaca berdasarkan bulan tanggal registrasi pasien.
                            </small>
                        </div>
                    </div>

                    <div class="bhp-criteria-box mt-3">
                        <div class="bhp-criteria-title">
                            <i class="mdi mdi-filter-check-outline text-primary"></i>
                            Kriteria Data <span id="activeTypeBadgeText">Umum</span>
                        </div>
                        <div class="bhp-criteria-list" id="activeCriteriaList">
                            <div class="bhp-criteria-item">
                                <i class="mdi mdi-check-circle"></i>
                                <span>Status lanjut pasien adalah rawat inap.</span>
                            </div>
                            <div class="bhp-criteria-item">
                                <i class="mdi mdi-check-circle"></i>
                                <span>Penjamin selain BPJS Kesehatan dan kode kosong.</span>
                            </div>
                            <div class="bhp-criteria-item">
                                <i class="mdi mdi-check-circle"></i>
                                <span>Piutang belum lunas, dengan perlakuan khusus pasien A09.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-4">
                    <div class="bhp-action-panel">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="bhp-section-eyebrow">Snapshot Periode</div>
                                <div class="bhp-action-period" id="actionActivePeriod">{{ date("Y-m") }}</div>
                            </div>
                            <span class="bhp-type-badge" id="activeTypeBadge">
                                <i class="mdi mdi-check-circle-outline"></i>
                                <span id="actionActiveType">Umum</span>
                            </span>
                        </div>

                        <div class="bhp-action-state mt-2" id="activeTypeDescription">
                            <i class="mdi mdi-information-outline"></i>
                            <span>Perhitungan BHP pasien rawat inap non-BPJS</span>
                        </div>

                        <div class="bhp-modal-info my-3" id="actionStatusMessage">
                            Belum ada hasil generate untuk konteks yang dipilih.
                        </div>

                        <button type="button" id="btnGenerateBhp" class="btn btn-primary bhp-action-button">
                            <i class="mdi mdi-calculator-variant-outline me-1"></i>
                            <span id="btnGenerateBhpLabel">Generate BHP Umum</span>
                        </button>

                        <small class="text-muted d-block mt-2 text-center">
                            Nominal per pasien diisi pada langkah berikutnya.
                        </small>
                    </div>
                </div>
            </div>
        </section>

        <section class="bhp-summary-grid mb-3">
            <div class="bhp-summary-card is-success">
                <div class="bhp-summary-icon">
                    <i class="mdi mdi-account-multiple-check-outline"></i>
                </div>
                <div class="bhp-summary-copy">
                    <div class="bhp-summary-label">Jumlah Pasien</div>
                    <h5 class="bhp-summary-value" id="summaryJumlahBhp">0 pasien</h5>
                    <small class="bhp-summary-note" id="summaryPeriode">-</small>
                </div>
            </div>

            <div class="bhp-summary-card is-warning">
                <div class="bhp-summary-icon">
                    <i class="mdi mdi-cash-marker"></i>
                </div>
                <div class="bhp-summary-copy">
                    <div class="bhp-summary-label">Nominal Per Pasien</div>
                    <h5 class="bhp-summary-value" id="summaryNominalBhp">Rp 0</h5>
                    <small class="bhp-summary-note">Nilai perhitungan terakhir</small>
                </div>
            </div>

            <div class="bhp-summary-card is-total">
                <div class="bhp-summary-icon">
                    <i class="mdi mdi-wallet-outline"></i>
                </div>
                <div class="bhp-summary-copy">
                    <div class="bhp-summary-label">Total BHP</div>
                    <h5 class="bhp-summary-value text-primary" id="summaryTotalBhp">Rp 0</h5>
                    <small class="bhp-summary-note" id="summaryFormulaBhp">0 pasien x Rp 0</small>
                </div>
            </div>

            <div class="bhp-summary-card is-lock" id="summaryLockCard">
                <div class="bhp-summary-icon">
                    <i class="mdi mdi-lock-open-variant-outline" id="summaryLockIcon"></i>
                </div>
                <div class="bhp-summary-copy">
                    <div class="bhp-summary-label">Status Data</div>
                    <h5 class="bhp-summary-value" id="summaryLockValue">Belum Ada Data</h5>
                    <small class="bhp-summary-note" id="summaryLockNote">Generate data terlebih dahulu</small>
                </div>
            </div>
        </section>

        <section class="bhp-results-card">
            <div class="bhp-results-header">
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <div>
                            <div class="bhp-section-eyebrow">Hasil Periode Aktif</div>
                            <h2 class="bhp-section-title" id="resultBhpTitle">Hasil Generate BHP Umum</h2>
                        </div>
                        <span class="bhp-lock-status is-open d-none" id="activeBhpLockStatus">
                            <i class="mdi mdi-lock-open-variant-outline"></i>
                            <span>Belum dikunci</span>
                        </span>
                    </div>
                    <small class="text-muted">Tinjau detail, kunci hasil, atau buka kunci sebagai Admin.</small>
                </div>

                <div class="bhp-results-toolbar">
                    <div class="input-group input-group-sm bhp-results-search">
                        <span class="input-group-text bg-white">
                            <i class="mdi mdi-magnify text-muted"></i>
                        </span>
                        <input type="text" id="searchBhp" class="form-control"
                            placeholder="Cari periode atau nilai...">
                    </div>
                    <button type="button" id="btnRefreshBhp" class="btn btn-light bhp-refresh-button"
                        title="Refresh data">
                        <i class="mdi mdi-refresh"></i>
                    </button>
                    <button type="button" id="btnLockAllBhp" class="btn btn-warning btn-sm" disabled
                        title="Kunci semua data terbuka">
                        <i class="mdi mdi-lock-check-outline me-1"></i>
                        Kunci Semua
                    </button>
                </div>
            </div>

            <div class="bhp-table-wrap">
                <table id="tableGenerateBhp" class="table table-hover align-middle w-100 bhp-table">
                    <thead>
                        <tr>
                            <th class="text-center" width="6%">No</th>
                            <th>Periode</th>
                            <th>Jenis</th>
                            <th>Ploting</th>
                            <th class="text-center">Jumlah BHP</th>
                            <th class="text-end">Nominal Hitung</th>
                            <th class="text-end">Total BHP</th>
                            <th>Terakhir Generate</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Generate By</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>

            <div class="bhp-table-empty-note">
                <i class="mdi mdi-information-outline"></i>
                Hasil ditampilkan berdasarkan jenis dan periode yang sedang dipilih.
            </div>
        </section>
    </div>
@endsection

@push("scripts")
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/js/bootstrap-datepicker.min.js">
    </script>
    @include("simrs.backOffice.keuangan.hitungPremi.generateBhp.jsMain")
@endpush
