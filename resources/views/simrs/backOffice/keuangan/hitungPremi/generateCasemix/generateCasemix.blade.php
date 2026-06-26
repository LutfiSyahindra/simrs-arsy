@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.select2")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")

    <style>
        .casemix-page {
            --cmx-indigo: #4f46e5;
            --cmx-cyan: #0891b2;
            --cmx-ink: #0f172a;
            --cmx-muted: #64748b;
            --cmx-line: #e2e8f0;
            --cmx-soft: #eef2ff;
            color: #172033;
        }

        .casemix-page .breadcrumb {
            background: transparent;
            font-size: 13px;
            margin-bottom: 0;
            padding: 0;
        }

        .casemix-hero {
            align-items: stretch;
            background: linear-gradient(135deg, #111827 0%, #4f46e5 56%, #0891b2 100%);
            border-radius: 18px;
            box-shadow: 0 20px 42px rgba(15, 23, 42, .18);
            color: #fff;
            display: flex;
            gap: 22px;
            justify-content: space-between;
            margin-top: 15px;
            overflow: hidden;
            padding: 22px;
            position: relative;
        }

        .casemix-hero::after {
            background: rgba(255, 255, 255, .10);
            content: "";
            height: 100%;
            position: absolute;
            right: 0;
            top: 0;
            width: 32%;
        }

        .casemix-hero-main,
        .casemix-hero-controls,
        .casemix-hero > * {
            position: relative;
            z-index: 1;
        }

        .casemix-hero-main {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            min-width: 0;
        }

        .casemix-hero-icon {
            align-items: center;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .24);
            border-radius: 14px;
            display: flex;
            flex: 0 0 auto;
            font-size: 31px;
            height: 58px;
            justify-content: center;
            width: 58px;
        }

        .casemix-eyebrow {
            color: #c7d2fe;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .casemix-title {
            font-size: 24px;
            font-weight: 900;
            margin: 3px 0 5px;
        }

        .casemix-description {
            color: rgba(255, 255, 255, .80);
            font-size: 13px;
            margin: 0;
            max-width: 790px;
        }

        .casemix-hero-controls {
            display: grid;
            flex: 0 0 320px;
            gap: 10px;
        }

        .casemix-control-box {
            background: rgba(255, 255, 255, .15);
            border: 1px solid rgba(255, 255, 255, .26);
            border-radius: 14px;
            padding: 12px;
        }

        .casemix-control-box label {
            color: #e0e7ff;
            display: block;
            font-size: 11px;
            font-weight: 900;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .casemix-period-picker {
            align-items: center;
            display: grid;
            gap: 7px;
            grid-template-columns: 38px minmax(0, 1fr) 38px;
        }

        .casemix-period-picker .form-control {
            border: 0;
            height: 39px;
            text-align: center;
        }

        .casemix-period-step,
        .casemix-period-current {
            align-items: center;
            background: rgba(255, 255, 255, .18);
            border: 1px solid rgba(255, 255, 255, .25);
            border-radius: 9px;
            color: #fff;
            display: inline-flex;
            font-size: 17px;
            height: 39px;
            justify-content: center;
            padding: 0;
        }

        .casemix-period-current {
            font-size: 11px;
            font-weight: 900;
            gap: 5px;
            margin-top: 8px;
            width: 100%;
        }

        .casemix-panel {
            background: #fff;
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 16px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, .06);
            margin-top: 16px;
            overflow: hidden;
        }

        .casemix-panel-head,
        .casemix-filter-bar {
            align-items: center;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            padding: 15px 17px;
        }

        .casemix-panel-head {
            border-bottom: 1px solid #eef2f7;
        }

        .casemix-panel-title {
            color: var(--cmx-ink);
            font-size: 15px;
            font-weight: 900;
        }

        .casemix-panel-subtitle,
        .casemix-simple-text,
        .casemix-simple-section-subtitle {
            color: var(--cmx-muted);
            font-size: 12px;
            line-height: 1.45;
            margin-top: 3px;
        }

        .casemix-panel-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .casemix-btn {
            align-items: center;
            border-radius: 9px;
            display: inline-flex;
            font-size: 12px;
            font-weight: 900;
            gap: 6px;
            padding: 10px 12px;
        }

        .casemix-btn-light {
            background: #fff;
            border: 1px solid var(--cmx-line);
            color: #475569;
        }

        .casemix-btn-primary {
            background: linear-gradient(135deg, #4f46e5, #0891b2);
            border: 0;
            box-shadow: 0 10px 22px rgba(79, 70, 229, .20);
            color: #fff;
        }

        .casemix-summary {
            background: linear-gradient(135deg, #eef2ff, #ecfeff);
            display: grid;
            gap: 14px;
            grid-template-columns: minmax(280px, 1.1fr) minmax(280px, .9fr);
            padding: 17px;
        }

        .casemix-highlight {
            background: linear-gradient(135deg, #111827, #4f46e5 60%, #0e7490);
            border-radius: 14px;
            box-shadow: 0 18px 34px rgba(15, 23, 42, .18);
            color: #fff;
            min-width: 0;
            padding: 18px;
        }

        .casemix-summary-badge {
            align-items: center;
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .25);
            border-radius: 999px;
            color: #fff;
            display: inline-flex;
            font-size: 10px;
            font-weight: 900;
            gap: 5px;
            padding: 6px 9px;
            white-space: nowrap;
        }

        .casemix-highlight-label,
        .casemix-micro-label,
        .casemix-stack-label,
        .casemix-data-label,
        .casemix-mini-label {
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .casemix-highlight-label {
            color: #c7d2fe;
            margin-top: 16px;
        }

        .casemix-highlight-value {
            color: #fff;
            font-size: 32px;
            font-weight: 950;
            line-height: 1.08;
            margin-top: 6px;
            overflow-wrap: anywhere;
        }

        .casemix-highlight-note {
            color: rgba(255, 255, 255, .78);
            font-size: 12px;
            margin-top: 7px;
        }

        .casemix-micro-grid,
        .casemix-stack,
        .casemix-data-band,
        .casemix-mini-grid,
        .casemix-config-grid {
            display: grid;
            gap: 10px;
        }

        .casemix-micro-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 18px;
        }

        .casemix-micro {
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 11px;
            padding: 10px;
        }

        .casemix-micro-label {
            color: #e0e7ff;
        }

        .casemix-micro-value {
            color: #fff;
            font-size: 19px;
            font-weight: 950;
            margin-top: 3px;
        }

        .casemix-stack {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .casemix-stack-card,
        .casemix-data-item,
        .casemix-mini-card,
        .casemix-simple-section,
        .casemix-config-section {
            background: #fff;
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 13px;
            min-width: 0;
            padding: 14px;
        }

        .casemix-stack-card.wide,
        .casemix-data-band {
            grid-column: 1 / -1;
        }

        .casemix-stack-label,
        .casemix-data-label,
        .casemix-mini-label {
            color: #64748b;
        }

        .casemix-stack-value,
        .casemix-data-value,
        .casemix-mini-value {
            color: #0f172a;
            font-weight: 950;
            margin-top: 5px;
            overflow-wrap: anywhere;
        }

        .casemix-stack-value {
            font-size: 20px;
        }

        .casemix-data-band {
            border-top: 1px solid rgba(226, 232, 240, .85);
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            padding-top: 13px;
        }

        .casemix-data-item {
            align-items: center;
            display: grid;
            gap: 10px;
            grid-template-columns: 34px minmax(0, 1fr);
            padding: 11px;
        }

        .casemix-data-icon {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            color: #475569;
            display: inline-flex;
            font-size: 17px;
            height: 34px;
            justify-content: center;
            width: 34px;
        }

        .casemix-formula-strip {
            background: #fff;
            border-top: 1px solid #eef2f7;
            padding: 16px 17px 18px;
        }

        .casemix-formula-head,
        .casemix-simple-head,
        .casemix-filter-bar {
            align-items: flex-start;
            display: flex;
            gap: 12px;
            justify-content: space-between;
        }

        .casemix-formula-title,
        .casemix-simple-title,
        .casemix-simple-section-title,
        .casemix-config-section h6 {
            color: #0f172a;
            font-size: 14px;
            font-weight: 950;
        }

        .casemix-formula-badge,
        .casemix-simple-badge {
            align-items: center;
            background: linear-gradient(135deg, #eef2ff, #ecfeff);
            border: 1px solid #c7d2fe;
            border-radius: 999px;
            color: #3730a3;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 10px;
            font-weight: 950;
            gap: 5px;
            padding: 6px 10px;
            white-space: nowrap;
        }

        .casemix-formula-grid,
        .casemix-mini-grid,
        .casemix-config-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-top: 12px;
        }

        .casemix-formula-card {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(226, 232, 240, .95);
            border-radius: 13px;
            min-height: 118px;
            padding: 13px;
        }

        .casemix-formula-card i {
            color: #4f46e5;
            font-size: 20px;
        }

        .casemix-formula-card span {
            color: #64748b;
            display: block;
            font-size: 10px;
            font-weight: 900;
            margin-top: 10px;
            text-transform: uppercase;
        }

        .casemix-formula-card strong {
            color: #0f172a;
            display: block;
            font-size: 16px;
            font-weight: 950;
            margin-top: 4px;
            overflow-wrap: anywhere;
        }

        .casemix-filter-bar {
            border-bottom: 1px solid #eef2f7;
            padding: 13px 17px;
        }

        .casemix-search {
            max-width: 340px;
            width: 100%;
        }

        .casemix-table-wrap {
            padding: 8px 17px 17px;
        }

        .dataTables_wrapper .dataTables_filter {
            display: none;
        }

        .casemix-actions {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .casemix-actions .btn {
            align-items: center;
            display: inline-flex;
            height: 31px;
            justify-content: center;
            padding: 0;
            width: 31px;
        }

        .casemix-lock,
        .casemix-grade {
            border-radius: 999px;
            display: inline-flex;
            font-size: 10px;
            font-weight: 900;
            gap: 4px;
            padding: 4px 8px;
        }

        .casemix-lock.locked {
            background: #fef3c7;
            color: #92400e;
        }

        .casemix-lock.open {
            background: #f1f5f9;
            color: #64748b;
        }

        .casemix-grade {
            background: #eef2ff;
            color: #3730a3;
        }

        #modalGenerateCasemix .modal-content,
        #modalConfigCasemix .modal-content,
        #modalDetailCasemix .modal-content {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 42%, #f1f5f9 100%);
            border: 1px solid rgba(226, 232, 240, .85);
            border-radius: 16px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .10);
            overflow: hidden;
            position: relative;
        }

        #modalGenerateCasemix .modal-content::before,
        #modalConfigCasemix .modal-content::before,
        #modalDetailCasemix .modal-content::before {
            background: linear-gradient(90deg, #4f46e5, #0891b2);
            content: "";
            height: 4px;
            left: 0;
            position: absolute;
            right: 0;
            top: 0;
            z-index: 2;
        }

        #modalGenerateCasemix .modal-dialog,
        #modalConfigCasemix .modal-dialog,
        #modalDetailCasemix .modal-dialog {
            max-width: 1120px;
        }

        .casemix-simple-head {
            background: linear-gradient(135deg, #111827, #4f46e5 62%, #0891b2);
            border-radius: 14px;
            color: #fff;
            margin-bottom: 14px;
            padding: 15px;
        }

        .casemix-simple-main {
            align-items: center;
            display: flex;
            gap: 12px;
            min-width: 0;
        }

        .casemix-simple-icon {
            align-items: center;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .24);
            border-radius: 13px;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 24px;
            height: 46px;
            justify-content: center;
            width: 46px;
        }

        .casemix-simple-head .casemix-simple-title {
            color: #fff;
        }

        .casemix-simple-head .casemix-simple-text {
            color: rgba(255, 255, 255, .78);
        }

        .casemix-generate-layout {
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(280px, .82fr) minmax(360px, 1.18fr);
        }

        .casemix-claim-section {
            align-self: stretch;
        }

        .casemix-input-stack,
        .casemix-live-section,
        .casemix-config-answer-list {
            display: grid;
            gap: 10px;
        }

        .casemix-loss-panel,
        .casemix-score-panel {
            background: #fff;
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 13px;
            min-width: 0;
            padding: 14px;
        }

        .casemix-loss-head,
        .casemix-score-head,
        .casemix-section-toolbar,
        .casemix-meter-head,
        .casemix-meter-foot {
            align-items: flex-start;
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }

        .casemix-loss-title {
            align-items: center;
            color: #0f172a;
            display: flex;
            font-size: 14px;
            font-weight: 950;
            gap: 7px;
        }

        .casemix-loss-ratio {
            background: #ecfeff;
            border: 1px solid #a5f3fc;
            border-radius: 999px;
            color: #0e7490;
            flex: 0 0 auto;
            font-size: 10px;
            font-weight: 950;
            padding: 6px 9px;
        }

        .casemix-loss-grid {
            display: grid;
            gap: 9px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 12px;
        }

        .casemix-loss-item {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 11px;
            display: grid;
            gap: 9px;
            grid-template-columns: 32px minmax(0, 1fr);
            padding: 10px;
        }

        .casemix-loss-item i {
            align-items: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            color: #64748b;
            display: inline-flex;
            font-size: 16px;
            height: 32px;
            justify-content: center;
            width: 32px;
        }

        .casemix-loss-item span {
            color: #64748b;
            display: block;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .casemix-loss-item strong {
            color: #0f172a;
            display: block;
            font-size: 14px;
            font-weight: 950;
            margin-top: 2px;
            overflow-wrap: anywhere;
        }

        .casemix-loss-item.good {
            background: #ecfdf5;
            border-color: #bbf7d0;
        }

        .casemix-loss-item.good i {
            color: #15803d;
        }

        .casemix-loss-item.bad {
            background: #fff7ed;
            border-color: #fed7aa;
        }

        .casemix-loss-item.bad i {
            color: #c2410c;
        }

        .casemix-meter {
            margin-top: 13px;
        }

        .casemix-meter-head span,
        .casemix-meter-foot span {
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
        }

        .casemix-meter-head strong {
            color: #0f172a;
            font-size: 18px;
            font-weight: 950;
        }

        .casemix-meter-track {
            background: #e2e8f0;
            border-radius: 999px;
            height: 10px;
            margin: 8px 0 7px;
            overflow: hidden;
        }

        .casemix-meter-fill {
            border-radius: inherit;
            height: 100%;
            transition: width .22s ease;
        }

        .casemix-meter-fill.excellent {
            background: linear-gradient(90deg, #059669, #0891b2);
        }

        .casemix-meter-fill.good {
            background: linear-gradient(90deg, #4f46e5, #0891b2);
        }

        .casemix-meter-fill.low {
            background: linear-gradient(90deg, #f97316, #ef4444);
        }

        .casemix-tier-excellent {
            background: #ecfdf5;
            border-color: #bbf7d0;
            color: #047857;
        }

        .casemix-tier-good {
            background: #eef2ff;
            border-color: #c7d2fe;
            color: #3730a3;
        }

        .casemix-tier-low {
            background: #fff7ed;
            border-color: #fed7aa;
            color: #c2410c;
        }

        .casemix-score-panel .casemix-mini-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .casemix-mini-card.total {
            background: linear-gradient(135deg, #4f46e5, #0891b2);
            border: 0;
            color: #fff;
        }

        .casemix-mini-card.total .casemix-mini-label,
        .casemix-mini-card.total .casemix-mini-value {
            color: #fff;
        }

        .casemix-question-list,
        .casemix-recipient-list {
            display: grid;
            gap: 10px;
        }

        .casemix-question-card {
            background: #fff;
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 13px;
            padding: 13px;
        }

        .casemix-question-title {
            color: #0f172a;
            font-size: 13px;
            font-weight: 950;
            line-height: 1.35;
        }

        .casemix-answer-grid {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-top: 10px;
        }

        .casemix-answer-option {
            align-items: flex-start;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 11px;
            cursor: pointer;
            display: flex;
            gap: 8px;
            min-width: 0;
            padding: 10px;
        }

        .casemix-answer-option:hover,
        .casemix-answer-option:has(input:checked) {
            background: #eef2ff;
            border-color: #818cf8;
            box-shadow: 0 8px 18px rgba(79, 70, 229, .08);
        }

        .casemix-answer-option:has(input:checked) strong {
            color: #3730a3;
        }

        .casemix-answer-option input {
            margin-top: 2px;
        }

        .casemix-answer-option strong,
        .casemix-recipient-name {
            color: #0f172a;
            display: block;
            font-size: 12px;
            font-weight: 950;
        }

        .casemix-answer-option small,
        .casemix-recipient-meta,
        .casemix-field-help {
            color: #64748b;
            display: block;
            font-size: 11px;
            line-height: 1.4;
            margin-top: 3px;
        }

        .casemix-recipient-item {
            align-items: center;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 12px;
            display: grid;
            gap: 9px;
            grid-template-columns: minmax(0, 1fr) auto;
            padding: 11px 12px;
        }

        .casemix-recipient-total {
            color: #4f46e5;
            font-size: 13px;
            font-weight: 950;
            text-align: right;
        }

        .casemix-empty-state {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            color: #64748b;
            font-size: 12px;
            padding: 12px;
        }

        .casemix-config-section h6 {
            align-items: center;
            display: flex;
            gap: 7px;
            margin-bottom: 12px;
        }

        .casemix-config-section .form-label {
            color: #334155;
            font-size: 11px;
            font-weight: 900;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .casemix-config-answer-list {
            margin-top: 11px;
        }

        .casemix-config-answer-row {
            align-items: end;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            display: grid;
            gap: 10px;
            grid-template-columns: 38px minmax(0, 1fr) 115px;
            padding: 10px;
        }

        .casemix-config-answer-icon {
            align-items: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            color: #4f46e5;
            display: inline-flex;
            font-size: 18px;
            height: 38px;
            justify-content: center;
            width: 38px;
        }

        @media (max-width: 991.98px) {
            .casemix-hero,
            .casemix-panel-head,
            .casemix-filter-bar,
            .casemix-formula-head,
            .casemix-simple-head,
            .casemix-section-toolbar,
            .casemix-loss-head,
            .casemix-score-head,
            .casemix-meter-foot {
                align-items: stretch;
                flex-direction: column;
            }

            .casemix-hero-controls,
            .casemix-panel-actions,
            .casemix-search {
                flex-basis: auto;
                max-width: none;
                width: 100%;
            }

            .casemix-summary,
            .casemix-stack,
            .casemix-data-band,
            .casemix-formula-grid,
            .casemix-mini-grid,
            .casemix-config-grid,
            .casemix-generate-layout,
            .casemix-loss-grid,
            .casemix-answer-grid,
            .casemix-micro-grid {
                grid-template-columns: 1fr;
            }

            .casemix-config-answer-row {
                align-items: stretch;
                grid-template-columns: 1fr;
            }

            .casemix-recipient-item {
                grid-template-columns: 1fr;
            }

            .casemix-recipient-total {
                text-align: left;
            }
        }
    </style>
@endpush

@section("content")
    <div class="casemix-page">
        <nav class="page-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route("backOffice.keuangan.hitungPremi") }}">Generate Premi</a>
                </li>
                <li class="breadcrumb-item active">Generate Casemix</li>
            </ol>
        </nav>

        <section class="casemix-hero">
            <div class="casemix-hero-main">
                <div class="casemix-hero-icon">
                    <i class="mdi mdi-file-chart-outline"></i>
                </div>
                <div>
                    <div class="casemix-eyebrow">Questionnaire Based Reward</div>
                    <h4 class="casemix-title">Generate Premi Casemix</h4>
                    <p class="casemix-description">
                        Input biaya RS, tarif BPJS, realisasi verifikasi BPJS, lalu jawab indikator kinerja tim.
                        Skor dikonversi menjadi persentase reward dan dibagikan ke Ketua, Kanit/Coding, dan Inputer.
                    </p>
                </div>
            </div>
            <div class="casemix-hero-controls">
                <div class="casemix-control-box">
                    <label for="periodeCasemix">Periode Perhitungan</label>
                    <div class="casemix-period-picker">
                        <button type="button" class="casemix-period-step" id="btnPrevPeriodCasemix">
                            <i class="mdi mdi-chevron-left"></i>
                        </button>
                        <input type="month" id="periodeCasemix" class="form-control">
                        <button type="button" class="casemix-period-step" id="btnNextPeriodCasemix">
                            <i class="mdi mdi-chevron-right"></i>
                        </button>
                    </div>
                    <button type="button" class="casemix-period-current" id="btnCurrentPeriodCasemix">
                        <i class="mdi mdi-calendar-today-outline"></i>
                        Bulan Ini
                    </button>
                </div>
            </div>
        </section>

        <section class="casemix-panel">
            <div class="casemix-panel-head">
                <div>
                    <div class="casemix-panel-title">Ringkasan Generate Casemix</div>
                    <div class="casemix-panel-subtitle" id="summaryCasemixSubtitle">
                        Data dihitung dari hasil generate periode aktif.
                    </div>
                </div>
                <div class="casemix-panel-actions">
                    <button type="button" class="casemix-btn casemix-btn-light" id="btnRefreshCasemix">
                        <i class="mdi mdi-refresh"></i>
                        Refresh
                    </button>
                    <button type="button" class="casemix-btn casemix-btn-light" id="btnOpenConfigCasemix">
                        <i class="mdi mdi-cog-outline"></i>
                        Konfigurasi
                    </button>
                    <button type="button" class="casemix-btn casemix-btn-primary" id="btnOpenGenerateCasemix">
                        <i class="mdi mdi-calculator-variant-outline"></i>
                        Generate Casemix
                    </button>
                </div>
            </div>
            <div class="casemix-summary">
                <div class="casemix-highlight">
                    <span class="casemix-summary-badge" id="summaryCasemixCount">
                        <i class="mdi mdi-file-check-outline"></i>
                        0 data generate
                    </span>
                    <div class="casemix-highlight-label">Total Premi Tim Casemix</div>
                    <div class="casemix-highlight-value" id="summaryCasemixTeamPool">Rp 0</div>
                    <div class="casemix-highlight-note" id="summaryCasemixNote">
                        70% dari reward Casemix akan menjadi pool tim.
                    </div>
                    <div class="casemix-micro-grid">
                        <div class="casemix-micro">
                            <div class="casemix-micro-label">Score</div>
                            <div class="casemix-micro-value" id="summaryCasemixScore">0%</div>
                        </div>
                        <div class="casemix-micro">
                            <div class="casemix-micro-label">Reward</div>
                            <div class="casemix-micro-value" id="summaryCasemixRewardPercent">0%</div>
                        </div>
                    </div>
                </div>

                <div class="casemix-stack">
                    <div class="casemix-stack-card wide">
                        <div class="casemix-stack-label">Total Reward dari BPJS</div>
                        <div class="casemix-stack-value" id="summaryCasemixReward">Rp 0</div>
                        <div class="casemix-panel-subtitle">Persentase reward dikalikan verifikasi hasil BPJS.</div>
                    </div>
                    <div class="casemix-stack-card">
                        <div class="casemix-stack-label">Verifikasi Hasil BPJS</div>
                        <div class="casemix-stack-value" id="summaryCasemixVerifikasi">Rp 0</div>
                    </div>
                    <div class="casemix-stack-card">
                        <div class="casemix-stack-label">Terkunci</div>
                        <div class="casemix-stack-value" id="summaryCasemixLocked">0</div>
                    </div>
                    <div class="casemix-data-band">
                        <div class="casemix-data-item">
                            <div class="casemix-data-icon"><i class="mdi mdi-account-star-outline"></i></div>
                            <div>
                                <div class="casemix-data-label">Ketua</div>
                                <div class="casemix-data-value" id="summaryCasemixLeader">Rp 0</div>
                            </div>
                        </div>
                        <div class="casemix-data-item">
                            <div class="casemix-data-icon"><i class="mdi mdi-account-tie-outline"></i></div>
                            <div>
                                <div class="casemix-data-label">Kanit / Coding</div>
                                <div class="casemix-data-value" id="summaryCasemixKanit">Rp 0</div>
                            </div>
                        </div>
                        <div class="casemix-data-item">
                            <div class="casemix-data-icon"><i class="mdi mdi-keyboard-outline"></i></div>
                            <div>
                                <div class="casemix-data-label">Inputer</div>
                                <div class="casemix-data-value" id="summaryCasemixInputer">Rp 0</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="casemix-formula-strip" id="formulaCasemixStrip">
                <div class="casemix-formula-head">
                    <div>
                        <div class="casemix-formula-title">Formula Aktif Casemix</div>
                        <div class="casemix-panel-subtitle">Konfigurasi belum dimuat.</div>
                    </div>
                    <span class="casemix-formula-badge">
                        <i class="mdi mdi-progress-clock"></i>
                        Memuat
                    </span>
                </div>
            </div>
        </section>

        <section class="casemix-panel">
            <div class="casemix-filter-bar">
                <div>
                    <div class="casemix-panel-title">Hasil Generate Casemix</div>
                    <div class="casemix-panel-subtitle">Satu periode hanya menyimpan satu hasil aktif.</div>
                </div>
                <div class="casemix-search input-group">
                    <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                    <input type="text" class="form-control" id="searchGenerateCasemix"
                        placeholder="Cari periode / score...">
                </div>
            </div>
            <div class="casemix-table-wrap">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100" id="tableGenerateCasemix">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Periode</th>
                                <th class="text-center">Score</th>
                                <th class="text-center">Reward</th>
                                <th class="text-end">Verifikasi BPJS</th>
                                <th class="text-end">Reward Casemix</th>
                                <th class="text-end">Pool Tim</th>
                                <th class="text-end">Dibagikan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </section>
    </div>

    @include("simrs.backOffice.keuangan.hitungPremi.generateCasemix.modal")
@endsection

@push("scripts")
    @include("simrs.backOffice.keuangan.hitungPremi.generateCasemix.jsMain")
@endpush
