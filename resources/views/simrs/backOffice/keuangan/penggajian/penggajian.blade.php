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
            grid-template-columns: repeat(4, minmax(0, 1fr)) auto;
            gap: .5rem;
        }

        .payroll-actions .btn {
            min-height: 34px;
            line-height: 1.2;
            white-space: normal;
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

        .wa-slip-toolbar {
            display: grid;
            grid-template-columns: auto minmax(220px, 1fr) auto;
            align-items: center;
            gap: .75rem;
        }

        .wa-slip-search {
            max-width: 360px;
            justify-self: end;
        }

        .wa-slip-filter-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(160px, 1fr));
            gap: .65rem;
        }

        .wa-slip-table-wrap {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
        }

        .wa-slip-table-wrap thead th {
            background: #f8fafc !important;
            color: #475569;
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .03em;
            border-bottom: 1px solid #e5e7eb !important;
            white-space: nowrap;
        }

        .wa-slip-table-wrap tbody td {
            border-color: #eef2f7;
        }

        .payroll-config-toolbar {
            display: grid;
            grid-template-columns: minmax(260px, 1fr) auto;
            align-items: end;
            gap: .75rem;
        }

        .payroll-config-list {
            display: grid;
            gap: .75rem;
        }

        .payroll-config-row {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
            padding: .9rem;
        }

        .payroll-config-components {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: .45rem .75rem;
        }

        .payroll-config-empty {
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            color: #64748b;
            padding: 1rem;
            text-align: center;
            background: #f8fafc;
        }

        @media (max-width: 767.98px) {
            .wa-slip-toolbar {
                grid-template-columns: 1fr;
            }

            .wa-slip-search {
                width: 100%;
                max-width: none;
                justify-self: stretch;
            }

            .wa-slip-filter-grid {
                grid-template-columns: 1fr;
            }

            .payroll-config-toolbar {
                grid-template-columns: 1fr;
            }
        }

        .payroll-page-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 18px 42px rgba(15, 23, 42, .08);
            overflow: hidden;
        }

        .min-w-0 {
            min-width: 0;
        }

        .payroll-workspace {
            background: #f8fafc;
            padding: 1.15rem !important;
        }

        .payroll-command-panel {
            background: #ffffff;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, .06);
            padding: 1rem;
        }

        .payroll-command-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .payroll-command-title {
            display: flex;
            align-items: flex-start;
            gap: .85rem;
            min-width: 0;
        }

        .payroll-command-icon,
        .payroll-modal-icon {
            width: 46px;
            height: 46px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            background: #0f766e;
            box-shadow: 0 10px 22px rgba(15, 118, 110, .2);
            flex: 0 0 46px;
        }

        .payroll-kicker {
            color: #64748b;
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .payroll-command-title h5,
        .payroll-table-title h6 {
            color: #0f172a;
        }

        .payroll-command-copy,
        .payroll-muted-copy {
            color: #64748b;
            font-size: .82rem;
            line-height: 1.45;
        }

        .payroll-stage-tabs {
            background: #eef2f7;
            border-radius: 8px;
            min-width: 310px;
            padding: .28rem;
        }

        .payroll-stage-tabs .nav-link {
            border-radius: 6px;
            min-height: 40px;
            font-size: .83rem;
        }

        .payroll-stage-tabs .nav-link.active {
            background: #0f172a;
            box-shadow: 0 8px 18px rgba(15, 23, 42, .18);
        }

        .payroll-command-grid {
            display: grid;
            grid-template-columns: minmax(180px, 240px) minmax(240px, 1fr) minmax(390px, 1.45fr);
            align-items: end;
            gap: .8rem;
        }

        .payroll-field-label {
            color: #475569;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .04em;
            margin-bottom: .35rem;
            text-transform: uppercase;
        }

        .payroll-command-panel .form-control,
        .payroll-command-panel .input-group-text,
        .payroll-config-toolbar .form-select,
        .payroll-config-toolbar .select2-container--default .select2-selection--single {
            border-color: #dbe3ef;
            border-radius: 6px;
        }

        .payroll-command-panel .form-control,
        .payroll-command-panel .input-group-text {
            min-height: 38px;
        }

        .payroll-actions {
            grid-template-columns: repeat(4, minmax(0, 1fr)) 40px;
            gap: .45rem;
            min-width: 0;
        }

        .payroll-actions .btn {
            border-radius: 6px;
            font-size: .78rem;
            font-weight: 800;
            min-height: 38px;
            min-width: 0;
            padding-inline: .65rem;
            overflow-wrap: anywhere;
        }

        .payroll-actions-wrap {
            min-width: 0;
        }

        .payroll-actions .btn-light {
            border: 1px solid #dbe3ef;
            color: #475569;
            background: #ffffff;
        }

        .payroll-context-strip {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .65rem;
            margin-top: .95rem;
            padding-top: .95rem;
            border-top: 1px solid #e2e8f0;
        }

        .payroll-context-item {
            min-width: 0;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            padding: .7rem .8rem;
        }

        .payroll-context-item span {
            display: block;
            color: #64748b;
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .payroll-context-item strong {
            display: block;
            color: #0f172a;
            font-size: .9rem;
            margin-top: .18rem;
            overflow-wrap: anywhere;
        }

        .payroll-summary-card,
        .payroll-stage-summary {
            background: #ffffff;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .05);
            overflow: hidden;
            position: relative;
        }

        .payroll-summary-card::before,
        .payroll-stage-summary::before {
            content: "";
            position: absolute;
            inset: 0 0 auto;
            height: 3px;
            background: linear-gradient(90deg, #0f766e, #2563eb, #f59e0b);
        }

        .payroll-summary-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 30px rgba(15, 23, 42, .08);
        }

        .payroll-summary-icon {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            flex: 0 0 42px;
        }

        .payroll-summary-label {
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .05em;
        }

        .payroll-summary-value {
            font-size: 1.14rem;
            line-height: 1.25;
            margin-top: .2rem;
        }

        .payroll-stage-summary .row .col-6 {
            border-top: 1px solid #eef2f7;
            padding-top: .65rem;
        }

        .payroll-stage-summary strong {
            color: #0f172a;
            font-size: .95rem;
        }

        .stage2-readiness-panel {
            background: #ffffff;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .05);
            overflow: hidden;
        }

        .stage2-readiness-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .85rem;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .stage2-readiness-badge {
            align-items: center;
            border-radius: 999px;
            display: inline-flex;
            font-size: .75rem;
            font-weight: 800;
            gap: .35rem;
            padding: .38rem .65rem;
            white-space: nowrap;
        }

        .stage2-readiness-badge.ready {
            background: #dcfce7;
            color: #15803d;
        }

        .stage2-readiness-badge.blocked {
            background: #fee2e2;
            color: #b91c1c;
        }

        .stage2-readiness-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .65rem;
            padding: .85rem 1rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .stage2-readiness-stat {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #ffffff;
            padding: .65rem .75rem;
        }

        .stage2-readiness-stat span {
            color: #64748b;
            display: block;
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .stage2-readiness-stat strong {
            color: #0f172a;
            display: block;
            margin-top: .16rem;
        }

        .stage2-generator-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: .65rem;
            padding: 1rem;
        }

        .stage2-generator-item {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #ffffff;
            padding: .75rem;
        }

        .stage2-generator-item.ready {
            border-color: #bbf7d0;
            background: #f0fdf4;
        }

        .stage2-generator-item.unlocked {
            border-color: #fed7aa;
            background: #fff7ed;
        }

        .stage2-generator-item.missing {
            border-color: #fecaca;
            background: #fef2f2;
        }

        .stage2-generator-title {
            align-items: center;
            color: #0f172a;
            display: flex;
            font-weight: 800;
            gap: .4rem;
            justify-content: space-between;
        }

        .stage2-generator-note {
            color: #64748b;
            font-size: .78rem;
            line-height: 1.35;
            margin-top: .3rem;
        }

        .stage2-generator-meta {
            color: #475569;
            display: flex;
            flex-wrap: wrap;
            font-size: .72rem;
            gap: .4rem .65rem;
            margin-top: .55rem;
        }

        .stage2-generator-types {
            display: grid;
            gap: .45rem;
            margin-top: .65rem;
        }

        .stage2-generator-type {
            align-items: center;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: .5rem;
            padding: .48rem .55rem;
            background: rgba(255, 255, 255, .72);
        }

        .stage2-generator-type.ready {
            border-color: #bbf7d0;
        }

        .stage2-generator-type.unlocked {
            border-color: #fed7aa;
        }

        .stage2-generator-type.missing {
            border-color: #fecaca;
        }

        .stage2-generator-type strong {
            color: #0f172a;
            font-size: .78rem;
        }

        .stage2-generator-type small {
            color: #64748b;
            display: block;
            font-size: .7rem;
            line-height: 1.2;
        }

        .stage2-generator-type-icon {
            align-items: center;
            border-radius: 999px;
            display: inline-flex;
            height: 24px;
            justify-content: center;
            width: 24px;
        }

        .stage2-generator-type.ready .stage2-generator-type-icon {
            background: #dcfce7;
            color: #15803d;
        }

        .stage2-generator-type.unlocked .stage2-generator-type-icon {
            background: #ffedd5;
            color: #c2410c;
        }

        .stage2-generator-type.missing .stage2-generator-type-icon {
            background: #fee2e2;
            color: #b91c1c;
        }

        .stage2-generator-type-count {
            color: #475569;
            font-size: .72rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .payroll-table-heading {
            align-items: center;
            background: #ffffff;
            border: 1px solid #dbe3ef;
            border-bottom: 0;
            border-radius: 8px 8px 0 0;
            padding: .9rem 1rem;
        }

        .payroll-table-note {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: #475569;
            display: inline-flex;
            font-size: .78rem;
            font-weight: 700;
            gap: .4rem;
            padding: .45rem .65rem;
        }

        .payroll-table-wrap,
        .table-responsive {
            border-color: #dbe3ef;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .05);
        }

        .payroll-table thead th,
        .table thead th {
            color: #334155;
            letter-spacing: .04em;
        }

        .payroll-action-group .btn {
            border-radius: 6px;
        }

        .payroll-status-badge {
            border-radius: 999px;
            min-width: 84px;
        }

        .payroll-modal {
            border: 0;
            border-radius: 8px;
            box-shadow: 0 28px 70px rgba(15, 23, 42, .22);
            overflow: hidden;
        }

        .payroll-modal .modal-header {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0 !important;
            padding: 1rem 1.15rem;
        }

        .payroll-modal .modal-body {
            background: #f8fafc;
            padding: 1rem 1.15rem;
        }

        .payroll-modal .modal-footer {
            background: #ffffff;
            border-top: 1px solid #e2e8f0 !important;
            padding: .85rem 1.15rem;
        }

        .payroll-modal-title {
            display: flex;
            align-items: center;
            gap: .8rem;
            min-width: 0;
        }

        .payroll-modal-icon.is-blue {
            background: #2563eb;
            box-shadow: 0 10px 22px rgba(37, 99, 235, .18);
        }

        .payroll-modal-icon.is-green {
            background: #16a34a;
            box-shadow: 0 10px 22px rgba(22, 163, 74, .18);
        }

        .payroll-modal-icon.is-amber {
            background: #d97706;
            box-shadow: 0 10px 22px rgba(217, 119, 6, .18);
        }

        .payroll-modal-icon.is-blue {
            background: #2563eb;
            box-shadow: 0 10px 22px rgba(37, 99, 235, .18);
        }

        .slip-card {
            background: transparent;
            border: 0;
            border-radius: 0;
            padding: 0;
        }

        .slip-identity-panel,
        .slip-info-box,
        .slip-detail-list,
        .slip-allowance-box,
        .wa-slip-overview,
        .wa-slip-control-panel,
        .payroll-config-overview,
        .payroll-config-toolbar,
        .payroll-config-row {
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            background: #ffffff;
        }

        .slip-identity-panel {
            padding: 1rem;
        }

        .slip-avatar {
            width: 46px;
            height: 46px;
            border-radius: 8px;
            background: #e0f2fe;
            color: #0369a1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 46px;
        }

        .slip-info-box {
            padding: .78rem .85rem;
        }

        .slip-info-box span {
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .slip-detail-list,
        .slip-allowance-box {
            overflow: hidden;
        }

        .slip-detail-row {
            align-items: center;
            background: #ffffff;
            padding: .82rem .95rem;
        }

        .slip-detail-row strong {
            text-align: right;
        }

        .slip-detail-row.total {
            background: #ecfdf5;
        }

        .slip-detail-row.total span,
        .slip-detail-row.total strong {
            color: #047857;
        }

        .slip-section-title {
            padding: .85rem .95rem .1rem;
            margin-bottom: 0;
        }

        .slip-empty-row {
            background: #ffffff;
        }

        .wa-slip-overview,
        .payroll-config-overview {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .65rem;
            padding: .75rem;
        }

        .wa-slip-stat,
        .payroll-config-stat {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: .65rem .75rem;
        }

        .wa-slip-stat span,
        .payroll-config-stat span {
            color: #64748b;
            display: block;
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .wa-slip-stat strong,
        .payroll-config-stat strong {
            color: #0f172a;
            display: block;
            margin-top: .16rem;
        }

        .wa-slip-control-panel {
            padding: .75rem;
        }

        .wa-slip-table-wrap {
            border-radius: 8px;
        }

        .payroll-config-toolbar {
            padding: .85rem;
        }

        .payroll-config-tabs {
            background: #eef2f7;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            display: inline-flex;
            gap: .25rem;
            padding: .28rem;
        }

        .payroll-config-tabs .nav-link {
            border-radius: 6px;
            color: #475569;
            font-size: .82rem;
            font-weight: 800;
            min-width: 112px;
        }

        .payroll-config-tabs .nav-link.active {
            background: #0f172a;
            color: #ffffff;
        }

        .payroll-config-row {
            padding: .9rem;
        }

        .payroll-config-row-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .85rem;
        }

        .payroll-config-count {
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            display: inline-flex;
            font-size: .72rem;
            font-weight: 800;
            padding: .32rem .55rem;
            white-space: nowrap;
        }

        .payroll-config-components .form-check {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            padding: .58rem .65rem .58rem 2.05rem;
        }

        .payroll-config-components .form-check-input {
            margin-top: .18rem;
        }

        .payroll-config-empty-mini {
            color: #94a3b8;
            font-size: .82rem;
            padding: .55rem .65rem;
        }

        @media (max-width: 1199.98px) {
            .payroll-command-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .payroll-command-grid .payroll-actions-wrap {
                grid-column: 1 / -1;
            }

            .payroll-actions {
                grid-template-columns: repeat(4, minmax(0, 1fr)) 40px;
            }
        }

        @media (max-width: 991.98px) {
            .payroll-command-header {
                flex-direction: column;
            }

            .payroll-stage-tabs {
                width: 100%;
                min-width: 0;
            }
        }

        @media (max-width: 767.98px) {
            .payroll-workspace {
                padding: .8rem !important;
            }

            .payroll-command-panel {
                padding: .85rem;
            }

            .payroll-command-grid,
            .payroll-context-strip,
            .stage2-readiness-stats,
            .wa-slip-overview,
            .payroll-config-overview {
                grid-template-columns: 1fr;
            }

            .payroll-actions {
                grid-template-columns: 1fr 1fr;
            }

            .payroll-actions .btn:first-child,
            .payroll-actions .btn:nth-child(2),
            .payroll-actions .btn:nth-child(3),
            .payroll-actions .btn:nth-child(4) {
                grid-column: span 2;
            }

            .payroll-table-heading {
                align-items: stretch;
            }

            .payroll-table-note {
                justify-content: center;
                width: 100%;
            }
        }

        .payroll-premium-page {
            --payroll-ink: #0f172a;
            --payroll-muted: #64748b;
            --payroll-line: #dbe3ef;
            --payroll-soft: #f6f8fb;
            --payroll-panel: #ffffff;
            --payroll-teal: #0f766e;
            --payroll-blue: #2563eb;
            --payroll-green: #16a34a;
            --payroll-amber: #d97706;
            color: var(--payroll-ink);
        }

        .payroll-breadcrumb .breadcrumb {
            background: transparent;
            margin-bottom: .75rem;
            padding: 0;
        }

        .payroll-premium-page .payroll-kicker,
        .payroll-premium-page .payroll-summary-label,
        .payroll-premium-page .payroll-field-label {
            letter-spacing: .04em;
        }

        .payroll-hero-panel,
        .payroll-control-bar,
        .payroll-active-card,
        .payroll-table-card,
        .payroll-stage-card,
        .payroll-summary-card,
        .stage2-readiness-panel {
            border: 1px solid var(--payroll-line);
            border-radius: 8px;
            box-shadow: 0 16px 36px rgba(15, 23, 42, .07);
        }

        .payroll-hero-panel {
            align-items: center;
            background: linear-gradient(135deg, #ffffff 0%, #f2fbf7 52%, #f8fbff 100%);
            display: grid;
            gap: 1rem;
            grid-template-columns: minmax(0, 1fr) auto;
            margin-bottom: .85rem;
            padding: 1rem;
        }

        .payroll-hero-title,
        .payroll-active-card-head,
        .payroll-stage-card-top {
            align-items: flex-start;
            display: flex;
            gap: .85rem;
            min-width: 0;
        }

        .payroll-hero-icon,
        .payroll-active-orb {
            align-items: center;
            background: #0f766e;
            border-radius: 8px;
            color: #ffffff;
            display: inline-flex;
            flex: 0 0 44px;
            height: 44px;
            justify-content: center;
            width: 44px;
        }

        .payroll-active-orb {
            background: #eff6ff;
            color: var(--payroll-blue);
            flex-basis: 38px;
            height: 38px;
            margin-left: auto;
            width: 38px;
        }

        .payroll-stage-tabs {
            background: #eef2f7;
            border: 1px solid var(--payroll-line);
            border-radius: 8px;
            display: grid;
            gap: .28rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            min-width: 260px;
            padding: .28rem;
        }

        .payroll-stage-tabs .nav-link {
            border-radius: 6px;
            color: #475569;
            font-size: .82rem;
            font-weight: 800;
            min-height: 38px;
        }

        .payroll-stage-tabs .nav-link.active {
            background: var(--payroll-ink);
            box-shadow: 0 9px 18px rgba(15, 23, 42, .2);
            color: #ffffff;
        }

        .payroll-control-bar {
            align-items: end;
            background: var(--payroll-panel);
            display: grid;
            gap: .75rem;
            grid-template-columns: 190px minmax(260px, 1fr) minmax(420px, auto);
            margin-bottom: .85rem;
            padding: .9rem;
        }

        .payroll-control-field,
        .payroll-actions-wrap {
            min-width: 0;
        }

        .payroll-control-bar .form-control,
        .payroll-control-bar .input-group-text {
            border-color: var(--payroll-line);
            border-radius: 6px;
            min-height: 36px;
        }

        .payroll-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            min-width: 0;
        }

        .payroll-actions .btn {
            align-items: center;
            border-radius: 6px;
            display: inline-flex;
            font-size: .78rem;
            font-weight: 800;
            justify-content: center;
            min-height: 36px;
            white-space: nowrap;
        }

        .payroll-slip-dropdown {
            display: inline-flex;
            min-width: 132px;
        }

        .payroll-slip-dropdown .dropdown-toggle {
            width: 100%;
        }

        .payroll-slip-dropdown .dropdown-menu {
            border: 1px solid var(--payroll-line);
            border-radius: 8px;
            min-width: 12rem;
            padding: .35rem;
        }

        .payroll-slip-dropdown .dropdown-item {
            align-items: center;
            border-radius: 6px;
            display: flex;
            font-size: .78rem;
            font-weight: 800;
            min-height: 34px;
        }

        .payroll-actions .btn-light {
            background: #ffffff;
            border: 1px solid var(--payroll-line);
            color: #475569;
            min-width: 38px;
        }

        .payroll-action-hint {
            color: var(--payroll-muted);
            display: block;
            font-size: .74rem;
            margin-top: .35rem;
        }

        .payroll-stage-workspace,
        .payroll-stage-content,
        .payroll-stage-pane {
            min-width: 0;
        }

        .payroll-stage-pane.active {
            display: grid;
            gap: .85rem;
        }

        .payroll-stage-overview {
            align-items: stretch;
            display: grid;
            gap: .85rem;
            grid-template-columns: minmax(0, 1fr);
            min-width: 0;
        }

        .payroll-stage-overview.is-stage-2 {
            grid-template-columns: minmax(320px, .7fr) minmax(0, 1.3fr);
        }

        .payroll-stage-overview .payroll-stage-card,
        .payroll-stage-overview .stage2-readiness-panel {
            min-height: 100%;
        }

        .payroll-stage-overview.is-stage-1 .payroll-stage-metrics {
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }

        .payroll-stage-overview .stage2-readiness-stats {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .payroll-stage-overview .stage2-generator-grid {
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            max-height: 210px;
        }

        .payroll-layout-grid {
            align-items: start;
            display: grid;
            gap: .85rem;
            grid-template-columns: minmax(0, 1fr) 360px;
        }

        .payroll-primary-column {
            display: grid;
            gap: .85rem;
            min-width: 0;
        }

        .payroll-active-overview {
            display: grid;
            gap: .85rem;
            grid-template-columns: minmax(300px, .9fr) minmax(460px, 1.1fr);
        }

        .payroll-active-card,
        .payroll-table-card,
        .payroll-summary-card,
        .payroll-stage-card,
        .stage2-readiness-panel {
            background: var(--payroll-panel);
        }

        .payroll-active-card {
            padding: .95rem;
        }

        .payroll-active-card-head {
            justify-content: space-between;
        }

        .payroll-active-card-head span {
            color: var(--payroll-muted);
            font-size: .84rem;
        }

        .payroll-context-strip {
            border-top: 1px solid #e8edf5;
            display: grid;
            gap: .55rem;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-top: .85rem;
            padding-top: .85rem;
        }

        .payroll-context-item {
            background: var(--payroll-soft);
            border: 1px solid #e6edf5;
            border-radius: 8px;
            min-width: 0;
            padding: .62rem .68rem;
        }

        .payroll-context-item span,
        .payroll-stage-metrics small {
            color: var(--payroll-muted);
            display: block;
            font-size: .68rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .payroll-context-item strong {
            color: var(--payroll-ink);
            display: block;
            font-size: .82rem;
            margin-top: .15rem;
            overflow-wrap: anywhere;
        }

        .payroll-summary-grid {
            display: grid;
            gap: .65rem;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .payroll-summary-card {
            box-shadow: none;
            min-width: 0;
            overflow: hidden;
            padding: .78rem;
            position: relative;
        }

        .payroll-summary-card::before,
        .payroll-stage-card::before {
            background: linear-gradient(90deg, var(--payroll-teal), var(--payroll-blue), var(--payroll-amber));
            content: "";
            height: 3px;
            inset: 0 0 auto;
            position: absolute;
        }

        .payroll-summary-value {
            color: var(--payroll-ink);
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.25;
            margin: .22rem 0 0;
            overflow-wrap: anywhere;
        }

        .payroll-table-card {
            overflow: hidden;
        }

        .payroll-table-heading {
            align-items: center;
            background: #ffffff;
            border: 0;
            border-bottom: 1px solid var(--payroll-line);
            border-radius: 0;
            display: flex;
            gap: .75rem;
            justify-content: space-between;
            padding: .85rem .95rem;
        }

        .payroll-table-note {
            align-items: center;
            background: var(--payroll-soft);
            border: 1px solid #e6edf5;
            border-radius: 8px;
            color: #475569;
            display: inline-flex;
            font-size: .76rem;
            font-weight: 800;
            gap: .35rem;
            padding: .4rem .58rem;
            white-space: nowrap;
        }

        .payroll-table-wrap,
        .payroll-premium-page .table-responsive {
            border: 0;
            border-radius: 0;
            box-shadow: none;
            height: calc(100vh - 330px);
            min-height: 430px;
            overflow: auto;
        }

        .payroll-table {
            min-width: 1080px;
        }

        .payroll-table thead th,
        .payroll-premium-page .table thead th {
            background: #f8fafc !important;
            border-bottom: 1px solid var(--payroll-line) !important;
            color: #334155;
            font-size: .7rem;
            font-weight: 800;
            padding: .75rem .8rem;
            position: sticky;
            text-transform: uppercase;
            top: 0;
            z-index: 2;
        }

        .payroll-table tbody td,
        .payroll-premium-page .table tbody td {
            border-color: #edf2f7;
            padding: .74rem .8rem;
        }

        .payroll-insight-rail {
            display: grid;
            gap: .85rem;
            max-height: calc(100vh - 120px);
            min-width: 0;
            overflow: auto;
            position: sticky;
            top: 82px;
        }

        .payroll-stage-card {
            appearance: none;
            border-color: var(--payroll-line);
            color: inherit;
            cursor: pointer;
            overflow: hidden;
            padding: .85rem;
            position: relative;
            text-align: left;
            transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
            width: 100%;
        }

        .payroll-stage-card:hover,
        .payroll-stage-card.is-active {
            border-color: #bfdbfe;
            box-shadow: 0 18px 36px rgba(37, 99, 235, .12);
            transform: translateY(-1px);
        }

        .payroll-stage-card-top {
            justify-content: space-between;
            width: 100%;
        }

        .payroll-stage-card-top>span {
            min-width: 0;
        }

        .payroll-stage-card-top strong,
        .payroll-stage-card-top small {
            display: block;
        }

        .payroll-stage-card-top strong {
            color: var(--payroll-ink);
            font-size: .9rem;
            margin-top: .12rem;
        }

        .payroll-stage-card-top small {
            color: var(--payroll-muted);
            font-size: .76rem;
            margin-top: .12rem;
        }

        .payroll-stage-card-top i {
            align-items: center;
            background: #eff6ff;
            border-radius: 8px;
            color: var(--payroll-blue);
            display: inline-flex;
            flex: 0 0 36px;
            height: 36px;
            justify-content: center;
            width: 36px;
        }

        .payroll-stage-metrics {
            display: grid;
            gap: .48rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: .75rem;
        }

        .payroll-stage-metrics>span {
            background: var(--payroll-soft);
            border: 1px solid #e6edf5;
            border-radius: 8px;
            min-width: 0;
            padding: .55rem .6rem;
        }

        .payroll-stage-metrics strong {
            color: var(--payroll-ink);
            display: block;
            font-size: .84rem;
            margin-top: .15rem;
            overflow-wrap: anywhere;
        }

        .stage2-readiness-panel {
            overflow: hidden;
        }

        .stage2-readiness-header {
            padding: .85rem;
        }

        .stage2-readiness-stats {
            gap: .48rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            padding: .75rem .85rem;
        }

        .stage2-readiness-stat {
            padding: .55rem .6rem;
        }

        .stage2-generator-grid {
            display: grid;
            gap: .55rem;
            max-height: 310px;
            overflow: auto;
            padding: .85rem;
        }

        .stage2-generator-item,
        .stage2-generator-type {
            border-radius: 8px;
        }

        .dataTables_wrapper .dataTables_info {
            padding-top: .35rem;
        }

        .dataTables_wrapper .row {
            margin-left: 0;
            margin-right: 0;
            padding: .55rem .75rem;
        }

        .dataTables_wrapper .row:first-child {
            border-bottom: 1px solid #eef2f7;
        }

        .dataTables_wrapper .row:last-child {
            border-top: 1px solid #eef2f7;
        }

        @media (max-width: 1399.98px) {
            .payroll-layout-grid {
                grid-template-columns: minmax(0, 1fr) 330px;
            }

            .payroll-stage-overview.is-stage-2 {
                grid-template-columns: minmax(300px, .8fr) minmax(0, 1.2fr);
            }

            .payroll-active-overview {
                grid-template-columns: 1fr;
            }

            .payroll-summary-grid {
                grid-template-columns: repeat(4, minmax(150px, 1fr));
                overflow-x: auto;
            }
        }

        @media (max-width: 1199.98px) {
            .payroll-control-bar {
                grid-template-columns: 190px minmax(240px, 1fr);
            }

            .payroll-actions-wrap {
                grid-column: 1 / -1;
            }

            .payroll-layout-grid {
                grid-template-columns: 1fr;
            }

            .payroll-stage-overview.is-stage-2 {
                grid-template-columns: 1fr;
            }

            .payroll-stage-overview.is-stage-1 .payroll-stage-metrics,
            .payroll-stage-overview.is-stage-2 .payroll-stage-metrics {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }

            .payroll-stage-overview .stage2-readiness-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .payroll-insight-rail {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                max-height: none;
                overflow: visible;
                position: static;
            }

            .stage2-readiness-panel {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 991.98px) {
            .payroll-hero-panel {
                grid-template-columns: 1fr;
            }

            .payroll-stage-tabs {
                min-width: 0;
                width: 100%;
            }

            .payroll-summary-grid,
            .payroll-context-strip {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .payroll-stage-overview.is-stage-1 .payroll-stage-metrics,
            .payroll-stage-overview.is-stage-2 .payroll-stage-metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .payroll-table-wrap {
                height: calc(100vh - 355px);
                min-height: 380px;
            }
        }

        @media (max-width: 767.98px) {
            .payroll-premium-page {
                margin-left: -.35rem;
                margin-right: -.35rem;
            }

            .payroll-hero-panel,
            .payroll-control-bar,
            .payroll-active-card,
            .payroll-table-heading {
                padding: .78rem;
            }

            .payroll-control-bar,
            .payroll-active-overview,
            .payroll-stage-overview,
            .payroll-stage-overview.is-stage-1 .payroll-stage-metrics,
            .payroll-stage-overview.is-stage-2 .payroll-stage-metrics,
            .payroll-insight-rail,
            .payroll-summary-grid,
            .payroll-context-strip,
            .stage2-readiness-stats {
                grid-template-columns: 1fr;
            }

            .payroll-actions .btn,
            .payroll-slip-dropdown {
                flex: 1 1 calc(50% - .45rem);
            }

            .payroll-table-heading {
                align-items: stretch;
                flex-direction: column;
            }

            .payroll-table-note {
                justify-content: center;
                white-space: normal;
            }

            .payroll-table-wrap {
                height: calc(100vh - 360px);
                min-height: 320px;
            }
        }
    </style>
@endpush

@section("content")
    @include("simrs.backOffice.keuangan.penggajian.modalDetail")
    @include("simrs.backOffice.keuangan.penggajian.modalWhatsapp")
    @include("simrs.backOffice.keuangan.penggajian.modalGajiTahap2DoctorConfig")

    <nav class="page-breadcrumb payroll-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Keuangan</a></li>
            <li class="breadcrumb-item active" aria-current="page">Penggajian</li>
        </ol>
    </nav>

    <div class="payroll-premium-page">
        <section class="payroll-hero-panel">
            <div class="payroll-hero-title">
                <div class="payroll-hero-icon">
                    <i class="mdi mdi-cash-sync mdi-24px"></i>
                </div>
                <div class="min-w-0">
                    <div class="payroll-kicker">Payroll Command Center</div>
                    <h4 class="fw-bold mb-1">Penggajian Pegawai</h4>
                    <p class="mb-0 payroll-command-copy">
                        Periode <strong id="commandPeriodeText">{{ date("Y-m") }}</strong>
                        dengan kontrol tahap, ringkasan biaya, dan status generator dalam satu layar kerja.
                    </p>
                </div>
            </div>

            <ul class="nav nav-pills payroll-stage-tabs" id="payrollStageTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link active" id="tabTahap1" data-bs-toggle="pill"
                        data-bs-target="#paneTahap1" data-tahap="1" role="tab">
                        <i class="mdi mdi-numeric-1-circle-outline"></i>
                        <span>Tahap 1</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link" id="tabTahap2" data-bs-toggle="pill"
                        data-bs-target="#paneTahap2" data-tahap="2" role="tab">
                        <i class="mdi mdi-numeric-2-circle-outline"></i>
                        <span>Tahap 2</span>
                    </button>
                </li>
            </ul>
        </section>

        <input type="hidden" id="tahapGaji" value="1">

        <section class="payroll-control-bar">
            <div class="payroll-control-field">
                <label class="payroll-field-label">Periode</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white">
                        <i class="mdi mdi-calendar-month text-muted"></i>
                    </span>
                    <input type="text" id="periodeGaji" class="form-control" value="{{ date("Y-m") }}"
                        autocomplete="off">
                </div>
            </div>

            <div class="payroll-control-field payroll-search-field">
                <label class="payroll-field-label">Pencarian Payroll</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white">
                        <i class="mdi mdi-magnify text-muted"></i>
                    </span>
                    <input type="text" id="searchPenggajian" class="form-control"
                        placeholder="Cari nama / NIK / jabatan...">
                </div>
            </div>

            <div class="payroll-actions-wrap">
                <label class="payroll-field-label">Aksi</label>
                <div class="payroll-actions">
                    <button type="button" id="btnGenerateGaji" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-calculator-variant-outline me-1"></i>
                        <span class="payroll-action-label">Generate Tahap 1</span>
                    </button>

                    <div class="dropdown payroll-slip-dropdown">
                        <button type="button" id="btnOpenSlipDelivery" class="btn btn-success btn-sm dropdown-toggle"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="mdi mdi-send me-1"></i>
                            <span>Kirim Slip</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <button type="button" class="dropdown-item slip-delivery-option"
                                    data-channel="whatsapp">
                                    <i class="mdi mdi-whatsapp me-2 text-success"></i>
                                    WhatsApp
                                </button>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item slip-delivery-option" data-channel="email">
                                    <i class="mdi mdi-email-outline me-2 text-primary"></i>
                                    Email
                                </button>
                            </li>
                        </ul>
                    </div>

                    <button type="button" id="btnOpenPayrollDoctorConfig" class="btn btn-outline-primary btn-sm">
                        <i class="mdi mdi-account-cog-outline me-1"></i>
                        <span>Konfig Gaji</span>
                    </button>

                    <button type="button" id="btnExportGajiExcel" class="btn btn-outline-success btn-sm">
                        <i class="mdi mdi-file-excel-outline me-1"></i>
                        <span>Export Excel</span>
                    </button>

                    <button type="button" id="btnRefreshPenggajian" class="btn btn-light btn-sm" title="Refresh">
                        <i class="mdi mdi-refresh"></i>
                    </button>
                </div>
                <small class="payroll-action-hint" id="stageActionHint">Slip dapat dikirim via WhatsApp atau Email.</small>
            </div>
        </section>

        <section class="payroll-stage-workspace">
            <div class="tab-content payroll-stage-content" id="payrollStageContent">
                <div class="tab-pane fade show active payroll-stage-pane" id="paneTahap1" role="tabpanel"
                    aria-labelledby="tabTahap1">
                    <div class="payroll-stage-overview is-stage-1">
                        <button type="button" class="payroll-stage-summary payroll-stage-card is-active"
                            data-stage-shortcut="1">
                            <span class="payroll-stage-card-top">
                                <span>
                                    <span class="payroll-summary-label">Summary Tahap 1</span>
                                    <strong>Gaji Pokok dan Tunjangan</strong>
                                    <small id="summaryTahap1Status">0 tetap / 0 kontrak</small>
                                </span>
                                <i class="mdi mdi-numeric-1-circle-outline"></i>
                            </span>
                            <span class="payroll-stage-metrics">
                                <span>
                                    <small>Pegawai</small>
                                    <strong id="summaryTahap1Pegawai">0</strong>
                                </span>
                                <span>
                                    <small>Total</small>
                                    <strong id="summaryTahap1Total">Rp 0</strong>
                                </span>
                                <span>
                                    <small>Gapok</small>
                                    <strong id="summaryTahap1Gapok">Rp 0</strong>
                                </span>
                                <span>
                                    <small>Tunjangan</small>
                                    <strong id="summaryTahap1Tunjangan">Rp 0</strong>
                                </span>
                                <span>
                                    <small>Premi</small>
                                    <strong id="summaryTahap1Premi">Rp 0</strong>
                                </span>
                            </span>
                        </button>
                    </div>

                    <div class="payroll-table-card">
                        <div class="payroll-table-heading">
                            <div class="payroll-table-title">
                                <div class="payroll-kicker">Daftar Payroll</div>
                                <h6 class="fw-bold mb-0">Hasil Generate Tahap 1</h6>
                                <small class="payroll-muted-copy">
                                    Gaji pokok, tunjangan, dan premi pegawai periode aktif
                                </small>
                            </div>
                            <div class="payroll-table-note">
                                <i class="mdi mdi-database-check-outline"></i>
                                <span>Workspace tabel penuh</span>
                            </div>
                        </div>

                        <div class="payroll-table-wrap">
                            <table id="tablePenggajianTahap1" class="table table-hover align-middle w-100 payroll-table">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th>Nama</th>
                                        <th>Jabatan</th>
                                        <th>Status</th>
                                        <th class="text-end">Komponen Gaji</th>
                                        <th class="text-end">Dibayarkan</th>
                                        <th class="text-end">Tunjangan</th>
                                        <th class="text-end">Premi</th>
                                        <th class="text-end">Total</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade payroll-stage-pane" id="paneTahap2" role="tabpanel" aria-labelledby="tabTahap2">
                    <div class="payroll-stage-overview is-stage-2">
                        <button type="button" class="payroll-stage-summary payroll-stage-card" data-stage-shortcut="2">
                            <span class="payroll-stage-card-top">
                                <span>
                                    <span class="payroll-summary-label">Summary Tahap 2</span>
                                    <strong>Sisa Gaji, Premi, Potongan</strong>
                                    <small id="summaryTahap2Status">0 tetap / 0 kontrak</small>
                                </span>
                                <i class="mdi mdi-numeric-2-circle-outline"></i>
                            </span>
                            <span class="payroll-stage-metrics">
                                <span>
                                    <small>Pegawai</small>
                                    <strong id="summaryTahap2Pegawai">0</strong>
                                </span>
                                <span>
                                    <small>Total</small>
                                    <strong id="summaryTahap2Total">Rp 0</strong>
                                </span>
                                <span>
                                    <small>Gaji</small>
                                    <strong id="summaryTahap2Gapok">Rp 0</strong>
                                </span>
                                <span>
                                    <small>Premi</small>
                                    <strong id="summaryTahap2Premi">Rp 0</strong>
                                </span>
                                <span>
                                    <small>Potongan</small>
                                    <strong id="summaryTahap2Potongan">Rp 0</strong>
                                </span>
                            </span>
                        </button>

                        <div class="stage2-readiness-panel d-none" id="stage2GeneratorReadinessPanel">
                            <div class="stage2-readiness-header">
                                <div>
                                    <div class="payroll-kicker">Kesiapan Generator Tahap 2</div>
                                    <h6 class="fw-bold mb-1">Generator Ready</h6>
                                    <small class="payroll-muted-copy" id="stage2GeneratorReadinessMessage">
                                        Memeriksa generator yang sudah digenerate dan dikunci.
                                    </small>
                                </div>
                                <span class="stage2-readiness-badge blocked" id="stage2GeneratorReadinessBadge">
                                    <i class="mdi mdi-timer-sand"></i>
                                    Memuat
                                </span>
                            </div>

                            <div class="stage2-readiness-stats">
                                <div class="stage2-readiness-stat">
                                    <span>Ready</span>
                                    <strong id="stage2GeneratorReadyCount">0 / 0</strong>
                                </div>
                                <div class="stage2-readiness-stat">
                                    <span>Generated</span>
                                    <strong id="stage2GeneratorGeneratedCount">0 data</strong>
                                </div>
                                <div class="stage2-readiness-stat">
                                    <span>Terkunci</span>
                                    <strong id="stage2GeneratorLockedCount">0 data</strong>
                                </div>
                                <div class="stage2-readiness-stat">
                                    <span>Terbuka</span>
                                    <strong id="stage2GeneratorUnlockedCount">0 data</strong>
                                </div>
                            </div>

                            <div class="stage2-generator-grid" id="stage2GeneratorReadinessList">
                                <div class="stage2-generator-note">Memuat status generator...</div>
                            </div>
                        </div>
                    </div>

                    <div class="payroll-table-card">
                        <div class="payroll-table-heading">
                            <div class="payroll-table-title">
                                <div class="payroll-kicker">Daftar Payroll</div>
                                <h6 class="fw-bold mb-0">Hasil Generate Tahap 2</h6>
                                <small class="payroll-muted-copy">
                                    Sisa gaji kontrak, premi generator, dan potongan periode aktif
                                </small>
                            </div>
                            <div class="payroll-table-note">
                                <i class="mdi mdi-table-large"></i>
                                <span>Workspace tabel penuh</span>
                            </div>
                        </div>

                        <div class="payroll-table-wrap">
                            <table id="tablePenggajianTahap2" class="table table-hover align-middle w-100 payroll-table">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th>Nama</th>
                                        <th>Jabatan</th>
                                        <th>Status</th>
                                        <th class="text-end">Gaji Pokok</th>
                                        <th class="text-end">Dibayarkan</th>
                                        <th class="text-end">Premi</th>
                                        <th class="text-end">Potongan</th>
                                        <th class="text-center">Sumber</th>
                                        <th class="text-end">Total</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
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

        $(document).on('click', '.payroll-stage-card[data-stage-shortcut]', function() {
            const tahap = String($(this).data('stage-shortcut'));
            $('#payrollStageTabs .nav-link[data-tahap="' + tahap + '"]').trigger('click');
        });

        function getActivePayrollTableId() {
            return $('#tahapGaji').val() == '2' ?
                '#tablePenggajianTahap2' :
                '#tablePenggajianTahap1';
        }
    </script>
@endpush
