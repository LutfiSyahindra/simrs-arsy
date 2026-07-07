@extends("template.partials.app")

@push("style")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.select2")
    <style>
        .pb-page {
            --pb-blue: #1d4ed8;
            --pb-cyan: #0891b2;
            --pb-green: #059669;
            --pb-ink: #0f172a;
            --pb-muted: #64748b;
            --pb-line: #e2e8f0;
            color: #172033;
        }

        .pb-page .breadcrumb {
            background: transparent;
            font-size: 13px;
            margin-bottom: 0;
            padding: 0;
        }

        .pb-hero {
            align-items: stretch;
            background: linear-gradient(135deg, rgba(29, 78, 216, .96), rgba(8, 145, 178, .94));
            border-radius: 8px;
            color: #fff;
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(0, 1fr) 330px;
            margin-bottom: 16px;
            overflow: hidden;
            padding: 22px;
        }

        .pb-hero-title {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 0;
            margin-bottom: 6px;
        }

        .pb-hero-subtitle {
            color: rgba(255, 255, 255, .78);
            font-size: 13px;
            line-height: 1.55;
            margin: 0;
            max-width: 820px;
        }

        .pb-hero-panel {
            background: rgba(255, 255, 255, .13);
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 8px;
            padding: 14px;
        }

        .pb-hero-panel label {
            color: #dbeafe;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .pb-hero-panel .form-control,
        .pb-hero-panel .btn {
            height: 38px;
        }

        .pb-toolbar {
            align-items: center;
            background: #fff;
            border: 1px solid var(--pb-line);
            border-radius: 8px;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 14px;
            padding: 12px;
        }

        .pb-type-switch {
            background: #f8fafc;
            border: 1px solid var(--pb-line);
            border-radius: 8px;
            display: inline-flex;
            padding: 4px;
        }

        .pb-type-btn {
            border: 0;
            border-radius: 6px;
            color: var(--pb-muted);
            font-size: 12px;
            font-weight: 800;
            min-width: 82px;
            padding: 8px 12px;
        }

        .pb-type-btn.active {
            background: var(--pb-blue);
            color: #fff;
        }

        .pb-type-btn:disabled {
            cursor: not-allowed;
            opacity: .55;
        }

        .pb-action-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .pb-panel {
            background: #fff;
            border: 1px solid var(--pb-line);
            border-radius: 8px;
            margin-bottom: 14px;
            overflow: hidden;
        }

        .pb-panel-head {
            align-items: center;
            border-bottom: 1px solid #eef2f7;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: space-between;
            padding: 13px 15px;
        }

        .pb-panel-title {
            color: var(--pb-ink);
            font-size: 15px;
            font-weight: 800;
            margin: 0;
        }

        .pb-panel-note {
            color: var(--pb-muted);
            font-size: 12px;
        }

        .pb-panel-body {
            padding: 15px;
        }

        .pb-summary-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            margin-bottom: 14px;
        }

        .pb-metric {
            background: #fff;
            border: 1px solid var(--pb-line);
            border-radius: 8px;
            padding: 13px;
        }

        .pb-metric-label {
            color: var(--pb-muted);
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .pb-metric-value {
            color: var(--pb-ink);
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 0;
            line-height: 1.2;
        }

        .pb-metric-foot {
            color: var(--pb-muted);
            font-size: 11px;
            margin-top: 5px;
        }

        .pb-step-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .pb-step {
            border: 1px solid var(--pb-line);
            border-radius: 8px;
            padding: 11px;
        }

        .pb-step.success {
            background: #ecfdf5;
            border-color: #bbf7d0;
        }

        .pb-step.warning {
            background: #fffbeb;
            border-color: #fde68a;
        }

        .pb-step.danger {
            background: #fef2f2;
            border-color: #fecaca;
        }

        .pb-step.muted {
            background: #f8fafc;
        }

        .pb-step-label {
            color: var(--pb-muted);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .pb-step-value {
            color: var(--pb-ink);
            font-size: 14px;
            font-weight: 900;
            margin: 3px 0;
        }

        .pb-step-note {
            color: var(--pb-muted);
            font-size: 11px;
            line-height: 1.45;
        }

        .pb-source-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }

        .pb-source-item {
            background:
                linear-gradient(135deg, rgba(255, 255, 255, .98), rgba(248, 250, 252, .95)),
                radial-gradient(circle at top right, rgba(8, 145, 178, .16), transparent 38%);
            border: 1px solid var(--pb-line);
            border-radius: 8px;
            min-height: 132px;
            padding: 12px;
            position: relative;
            transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
        }

        .pb-source-item:hover {
            border-color: rgba(8, 145, 178, .44);
            box-shadow: 0 14px 30px rgba(15, 23, 42, .1);
            transform: translateY(-2px);
        }

        .pb-source-item.ready {
            background:
                linear-gradient(135deg, rgba(240, 253, 250, .98), rgba(255, 255, 255, .96)),
                radial-gradient(circle at top right, rgba(16, 185, 129, .2), transparent 40%);
            border-color: #99f6e4;
        }

        .pb-source-item.pending {
            background:
                linear-gradient(135deg, rgba(255, 251, 235, .98), rgba(255, 255, 255, .96)),
                radial-gradient(circle at top right, rgba(245, 158, 11, .18), transparent 40%);
            border-color: #fde68a;
        }

        .pb-source-top {
            align-items: center;
            display: flex;
            gap: 8px;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .pb-source-name {
            color: var(--pb-ink);
            font-size: 12px;
            font-weight: 900;
            line-height: 1.3;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pb-badge {
            border-radius: 999px;
            display: inline-flex;
            font-size: 10px;
            font-weight: 800;
            line-height: 1;
            padding: 5px 7px;
        }

        .pb-badge.success {
            background: #dcfce7;
            color: #166534;
        }

        .pb-badge.warning {
            background: #fef3c7;
            color: #92400e;
        }

        .pb-badge.muted {
            background: #f1f5f9;
            color: #64748b;
        }

        .pb-source-value {
            color: var(--pb-blue);
            font-size: 16px;
            font-weight: 900;
            line-height: 1.25;
        }

        .pb-source-note {
            color: var(--pb-muted);
            font-size: 11px;
            line-height: 1.45;
            margin-top: 5px;
        }

        .pb-source-meta {
            align-items: center;
            color: #475569;
            display: flex;
            font-size: 10px;
            font-weight: 800;
            gap: 6px;
            justify-content: space-between;
            margin-top: 12px;
        }

        .pb-source-progress,
        .pb-distribution-progress,
        .pb-detail-progress {
            background: #e2e8f0;
            border-radius: 999px;
            height: 7px;
            margin-top: 7px;
            overflow: hidden;
        }

        .pb-source-progress span,
        .pb-distribution-progress span,
        .pb-detail-progress span {
            background: linear-gradient(90deg, #0f766e, #0891b2, #2563eb);
            border-radius: inherit;
            display: block;
            height: 100%;
        }

        .pb-table-wrap {
            overflow-x: auto;
        }

        .pb-table {
            margin-bottom: 0;
            min-width: 980px;
        }

        .pb-table thead th {
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .pb-table tbody td {
            font-size: 12px;
            vertical-align: middle;
        }

        .pb-table tbody tr {
            transition: background .16s ease, box-shadow .16s ease;
        }

        .pb-table tbody tr:hover {
            background: #f8fafc;
        }

        .pb-panel-title-wrap {
            align-items: center;
            display: flex;
            flex: 1 1 260px;
            gap: 10px;
            min-width: 0;
        }

        .pb-panel-head-icon {
            align-items: center;
            background: #ecfeff;
            border-radius: 8px;
            color: #0e7490;
            display: flex;
            flex: 0 0 auto;
            font-size: 21px;
            height: 38px;
            justify-content: center;
            width: 38px;
        }

        .pb-panel-head-icon.amber {
            background: #fffbeb;
            color: #b45309;
        }

        .pb-panel-head-icon.rose {
            background: #fff1f2;
            color: #be123c;
        }

        .pb-panel-head-meta {
            align-items: center;
            display: flex;
            flex: 0 1 auto;
            flex-wrap: wrap;
            gap: 7px;
            justify-content: flex-end;
        }

        .pb-mini-pill {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            color: #475569;
            display: inline-flex;
            font-size: 11px;
            font-weight: 800;
            gap: 5px;
            line-height: 1;
            max-width: 230px;
            min-width: 0;
            padding: 7px 9px;
            white-space: nowrap;
        }

        .pb-mini-pill strong {
            color: var(--pb-ink);
            font-weight: 900;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pb-mini-pill.success {
            background: #ecfdf5;
            border-color: #bbf7d0;
            color: #166534;
        }

        .pb-mini-pill.warning {
            background: #fffbeb;
            border-color: #fde68a;
            color: #92400e;
        }

        .pb-preview-strip {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-bottom: 12px;
        }

        .pb-preview-stat {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: flex;
            gap: 10px;
            min-width: 0;
            padding: 10px;
        }

        .pb-preview-stat>div,
        .pb-page .min-w-0 {
            min-width: 0;
        }

        .pb-preview-stat-icon {
            align-items: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: #0f766e;
            display: flex;
            flex: 0 0 auto;
            font-size: 19px;
            height: 36px;
            justify-content: center;
            width: 36px;
        }

        .pb-preview-stat-icon.blue {
            color: #2563eb;
        }

        .pb-preview-stat-icon.amber {
            color: #b45309;
        }

        .pb-preview-stat-label {
            color: var(--pb-muted);
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .pb-preview-stat-value {
            color: var(--pb-ink);
            font-size: 13px;
            font-weight: 900;
            line-height: 1.25;
            margin-top: 2px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pb-filter-bar {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            margin-bottom: 12px;
            padding: 10px;
        }

        .pb-filter-group {
            display: grid;
            flex: 1 1 auto;
            gap: 8px;
            grid-template-columns: minmax(220px, 1.35fr) repeat(3, minmax(130px, .7fr)) auto;
            min-width: 0;
        }

        .pb-filter-group.distribution {
            grid-template-columns: minmax(220px, 1.35fr) minmax(150px, .75fr) minmax(120px, .55fr) auto;
        }

        .pb-filter-group.rawat {
            grid-template-columns: minmax(240px, 1.45fr) minmax(150px, .75fr) minmax(140px, .65fr) minmax(150px, .75fr) minmax(120px, .55fr) auto;
        }

        .pb-filter-bar .input-group-text,
        .pb-filter-bar .form-control,
        .pb-filter-bar .form-select,
        .pb-filter-bar .btn {
            font-size: 12px;
            min-height: 34px;
        }

        .pb-filter-bar .input-group-text {
            background: #fff;
            color: #64748b;
        }

        .pb-filter-info {
            color: #475569;
            flex: 0 0 auto;
            font-size: 11px;
            font-weight: 800;
            line-height: 1.35;
            max-width: 340px;
            text-align: right;
        }

        .pb-action-name,
        .pb-employee-name {
            align-items: center;
            display: flex;
            gap: 8px;
            min-width: 0;
        }

        .pb-action-index,
        .pb-employee-rank {
            align-items: center;
            background: #eef2ff;
            border-radius: 8px;
            color: #3730a3;
            display: flex;
            flex: 0 0 auto;
            font-size: 11px;
            font-weight: 900;
            height: 30px;
            justify-content: center;
            width: 30px;
        }

        .pb-action-title,
        .pb-employee-title {
            color: var(--pb-ink);
            display: block;
            font-size: 12px;
            font-weight: 900;
            line-height: 1.25;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pb-action-subtitle,
        .pb-employee-subtitle {
            color: var(--pb-muted);
            display: block;
            font-size: 11px;
            line-height: 1.35;
            margin-top: 2px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pb-map-chip {
            align-items: center;
            border-radius: 999px;
            display: inline-flex;
            font-size: 10px;
            font-weight: 900;
            line-height: 1;
            padding: 6px 8px;
            text-transform: uppercase;
        }

        .pb-map-chip.percent {
            background: #ecfeff;
            color: #0e7490;
        }

        .pb-map-chip.nominal {
            background: #fff7ed;
            color: #9a3412;
        }

        .pb-detail-formula {
            color: #64748b;
            font-size: 10px;
            line-height: 1.35;
            margin-top: 5px;
        }

        .pb-amount-strong {
            color: #0f766e;
            display: block;
            font-size: 13px;
            font-weight: 900;
            white-space: nowrap;
        }

        .pb-amount-note {
            color: var(--pb-muted);
            display: block;
            font-size: 10px;
            margin-top: 2px;
        }

        .pb-distribution-cell {
            min-width: 98px;
        }

        .pb-distribution-percent {
            color: var(--pb-ink);
            font-weight: 900;
        }

        .pb-distribution-progress span {
            background: linear-gradient(90deg, #be123c, #f59e0b, #0f766e);
        }

        .pb-table-scroll {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            max-height: 420px;
            overflow: auto;
        }

        .pb-table-scroll.sm {
            max-height: 360px;
        }

        .pb-table-scroll.lg {
            max-height: 480px;
        }

        .pb-table-scroll .pb-table thead th {
            box-shadow: 0 1px 0 #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .pb-actions {
            display: flex;
            gap: 6px;
        }

        .pb-actions .btn {
            align-items: center;
            display: inline-flex;
            height: 31px;
            justify-content: center;
            padding: 0;
            width: 31px;
        }

        .pb-config-dialog {
            max-width: 1180px;
        }

        .pb-config-modal {
            border: 0;
            border-radius: 8px;
            box-shadow: 0 22px 70px rgba(15, 23, 42, .24);
            overflow: hidden;
        }

        .pb-config-header {
            align-items: flex-start;
            background: linear-gradient(135deg, #0f172a 0%, #155e75 58%, #0f766e 100%);
            border-bottom: 0;
            color: #fff;
            padding: 18px 20px;
        }

        .pb-config-header-main {
            align-items: center;
            display: flex;
            gap: 13px;
            min-width: 0;
        }

        .pb-config-title-icon {
            align-items: center;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 8px;
            display: flex;
            flex: 0 0 auto;
            font-size: 27px;
            height: 50px;
            justify-content: center;
            width: 50px;
        }

        .pb-config-kicker {
            color: #a7f3d0;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .pb-config-title {
            color: #fff;
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 0;
            margin: 1px 0 3px;
        }

        .pb-config-subtitle {
            color: rgba(255, 255, 255, .72);
            font-size: 12px;
        }

        .pb-config-header .btn-close {
            filter: invert(1) grayscale(100%);
            opacity: .86;
        }

        .pb-config-body {
            background: #f6f8fb;
            padding: 15px;
            position: relative;
        }

        .pb-config-loader {
            align-items: center;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            color: #1e3a8a;
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
            padding: 12px;
        }

        .pb-config-loader strong,
        .pb-config-loader span {
            display: block;
        }

        .pb-config-loader strong {
            color: #0f172a;
            font-size: 13px;
            font-weight: 900;
            line-height: 1.25;
        }

        .pb-config-loader span {
            color: #475569;
            font-size: 11px;
            line-height: 1.35;
            margin-top: 1px;
        }

        .pb-config-body.is-loading .pb-config-summary-grid,
        .pb-config-body.is-loading .pb-config-grid,
        .pb-config-body.is-loading > .pb-config-section {
            opacity: .48;
            pointer-events: none;
        }

        .pb-config-summary-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: 1.15fr repeat(4, minmax(0, 1fr));
            margin-bottom: 13px;
        }

        .pb-config-summary-card {
            background: #fff;
            border: 1px solid var(--pb-line);
            border-radius: 8px;
            min-width: 0;
            padding: 12px;
        }

        .pb-config-summary-card.total {
            background: #0f172a;
            border-color: #0f172a;
            color: #fff;
        }

        .pb-config-summary-label {
            color: var(--pb-muted);
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .pb-config-summary-card.total .pb-config-summary-label {
            color: #a7f3d0;
        }

        .pb-config-summary-value {
            color: var(--pb-ink);
            font-size: 16px;
            font-weight: 900;
            line-height: 1.25;
            margin-top: 4px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pb-config-summary-card.total .pb-config-summary-value {
            color: #fff;
            font-size: 18px;
        }

        .pb-config-summary-foot {
            color: var(--pb-muted);
            font-size: 10px;
            line-height: 1.4;
            margin-top: 4px;
        }

        .pb-config-summary-card.total .pb-config-summary-foot {
            color: #cbd5e1;
        }

        .pb-config-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .pb-config-section {
            background: #fff;
            border: 1px solid var(--pb-line);
            border-radius: 8px;
            margin-bottom: 12px;
            overflow: hidden;
        }

        .pb-config-section-head {
            align-items: center;
            border-bottom: 1px solid #eef2f7;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 12px 14px;
        }

        .pb-config-section-title-wrap {
            align-items: center;
            display: flex;
            gap: 10px;
            min-width: 0;
        }

        .pb-config-section-icon {
            align-items: center;
            background: #ecfeff;
            border-radius: 8px;
            color: #0e7490;
            display: flex;
            flex: 0 0 auto;
            font-size: 21px;
            height: 38px;
            justify-content: center;
            width: 38px;
        }

        .pb-config-section-title {
            color: var(--pb-ink);
            font-size: 13px;
            font-weight: 900;
            margin: 0;
        }

        .pb-config-section-note {
            color: var(--pb-muted);
            font-size: 11px;
            margin-top: 1px;
        }

        .pb-config-section-body {
            padding: 14px;
        }

        .pb-config-form-grid,
        .pb-config-ploting-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .pb-config-modal .form-label {
            color: #475569;
            font-size: 10px;
            font-weight: 900;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .pb-config-modal .form-select:not([multiple]),
        .pb-config-modal .form-control {
            min-height: 39px;
        }

        .pb-config-modal select[multiple] {
            min-height: 154px;
        }

        .pb-config-modal .select2-container {
            width: 100% !important;
        }

        .pb-config-modal .select2-container--default .select2-selection--multiple {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            min-height: 39px;
            padding: 2px 4px;
        }

        .pb-config-modal .select2-container--default.select2-container--focus .select2-selection--multiple,
        .pb-config-modal .select2-container--default.select2-container--open .select2-selection--multiple,
        .pb-config-modal .select2-container--default.select2-container--focus .select2-selection--single,
        .pb-config-modal .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #0891b2;
            box-shadow: 0 0 0 .16rem rgba(8, 145, 178, .14);
        }

        .pb-config-modal .select2-container--default .select2-selection--single {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            height: 39px;
        }

        .pb-config-modal .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #334155;
            line-height: 37px;
        }

        .pb-config-modal .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 37px;
        }

        .pb-config-modal .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background: #ecfeff;
            border: 1px solid #bae6fd;
            border-radius: 999px;
            color: #0f172a;
            font-size: 11px;
            margin-top: 5px;
            padding: 2px 7px;
        }

        .pb-config-modal .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #0e7490;
            margin-right: 5px;
        }

        .select2-container--open .select2-dropdown {
            border-color: #0891b2;
        }

        .pb-config-help {
            color: var(--pb-muted);
            font-size: 10px;
            margin-top: 6px;
        }

        .pb-config-switch-list {
            display: grid;
            gap: 8px;
            margin-top: 12px;
        }

        .pb-config-switch {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            padding: 10px;
        }

        .pb-config-switch strong {
            color: var(--pb-ink);
            display: block;
            font-size: 12px;
        }

        .pb-config-switch span {
            color: var(--pb-muted);
            display: block;
            font-size: 10px;
            line-height: 1.35;
            margin-top: 1px;
        }

        .pb-config-box-title {
            color: var(--pb-ink);
            font-size: 13px;
            font-weight: 900;
            margin-bottom: 10px;
        }

        .pb-source-map-row {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: grid;
            gap: 8px;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.3fr) 34px;
            margin-bottom: 8px;
            padding: 8px;
        }

        .pb-config-footer {
            background: #fff;
            border-top: 1px solid #eef2f7;
            padding: 12px 16px;
        }

        .pb-empty {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            color: var(--pb-muted);
            font-size: 12px;
            grid-column: 1 / -1;
            padding: 18px;
            text-align: center;
        }

        .pb-page {
            background: #f5f7fb;
            margin: -8px -8px 0;
            min-height: calc(100vh - 72px);
            padding: 8px 8px 24px;
        }

        .pb-hero {
            background: linear-gradient(135deg, #111827 0%, #134e4a 54%, #365314 100%);
            border: 1px solid rgba(148, 163, 184, .34);
            box-shadow: 0 18px 46px rgba(15, 23, 42, .18);
            grid-template-columns: minmax(0, 1fr) 370px;
            padding: 24px;
        }

        .pb-hero-copy {
            align-content: space-between;
            display: grid;
            gap: 18px;
            min-width: 0;
        }

        .pb-hero-eyebrow {
            color: #bef264;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .pb-hero-title {
            font-size: 28px;
            line-height: 1.12;
            margin-top: 4px;
        }

        .pb-hero-subtitle {
            color: rgba(255, 255, 255, .78);
            max-width: 900px;
        }

        .pb-hero-stat-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            max-width: 860px;
        }

        .pb-hero-stat {
            background: rgba(255, 255, 255, .1);
            border: 1px solid rgba(255, 255, 255, .17);
            border-radius: 8px;
            padding: 10px 12px;
        }

        .pb-hero-stat span {
            color: rgba(255, 255, 255, .66);
            display: block;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .pb-hero-stat strong {
            color: #fff;
            display: block;
            font-size: 15px;
            font-weight: 900;
            margin-top: 3px;
        }

        .pb-period-panel {
            align-content: center;
            background: #fff;
            border-color: rgba(255, 255, 255, .42);
            color: var(--pb-ink);
            display: grid;
            gap: 12px;
        }

        .pb-period-panel label {
            color: #334155;
        }

        .pb-period-panel .pb-period-note {
            color: var(--pb-muted);
            font-size: 11px;
            line-height: 1.45;
        }

        .pb-toolbar {
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06);
        }

        .pb-toolbar-label {
            color: var(--pb-muted);
            font-size: 10px;
            font-weight: 900;
            margin-right: 8px;
            text-transform: uppercase;
        }

        .pb-metric {
            box-shadow: 0 9px 24px rgba(15, 23, 42, .05);
            min-width: 0;
        }

        .pb-metric-top {
            align-items: flex-start;
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }

        .pb-metric-icon {
            align-items: center;
            background: #eff6ff;
            border-radius: 8px;
            color: #2563eb;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 20px;
            height: 38px;
            justify-content: center;
            width: 38px;
        }

        .pb-metric-icon.teal {
            background: #ccfbf1;
            color: #0f766e;
        }

        .pb-metric-icon.amber {
            background: #fef3c7;
            color: #b45309;
        }

        .pb-metric-icon.rose {
            background: #ffe4e6;
            color: #be123c;
        }

        .pb-metric-icon.slate {
            background: #f1f5f9;
            color: #334155;
        }

        .pb-detail-dialog {
            max-width: 1240px;
        }

        .pb-detail-modal {
            border: 0;
            border-radius: 8px;
            box-shadow: 0 26px 80px rgba(15, 23, 42, .28);
            overflow: hidden;
        }

        .pb-detail-header {
            align-items: flex-start;
            background: linear-gradient(135deg, #111827 0%, #164e63 58%, #166534 100%);
            border-bottom: 0;
            color: #fff;
            padding: 18px 20px;
        }

        .pb-detail-header-main {
            align-items: center;
            display: flex;
            gap: 13px;
            min-width: 0;
        }

        .pb-detail-title-icon {
            align-items: center;
            background: rgba(255, 255, 255, .13);
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 8px;
            display: flex;
            flex: 0 0 auto;
            font-size: 26px;
            height: 50px;
            justify-content: center;
            width: 50px;
        }

        .pb-detail-kicker {
            color: #bef264;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .pb-detail-title {
            color: #fff;
            font-size: 18px;
            font-weight: 900;
            margin: 2px 0 3px;
        }

        .pb-detail-subtitle {
            color: rgba(255, 255, 255, .74);
            font-size: 12px;
        }

        .pb-detail-header .btn-close {
            filter: invert(1) grayscale(100%);
            opacity: .86;
        }

        .pb-detail-body {
            background: #f6f8fb;
            padding: 15px;
        }

        .pb-detail-loading {
            color: var(--pb-muted);
            font-size: 13px;
            padding: 52px 20px;
            text-align: center;
        }

        .pb-detail-metrics {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fit, minmax(135px, 1fr));
            margin-bottom: 12px;
        }

        .pb-detail-metric {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            min-width: 0;
            padding: 11px;
        }

        .pb-detail-metric.grand {
            background: #ecfdf5;
            border-color: #bbf7d0;
        }

        .pb-detail-metric span {
            color: var(--pb-muted);
            display: block;
            font-size: 11px;
            font-weight: 900;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .pb-detail-metric strong {
            color: #0f172a;
            display: block;
            font-size: 16px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pb-detail-metric.grand strong {
            color: #047857;
            font-size: 18px;
            font-weight: 900;
        }

        .pb-formula-strip {
            align-items: center;
            background: #102a43;
            border-radius: 8px;
            color: #dbeafe;
            display: flex;
            font-size: 12px;
            font-weight: 800;
            gap: 8px;
            margin-bottom: 12px;
            padding: 12px;
        }

        .pb-formula-strip strong {
            color: #fff;
            font-weight: 900;
        }

        .pb-formula-muted {
            color: #bfdbfe;
        }

        .pb-info-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }

        .pb-info-pill {
            align-items: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            display: inline-flex;
            gap: 7px;
            padding: 8px 10px;
        }

        .pb-info-pill i {
            color: #0f766e;
            font-size: 16px;
        }

        .pb-info-pill span {
            color: #475569;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .pb-info-pill strong {
            color: #0f172a;
            font-size: 12px;
            white-space: nowrap;
        }

        .pb-detail-section {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 12px;
            overflow: hidden;
        }

        .pb-detail-section-head {
            align-items: center;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 12px;
        }

        .pb-detail-section-title {
            color: #0f172a;
            font-size: 13px;
            font-weight: 900;
        }

        .pb-transaction-toolbar {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
            min-width: 420px;
        }

        .pb-transaction-toolbar .form-control,
        .pb-transaction-toolbar .form-select {
            font-size: 12px;
            min-height: 34px;
        }

        .pb-transaction-toolbar .form-control {
            width: 210px;
        }

        .pb-transaction-toolbar .form-select {
            width: 190px;
        }

        .pb-detail-table th {
            color: #475569;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .pb-detail-table td {
            font-size: 12px;
            vertical-align: middle;
        }

        .pb-table-scroll .pb-detail-table thead th {
            background: #f8fafc;
            box-shadow: 0 1px 0 #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .pb-detail-clickable {
            cursor: pointer;
        }

        .pb-route-note {
            color: #0f766e;
            font-size: 11px;
            font-weight: 900;
            margin-top: 2px;
        }

        .pb-detail-chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 9px;
        }

        .pb-detail-chip {
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 999px;
            color: rgba(255, 255, 255, .86);
            font-size: 11px;
            font-weight: 800;
            padding: 5px 9px;
        }

        .pb-detail-kpi-grid,
        .pb-detail-insight-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            margin-bottom: 12px;
        }

        .pb-detail-insight-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .pb-insight-card,
        .pb-mapping-card {
            background: #fff;
            border: 1px solid var(--pb-line);
            border-radius: 8px;
            padding: 12px;
        }

        .pb-insight-label,
        .pb-mapping-label {
            color: var(--pb-muted);
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .pb-insight-value {
            color: var(--pb-ink);
            font-size: 18px;
            font-weight: 900;
            margin-top: 3px;
        }

        .pb-insight-note,
        .pb-mapping-note {
            color: var(--pb-muted);
            font-size: 11px;
            line-height: 1.45;
            margin-top: 3px;
        }

        .pb-mapping-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .pb-mapping-card strong {
            color: var(--pb-ink);
            display: block;
            font-size: 13px;
            line-height: 1.35;
            margin-top: 4px;
        }

        .pb-mapping-card .pb-mapping-value {
            color: #0f766e;
            font-size: 15px;
            font-weight: 900;
            margin-top: 6px;
        }

        .pb-detail-rawat-table {
            min-width: 1120px;
        }

        #previewDistributionTable,
        #detailDistributionTable {
            min-width: 560px;
        }

        #detailActionTable {
            min-width: 820px;
        }

        @media (max-width: 1199.98px) {

            .pb-summary-grid,
            .pb-source-grid,
            .pb-preview-strip,
            .pb-config-summary-grid,
            .pb-detail-kpi-grid,
            .pb-detail-insight-grid,
            .pb-mapping-grid,
            .pb-hero-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .pb-hero {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767.98px) {
            .pb-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .pb-action-group {
                flex-direction: column;
            }

            .pb-panel-head-meta {
                justify-content: flex-start;
                width: 100%;
            }

            .pb-filter-bar {
                align-items: stretch;
                flex-direction: column;
            }

            .pb-filter-group,
            .pb-filter-group.distribution,
            .pb-filter-group.rawat {
                grid-template-columns: 1fr;
                width: 100%;
            }

            .pb-filter-info {
                max-width: none;
                text-align: left;
                width: 100%;
            }

            .pb-detail-section-head {
                align-items: stretch;
                flex-direction: column;
            }

            .pb-transaction-toolbar {
                justify-content: flex-start;
                min-width: 0;
                width: 100%;
            }

            .pb-transaction-toolbar .form-control,
            .pb-transaction-toolbar .form-select {
                width: 100%;
            }

            .pb-summary-grid,
            .pb-step-grid,
            .pb-source-grid,
            .pb-config-grid,
            .pb-config-summary-grid,
            .pb-config-form-grid,
            .pb-config-ploting-grid,
            .pb-detail-kpi-grid,
            .pb-detail-insight-grid,
            .pb-mapping-grid,
            .pb-preview-strip,
            .pb-hero-stat-grid {
                grid-template-columns: 1fr;
            }

            .pb-source-map-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section("content")
    <div class="pb-page">
        <nav class="page-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route("backOffice.keuangan.hitungPremi") }}">Generate Premi</a></li>
                <li class="breadcrumb-item active" aria-current="page">Premi Bersama</li>
            </ol>
        </nav>

        <section class="pb-hero">
            <div class="pb-hero-copy">
                <div>
                    <div class="pb-hero-eyebrow">Back Office Keuangan</div>
                    <div class="pb-hero-title">Generate Premi Bersama</div>
                    <p class="pb-hero-subtitle">
                        Agregator premi yang menarik nilai bersama dari generator unit, menghitung tindakan rawat sesuai
                        mapping premi, lalu membagikan grand total berdasarkan proporsi skor pegawai.
                    </p>
                </div>
                <div class="pb-hero-stat-grid">
                    <div class="pb-hero-stat">
                        <span>Sumber nilai</span>
                        <strong>Generator + rawat</strong>
                    </div>
                    <div class="pb-hero-stat">
                        <span>Basis distribusi</span>
                        <strong>Skor pegawai</strong>
                    </div>
                    <div class="pb-hero-stat">
                        <span>Snapshot</span>
                        <strong>Detail mapping tersimpan</strong>
                    </div>
                </div>
            </div>
            <div class="pb-hero-panel pb-period-panel">
                <label for="periodePremiBersama">Periode Generate</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="mdi mdi-calendar-month-outline"></i></span>
                    <input type="month" class="form-control" id="periodePremiBersama" value="{{ now()->format("Y-m") }}">
                </div>
                <p class="pb-period-note mb-0">
                    Preview diperbarui otomatis ketika periode diganti.
                </p>
            </div>
        </section>

        <div class="pb-toolbar">
            <div class="d-flex align-items-center flex-wrap gap-2">
                <span class="pb-toolbar-label">Mode Generate</span>
                <div class="pb-type-switch">
                    <button type="button" class="pb-type-btn active" data-type="umum">UMUM</button>
                    <button type="button" class="pb-type-btn" data-type="bpjs">BPJS</button>
                </div>
            </div>
            <div class="pb-action-group">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnConfigPremiBersama">
                    <i class="mdi mdi-cog-outline"></i> Konfigurasi
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm" id="btnRefreshPremiBersama">
                    <i class="mdi mdi-refresh"></i> Refresh
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="btnGeneratePremiBersama" disabled>
                    <i class="mdi mdi-play-circle-outline"></i> Generate UMUM
                </button>
            </div>
        </div>

        <div class="pb-summary-grid">
            <div class="pb-metric">
                <div class="pb-metric-top">
                    <div>
                        <div class="pb-metric-label">Grand Total</div>
                        <div class="pb-metric-value" id="summaryGrandTotal">Rp 0</div>
                    </div>
                    <span class="pb-metric-icon teal"><i class="mdi mdi-cash-multiple"></i></span>
                </div>
                <div class="pb-metric-foot" id="summaryGrandFoot">Generator + tindakan</div>
            </div>
            <div class="pb-metric">
                <div class="pb-metric-top">
                    <div>
                        <div class="pb-metric-label">Generator</div>
                        <div class="pb-metric-value" id="summaryGeneratorTotal">Rp 0</div>
                    </div>
                    <span class="pb-metric-icon amber"><i class="mdi mdi-source-branch"></i></span>
                </div>
                <div class="pb-metric-foot" id="summaryGeneratorFoot">0 sumber siap</div>
            </div>
            <div class="pb-metric">
                <div class="pb-metric-top">
                    <div>
                        <div class="pb-metric-label">Tindakan Rawat</div>
                        <div class="pb-metric-value" id="summaryRawatTotal">Rp 0</div>
                    </div>
                    <span class="pb-metric-icon"><i class="mdi mdi-stethoscope"></i></span>
                </div>
                <div class="pb-metric-foot" id="summaryRawatFoot">0 transaksi</div>
            </div>
            <div class="pb-metric">
                <div class="pb-metric-top">
                    <div>
                        <div class="pb-metric-label">Pegawai Skor</div>
                        <div class="pb-metric-value" id="summaryPenerima">0</div>
                    </div>
                    <span class="pb-metric-icon rose"><i class="mdi mdi-account-star-outline"></i></span>
                </div>
                <div class="pb-metric-foot" id="summarySkorFoot">Total skor 0</div>
            </div>
            <div class="pb-metric">
                <div class="pb-metric-top">
                    <div>
                        <div class="pb-metric-label">Dibagikan</div>
                        <div class="pb-metric-value" id="summaryDibagikan">Rp 0</div>
                    </div>
                    <span class="pb-metric-icon slate"><i class="mdi mdi-account-cash-outline"></i></span>
                </div>
                <div class="pb-metric-foot" id="summaryReadyText">Belum siap</div>
            </div>
        </div>

        <section class="pb-panel">
            <div class="pb-panel-head">
                <div>
                    <h5 class="pb-panel-title">Kesiapan Generate</h5>
                    <div class="pb-panel-note" id="readinessMessage">Memuat preview...</div>
                </div>
            </div>
            <div class="pb-panel-body">
                <div class="pb-step-grid" id="readinessSteps"></div>
            </div>
        </section>

        <section class="pb-panel">
            <div class="pb-panel-head">
                <div class="pb-panel-title-wrap">
                    <span class="pb-panel-head-icon amber"><i class="mdi mdi-source-branch"></i></span>
                    <div>
                        <h5 class="pb-panel-title">Nilai dari Generator</h5>
                        <div class="pb-panel-note">Hanya nilai dari sumber yang terkunci yang masuk ke grand total.</div>
                    </div>
                </div>
                <div class="pb-panel-head-meta" id="generatorPanelMeta">
                    <span class="pb-mini-pill"><i class="mdi mdi-lock-check-outline"></i> <strong id="generatorMetaLocked">0/0</strong> siap</span>
                    <span class="pb-mini-pill success"><i class="mdi mdi-chart-donut"></i> <strong id="generatorMetaShare">0%</strong> kontribusi</span>
                </div>
            </div>
            <div class="pb-panel-body">
                <div class="pb-source-grid" id="sourceGeneratorGrid"></div>
            </div>
        </section>

        <section class="pb-panel">
            <div class="pb-panel-head">
                <div class="pb-panel-title-wrap">
                    <span class="pb-panel-head-icon"><i class="mdi mdi-stethoscope"></i></span>
                    <div>
                        <h5 class="pb-panel-title">Perhitungan Tindakan Rawat</h5>
                        <div class="pb-panel-note">Nilai bersama dari mapping premi terpilih.</div>
                    </div>
                </div>
                <div class="pb-panel-head-meta" id="rawatPanelMeta">
                    <span class="pb-mini-pill"><i class="mdi mdi-format-list-checks"></i> <strong id="rawatMetaAction">0</strong> tindakan</span>
                    <span class="pb-mini-pill warning"><i class="mdi mdi-star-four-points-outline"></i> <strong id="rawatMetaTop">-</strong></span>
                </div>
            </div>
            <div class="pb-panel-body">
                <div class="pb-preview-strip">
                    <div class="pb-preview-stat">
                        <span class="pb-preview-stat-icon"><i class="mdi mdi-clipboard-pulse-outline"></i></span>
                        <div class="min-w-0">
                            <div class="pb-preview-stat-label">Tindakan aktif</div>
                            <div class="pb-preview-stat-value" id="rawatPreviewActionCount">0 mapping</div>
                        </div>
                    </div>
                    <div class="pb-preview-stat">
                        <span class="pb-preview-stat-icon blue"><i class="mdi mdi-receipt-text-outline"></i></span>
                        <div class="min-w-0">
                            <div class="pb-preview-stat-label">Transaksi</div>
                            <div class="pb-preview-stat-value" id="rawatPreviewTransactionCount">0 data</div>
                        </div>
                    </div>
                    <div class="pb-preview-stat">
                        <span class="pb-preview-stat-icon amber"><i class="mdi mdi-chart-timeline-variant"></i></span>
                        <div class="min-w-0">
                            <div class="pb-preview-stat-label">Kontribusi</div>
                            <div class="pb-preview-stat-value" id="rawatPreviewContribution">Rp 0</div>
                        </div>
                    </div>
                </div>
                <div class="pb-filter-bar">
                    <div class="pb-filter-group">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                            <input type="text" class="form-control" id="rawatFilterSearch" placeholder="Cari kode, nama tindakan, atau formula">
                        </div>
                        <select class="form-select form-select-sm" id="rawatFilterJenis">
                            <option value="all">Semua jenis</option>
                            <option value="persen">Persen</option>
                            <option value="nominal">Nominal</option>
                        </select>
                        <select class="form-select form-select-sm" id="rawatFilterData">
                            <option value="all">Semua data</option>
                            <option value="with_data">Ada transaksi</option>
                            <option value="empty">Tanpa transaksi</option>
                        </select>
                        <select class="form-select form-select-sm" id="rawatFilterSort">
                            <option value="hasil_desc">Hasil terbesar</option>
                            <option value="hasil_asc">Hasil terkecil</option>
                            <option value="data_desc">Data terbanyak</option>
                            <option value="kode_asc">Kode A-Z</option>
                        </select>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetRawatFilter" title="Reset filter tindakan">
                            <i class="mdi mdi-filter-remove-outline"></i>
                        </button>
                    </div>
                    <div class="pb-filter-info" id="rawatFilterInfo">Menampilkan semua tindakan.</div>
                </div>
                <div class="pb-table-wrap pb-table-scroll lg">
                    <table class="table table-hover pb-table" id="previewDetailTable">
                        <thead>
                            <tr>
                                <th>Tindakan</th>
                                <th>Jenis</th>
                                <th>Nilai</th>
                                <th>Data</th>
                                <th>Dasar</th>
                                <th>Hasil</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="pb-panel">
            <div class="pb-panel-head">
                <div class="pb-panel-title-wrap">
                    <span class="pb-panel-head-icon rose"><i class="mdi mdi-account-star-outline"></i></span>
                    <div>
                        <h5 class="pb-panel-title">Distribusi Skor Pegawai</h5>
                        <div class="pb-panel-note">Formula: skor pegawai / total skor x grand total.</div>
                    </div>
                </div>
                <div class="pb-panel-head-meta" id="distributionPanelMeta">
                    <span class="pb-mini-pill"><i class="mdi mdi-account-multiple-outline"></i> <strong id="distributionMetaRecipient">0</strong> pegawai</span>
                    <span class="pb-mini-pill success"><i class="mdi mdi-cash-fast"></i> <strong id="distributionMetaPaid">Rp 0</strong></span>
                </div>
            </div>
            <div class="pb-panel-body">
                <div class="pb-preview-strip">
                    <div class="pb-preview-stat">
                        <span class="pb-preview-stat-icon blue"><i class="mdi mdi-account-group-outline"></i></span>
                        <div class="min-w-0">
                            <div class="pb-preview-stat-label">Penerima</div>
                            <div class="pb-preview-stat-value" id="distPreviewRecipientCount">0 pegawai</div>
                        </div>
                    </div>
                    <div class="pb-preview-stat">
                        <span class="pb-preview-stat-icon amber"><i class="mdi mdi-counter"></i></span>
                        <div class="min-w-0">
                            <div class="pb-preview-stat-label">Total skor</div>
                            <div class="pb-preview-stat-value" id="distPreviewScoreTotal">0</div>
                        </div>
                    </div>
                    <div class="pb-preview-stat">
                        <span class="pb-preview-stat-icon"><i class="mdi mdi-trophy-outline"></i></span>
                        <div class="min-w-0">
                            <div class="pb-preview-stat-label">Teratas</div>
                            <div class="pb-preview-stat-value" id="distPreviewTopReceiver">-</div>
                        </div>
                    </div>
                </div>
                <div class="pb-filter-bar">
                    <div class="pb-filter-group distribution">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                            <input type="text" class="form-control" id="distributionFilterSearch" placeholder="Cari pegawai, NIK, atau posisi">
                        </div>
                        <select class="form-select form-select-sm" id="distributionFilterSort">
                            <option value="received_desc">Diterima terbesar</option>
                            <option value="score_desc">Skor tertinggi</option>
                            <option value="percent_desc">Persentase terbesar</option>
                            <option value="name_asc">Nama A-Z</option>
                        </select>
                        <select class="form-select form-select-sm" id="distributionFilterLimit">
                            <option value="25">Top 25</option>
                            <option value="50">Top 50</option>
                            <option value="all">Semua</option>
                        </select>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetDistributionFilter" title="Reset filter distribusi">
                            <i class="mdi mdi-filter-remove-outline"></i>
                        </button>
                    </div>
                    <div class="pb-filter-info" id="distributionFilterInfo">Menampilkan penerima teratas.</div>
                </div>
                <div class="pb-table-wrap pb-table-scroll">
                    <table class="table table-hover pb-table" id="previewDistributionTable">
                        <thead>
                            <tr>
                                <th>Pegawai</th>
                                <th>Skor</th>
                                <th>%</th>
                                <th>Diterima</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="pb-panel">
            <div class="pb-panel-head">
                <div>
                    <h5 class="pb-panel-title">Riwayat Generate Premi Bersama</h5>
                    <div class="pb-panel-note">Data yang sudah digenerate dapat dikunci sebagai snapshot final.</div>
                </div>
            </div>
            <div class="pb-panel-body">
                <div class="pb-table-wrap">
                    <table id="tablePremiBersama" class="table table-hover pb-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Periode</th>
                                <th>Jenis</th>
                                <th>Mapping Premi</th>
                                <th>Generator</th>
                                <th>Tindakan</th>
                                <th>Grand Total</th>
                                <th>Penerima</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </section>

        @include("simrs.backOffice.keuangan.hitungPremi.generatePremiBersama.modal")
    </div>
@endsection

@push("scripts")
    @include("simrs.backOffice.keuangan.hitungPremi.generatePremiBersama.jsMain")
@endpush
