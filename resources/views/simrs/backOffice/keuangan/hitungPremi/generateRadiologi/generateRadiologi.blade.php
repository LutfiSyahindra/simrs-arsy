@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.select2")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")

    <style>
        .radiologi-page {
            --rad-main: #0f766e;
            --rad-blue: #2563eb;
            --rad-deep: #0f172a;
            --rad-line: #e2e8f0;
            --rad-muted: #64748b;
            --rad-soft: #f0fdfa;
            --rad-shadow: 0 18px 45px rgba(15, 23, 42, .10);
            color: #172033;
        }

        .radiologi-page .breadcrumb {
            background: transparent;
            font-size: 13px;
            margin-bottom: 0;
            padding: 0;
        }

        .radiologi-hero {
            align-items: stretch;
            background: linear-gradient(135deg, #0f172a 0%, #0f766e 52%, #2563eb 100%);
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

        .radiologi-hero::after {
            background: radial-gradient(circle, rgba(255, 255, 255, .20), transparent 58%);
            content: "";
            height: 210px;
            position: absolute;
            right: -70px;
            top: -80px;
            width: 210px;
        }

        .radiologi-hero-main {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            min-width: 0;
            position: relative;
            z-index: 1;
        }

        .radiologi-hero-icon {
            align-items: center;
            background: #fff;
            border-radius: 13px;
            color: var(--rad-main);
            display: flex;
            flex: 0 0 auto;
            font-size: 30px;
            height: 58px;
            justify-content: center;
            width: 58px;
        }

        .radiologi-title {
            font-size: 24px;
            font-weight: 800;
            margin: 3px 0 5px;
        }

        .radiologi-eyebrow,
        .radiologi-control-box label {
            color: #ccfbf1;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .radiologi-description {
            color: rgba(255, 255, 255, .82);
            font-size: 13px;
            margin: 0;
            max-width: 760px;
        }

        .radiologi-hero-controls {
            display: grid;
            flex: 0 0 320px;
            gap: 10px;
            position: relative;
            z-index: 1;
        }

        .radiologi-control-box {
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .26);
            border-radius: 14px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .18);
            padding: 12px;
        }

        .radiologi-control-box label {
            display: block;
            margin-bottom: 6px;
        }

        .radiologi-period-picker {
            align-items: center;
            display: grid;
            gap: 7px;
            grid-template-columns: 38px minmax(0, 1fr) 38px;
        }

        .radiologi-period-picker .form-control {
            border: 0;
            height: 39px;
            text-align: center;
        }

        .radiologi-period-step,
        .radiologi-period-current {
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

        .radiologi-period-current {
            font-size: 11px;
            font-weight: 800;
            gap: 5px;
            margin-top: 8px;
            width: 100%;
        }

        .radiologi-type-tabs {
            background: #fff;
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .045);
            display: flex;
            gap: 8px;
            margin: 16px 0;
            padding: 8px;
        }

        .radiologi-type-tab {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 10px;
            color: #64748b;
            display: flex;
            flex: 1;
            gap: 10px;
            padding: 11px 14px;
            text-align: left;
        }

        .radiologi-type-tab i {
            font-size: 23px;
        }

        .radiologi-type-tab strong,
        .radiologi-type-tab small {
            display: block;
        }

        .radiologi-type-tab small {
            font-size: 11px;
        }

        .radiologi-type-tab.active {
            background: linear-gradient(135deg, #ecfdf5, #eff6ff);
            box-shadow: inset 0 0 0 1px #99f6e4, 0 8px 18px rgba(15, 118, 110, .08);
            color: var(--rad-main);
        }

        .radiologi-panel {
            background: #fff;
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 16px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, .06);
            margin-bottom: 16px;
            overflow: hidden;
        }

        .radiologi-panel-head,
        .radiologi-filter-bar {
            align-items: center;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            padding: 15px 17px;
        }

        .radiologi-panel-head {
            border-bottom: 1px solid #eef2f7;
        }

        .radiologi-panel-title {
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
        }

        .radiologi-panel-subtitle {
            color: var(--rad-muted);
            font-size: 11px;
        }

        .radiologi-panel-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .radiologi-btn {
            align-items: center;
            border-radius: 9px;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 6px;
            padding: 10px 12px;
        }

        .radiologi-btn-light {
            background: #fff;
            border: 1px solid var(--rad-line);
            color: #475569;
        }

        .radiologi-btn-primary {
            background: linear-gradient(135deg, #0f766e, #2563eb);
            border: 0;
            box-shadow: 0 10px 22px rgba(15, 118, 110, .20);
            color: #fff;
        }

        .radiologi-summary-grid {
            display: grid;
            gap: 11px;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            padding: 15px 17px;
        }

        .radiologi-summary-card,
        .radiologi-mini-card {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 12px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .045);
            padding: 14px;
        }

        #modalGenerateRadiologi .modal-content,
        #modalConfigRadiologi .modal-content,
        #modalDetailRadiologi .modal-content {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 38%, #f1f5f9 100%);
            border: 1px solid rgba(226, 232, 240, .85);
            border-radius: 16px;
            box-shadow: var(--rad-shadow);
            overflow: hidden;
            position: relative;
        }

        #modalGenerateRadiologi .modal-content::before,
        #modalConfigRadiologi .modal-content::before,
        #modalDetailRadiologi .modal-content::before {
            background: linear-gradient(90deg, #0f766e, #0891b2, #2563eb);
            content: "";
            height: 4px;
            left: 0;
            pointer-events: none;
            position: absolute;
            right: 0;
            top: 0;
            z-index: 2;
        }

        #modalGenerateRadiologi .modal-dialog,
        #modalConfigRadiologi .modal-dialog,
        #modalDetailRadiologi .modal-dialog {
            max-width: 980px;
        }

        #modalGenerateRadiologi .modal-header,
        #modalConfigRadiologi .modal-header,
        #modalDetailRadiologi .modal-header {
            background: rgba(255, 255, 255, .92);
            border-bottom: 1px solid rgba(226, 232, 240, .9);
            padding: 17px 18px 14px;
            position: relative;
            z-index: 1;
        }

        #modalGenerateRadiologi .modal-footer,
        #modalConfigRadiologi .modal-footer,
        #modalDetailRadiologi .modal-footer {
            background: rgba(255, 255, 255, .92);
            border-top: 1px solid rgba(226, 232, 240, .9);
            padding: 13px 18px;
            position: relative;
            z-index: 1;
        }

        #modalGenerateRadiologi .modal-body,
        #modalConfigRadiologi .modal-body,
        #modalDetailRadiologi .modal-body {
            padding: 18px;
            position: relative;
            z-index: 1;
        }

        .radiologi-summary-card.total {
            background: linear-gradient(135deg, #0f766e, #2563eb);
            border: 0;
            color: #fff;
        }

        .radiologi-summary-label,
        .radiologi-mini-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .radiologi-summary-card.total .radiologi-summary-label {
            color: #ccfbf1;
        }

        .radiologi-summary-value,
        .radiologi-mini-value {
            color: #0f172a;
            font-size: 18px;
            font-weight: 900;
            margin-top: 4px;
            overflow-wrap: anywhere;
        }

        .radiologi-summary-card.total .radiologi-summary-value {
            color: #fff;
        }

        .radiologi-mini-card.total {
            background: linear-gradient(135deg, #0f766e, #2563eb);
            border: 0;
            box-shadow: 0 16px 32px rgba(15, 118, 110, .20);
            color: #fff;
        }

        .radiologi-mini-card.total .radiologi-mini-label,
        .radiologi-mini-card.total .radiologi-mini-value,
        .radiologi-mini-card.total .radiologi-mini-note {
            color: #fff;
        }

        .radiologi-mini-card {
            min-width: 0;
        }

        .radiologi-mini-top {
            align-items: center;
            display: flex;
            gap: 8px;
            justify-content: space-between;
        }

        .radiologi-mini-icon {
            align-items: center;
            background: #ecfdf5;
            border: 1px solid #ccfbf1;
            border-radius: 10px;
            color: var(--rad-main);
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 17px;
            height: 32px;
            justify-content: center;
            width: 32px;
        }

        .radiologi-mini-card.total .radiologi-mini-icon {
            background: rgba(255, 255, 255, .16);
            border-color: rgba(255, 255, 255, .24);
            color: #fff;
        }

        .radiologi-mini-note {
            color: #64748b;
            font-size: 10px;
            line-height: 1.35;
            margin-top: 5px;
        }

        .radiologi-formula-strip {
            border-top: 1px solid #eef2f7;
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            padding: 15px 17px;
        }

        .radiologi-formula-chip {
            background: #f8fafc;
            border: 1px solid var(--rad-line);
            border-radius: 10px;
            padding: 11px;
        }

        .radiologi-formula-chip.primary {
            background: #ecfdf5;
            border-color: #99f6e4;
        }

        .radiologi-formula-top {
            align-items: center;
            display: flex;
            gap: 8px;
            margin-bottom: 6px;
        }

        .radiologi-formula-icon {
            align-items: center;
            background: #0f766e;
            border-radius: 8px;
            color: #fff;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 16px;
            height: 30px;
            justify-content: center;
            width: 30px;
        }

        .radiologi-formula-title {
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
        }

        .radiologi-formula-text {
            color: #64748b;
            font-size: 11px;
            margin-top: 3px;
        }

        .radiologi-source-note {
            align-items: flex-start;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, .92);
            border-left: 4px solid var(--rad-main);
            border-radius: 12px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .045);
            color: #115e59;
            display: flex;
            gap: 10px;
            margin-bottom: 14px;
            padding: 13px 14px;
        }

        .radiologi-source-note i {
            font-size: 20px;
            line-height: 1;
            margin-top: 1px;
        }

        .radiologi-source-note strong {
            display: block;
            font-size: 12px;
            margin-bottom: 2px;
        }

        .radiologi-source-note span {
            display: block;
            font-size: 11px;
            line-height: 1.45;
        }

        .radiologi-simple-head,
        .radiologi-simple-section {
            background: #fff;
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 14px;
            padding: 15px;
        }

        .radiologi-simple-head {
            align-items: flex-start;
            background: linear-gradient(135deg, #0f172a, #0f766e 62%, #2563eb);
            border: 0;
            box-shadow: 0 18px 38px rgba(15, 23, 42, .16);
            color: #fff;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 14px;
            overflow: hidden;
            position: relative;
        }

        .radiologi-simple-head::after {
            background: radial-gradient(circle, rgba(255, 255, 255, .22), transparent 58%);
            content: "";
            height: 120px;
            position: absolute;
            right: -30px;
            top: -45px;
            width: 120px;
        }

        .radiologi-simple-main {
            align-items: center;
            display: flex;
            gap: 12px;
            min-width: 0;
            position: relative;
            z-index: 1;
        }

        .radiologi-simple-icon {
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

        .radiologi-simple-title {
            color: inherit;
            font-size: 15px;
            font-weight: 900;
        }

        .radiologi-simple-text,
        .radiologi-simple-section-subtitle {
            color: #64748b;
            font-size: 12px;
            line-height: 1.45;
            margin-top: 3px;
        }

        .radiologi-simple-head .radiologi-simple-text {
            color: rgba(255, 255, 255, .78);
        }

        .radiologi-simple-badge {
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .28);
            border-radius: 999px;
            color: #fff;
            flex: 0 0 auto;
            font-size: 11px;
            font-weight: 900;
            padding: 6px 10px;
            position: relative;
            white-space: nowrap;
            z-index: 1;
        }

        .radiologi-simple-section-title {
            align-items: center;
            color: #0f172a;
            display: flex;
            font-size: 13px;
            font-weight: 900;
            gap: 7px;
            margin-bottom: 10px;
        }

        .radiologi-simple-section-title::before {
            background: linear-gradient(135deg, #0f766e, #2563eb);
            border-radius: 999px;
            content: "";
            height: 8px;
            width: 8px;
        }

        .radiologi-info-list {
            display: grid;
            gap: 8px;
        }

        .radiologi-info-row {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #eef2f7;
            border-radius: 10px;
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(0, 1fr) auto;
            padding: 10px 11px;
        }

        .radiologi-info-row:last-child {
            border-bottom: 1px solid #eef2f7;
        }

        .radiologi-info-label {
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
        }

        .radiologi-info-note {
            color: #64748b;
            font-size: 11px;
            margin-top: 2px;
        }

        .radiologi-info-value {
            color: #0f766e;
            font-size: 12px;
            font-weight: 900;
            overflow-wrap: anywhere;
            text-align: right;
        }

        .radiologi-workflow {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-bottom: 14px;
        }

        .radiologi-workflow-item,
        .radiologi-config-info {
            background: #fff;
            border: 1px solid var(--rad-line);
            border-radius: 12px;
            padding: 12px;
        }

        .radiologi-workflow-item {
            display: grid;
            gap: 8px;
            grid-template-columns: auto minmax(0, 1fr);
        }

        .radiologi-workflow-number {
            align-items: center;
            background: #0f766e;
            border-radius: 999px;
            color: #fff;
            display: inline-flex;
            font-size: 11px;
            font-weight: 900;
            height: 24px;
            justify-content: center;
            width: 24px;
        }

        .radiologi-workflow-title,
        .radiologi-config-info-title {
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
        }

        .radiologi-workflow-text,
        .radiologi-config-info-text {
            color: #64748b;
            font-size: 11px;
            line-height: 1.45;
            margin-top: 3px;
        }

        .radiologi-workflow-text {
            grid-column: 2;
            margin-top: 0;
        }

        .radiologi-config-overview {
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            margin-bottom: 14px;
        }

        .radiologi-config-health {
            display: grid;
            gap: 7px;
            margin-top: 10px;
        }

        .radiologi-config-health-row {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            display: flex;
            gap: 8px;
            padding: 8px;
        }

        .radiologi-config-health-row.ok {
            background: #ecfdf5;
            border-color: #bbf7d0;
            color: #166534;
        }

        .radiologi-config-health-row.warn {
            background: #fffbeb;
            border-color: #fde68a;
            color: #92400e;
        }

        .radiologi-config-health-row i {
            font-size: 17px;
        }

        .radiologi-config-health-row span {
            font-size: 11px;
            font-weight: 800;
        }

        .radiologi-config-section {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 14px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .055);
            padding: 15px;
        }

        .radiologi-config-section h6 {
            align-items: center;
            color: #0f172a;
            display: flex;
            gap: 6px;
            font-size: 13px;
            font-weight: 900;
            margin-bottom: 12px;
        }

        .radiologi-config-section h6 i {
            align-items: center;
            background: linear-gradient(135deg, #0f766e, #2563eb);
            border-radius: 10px;
            color: #fff;
            display: inline-flex;
            font-size: 17px;
            height: 30px;
            justify-content: center;
            width: 30px;
        }

        .radiologi-config-section .form-control,
        .radiologi-config-section .form-select,
        .radiologi-config-section .select2-container--default .select2-selection--multiple {
            border-color: #dbe4ee;
            border-radius: 10px;
            min-height: 40px;
        }

        .radiologi-config-section .input-group-text {
            background: #f8fafc;
            border-color: #dbe4ee;
            color: #475569;
            font-weight: 800;
        }

        .radiologi-config-section .form-label {
            color: #334155;
            font-size: 11px;
            font-weight: 900;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .radiologi-field-help {
            color: #64748b;
            display: block;
            font-size: 10px;
            line-height: 1.4;
            margin-top: 5px;
        }

        .radiologi-quality {
            border-radius: 10px;
            font-size: 12px;
            line-height: 1.5;
            margin-top: 12px;
            padding: 11px 12px;
        }

        .radiologi-quality.ok {
            background: linear-gradient(135deg, #ecfdf5, #eff6ff);
            border: 1px solid #99f6e4;
            color: #065f46;
        }

        .radiologi-quality.warn {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
        }

        .radiologi-live-formula {
            background: linear-gradient(135deg, #f0fdfa, #eff6ff);
            border: 1px solid #99f6e4;
            border-radius: 12px;
            margin-bottom: 14px;
            overflow: hidden;
        }

        .radiologi-live-formula-head {
            align-items: center;
            border-bottom: 1px solid #bfdbfe;
            display: flex;
            gap: 9px;
            justify-content: space-between;
            padding: 11px 12px;
        }

        .radiologi-live-formula-title {
            color: #0f172a;
            font-size: 13px;
            font-weight: 900;
        }

        .radiologi-live-formula-subtitle {
            color: #475569;
            font-size: 11px;
        }

        .radiologi-live-formula-body {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            padding: 12px;
        }

        .radiologi-live-formula-row {
            background: #fff;
            border: 1px solid #dbeafe;
            border-radius: 10px;
            padding: 10px;
        }

        .radiologi-live-formula-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .radiologi-live-formula-value {
            color: #0f172a;
            font-size: 13px;
            font-weight: 900;
            margin-top: 3px;
        }

        .radiologi-ledger {
            background: #fff;
            border: 1px solid var(--rad-line);
            border-radius: 12px;
            overflow: hidden;
        }

        .radiologi-ledger-row {
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            display: grid;
            gap: 12px;
            grid-template-columns: 34px minmax(0, 1fr) auto;
            padding: 10px 12px;
        }

        .radiologi-ledger-row:last-child {
            border-bottom: 0;
        }

        .radiologi-ledger-row.highlight {
            background: #ecfdf5;
        }

        .radiologi-ledger-row.highlight .radiologi-ledger-icon {
            background: #0f766e;
            color: #fff;
        }

        .radiologi-ledger-icon {
            align-items: center;
            background: #ccfbf1;
            border-radius: 9px;
            color: #0f766e;
            display: inline-flex;
            font-size: 17px;
            height: 34px;
            justify-content: center;
            width: 34px;
        }

        .radiologi-ledger-label {
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
        }

        .radiologi-ledger-note {
            color: #64748b;
            font-size: 10px;
            margin-top: 2px;
        }

        .radiologi-ledger-value {
            color: #0f766e;
            font-size: 13px;
            font-weight: 900;
            text-align: right;
            white-space: nowrap;
        }

        .radiologi-filter-bar {
            border-bottom: 1px solid #eef2f7;
            padding: 13px 17px;
        }

        .radiologi-search {
            max-width: 340px;
            width: 100%;
        }

        .radiologi-table-wrap {
            padding: 8px 17px 17px;
        }

        .dataTables_wrapper .dataTables_filter {
            display: none;
        }

        .radiologi-badge,
        .radiologi-lock {
            border-radius: 999px;
            display: inline-flex;
            font-size: 10px;
            font-weight: 800;
            gap: 4px;
            padding: 4px 8px;
        }

        .radiologi-badge.umum {
            background: #dcfce7;
            color: #166534;
        }

        .radiologi-badge.bpjs {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .radiologi-lock.locked {
            background: #fef3c7;
            color: #92400e;
        }

        .radiologi-lock.open {
            background: #f1f5f9;
            color: #64748b;
        }

        .radiologi-actions {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .radiologi-actions .btn {
            align-items: center;
            display: inline-flex;
            height: 31px;
            justify-content: center;
            padding: 0;
            width: 31px;
        }

        .radiologi-modal-hero,
        .radiologi-detail-hero {
            align-items: center;
            background: linear-gradient(135deg, #ecfdf5, #eff6ff);
            border: 1px solid #99f6e4;
            border-radius: 12px;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 14px;
            padding: 13px;
        }

        .radiologi-modal-main {
            align-items: center;
            display: flex;
            gap: 12px;
            min-width: 0;
        }

        .radiologi-modal-badges {
            align-items: flex-end;
            display: flex;
            flex: 0 0 auto;
            flex-direction: column;
            gap: 6px;
        }

        .radiologi-modal-badge {
            align-items: center;
            background: #fff;
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            color: #0f766e;
            display: inline-flex;
            font-size: 10px;
            font-weight: 900;
            gap: 5px;
            padding: 5px 8px;
            white-space: nowrap;
        }

        .radiologi-modal-icon {
            align-items: center;
            background: var(--rad-main);
            border-radius: 10px;
            color: #fff;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 24px;
            height: 44px;
            justify-content: center;
            width: 44px;
        }

        .radiologi-modal-title {
            color: #0f172a;
            font-size: 14px;
            font-weight: 900;
        }

        .radiologi-modal-text {
            color: #475569;
            font-size: 12px;
            margin-top: 2px;
        }

        .radiologi-preview-grid,
        .radiologi-config-grid,
        .radiologi-detail-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .radiologi-config-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .radiologi-recipient-list {
            display: grid;
            gap: 8px;
        }

        .radiologi-recipient-item {
            align-items: center;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 12px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, .035);
            display: grid;
            gap: 9px;
            grid-template-columns: minmax(0, 1fr) auto;
            padding: 11px 12px;
        }

        .radiologi-recipient-avatar {
            align-items: center;
            background: #ccfbf1;
            border: 1px solid #99f6e4;
            border-radius: 10px;
            color: #0f766e;
            display: inline-flex;
            font-size: 12px;
            font-weight: 900;
            height: 38px;
            justify-content: center;
            width: 38px;
        }

        .radiologi-recipient-name {
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
        }

        .radiologi-recipient-meta {
            color: #64748b;
            font-size: 11px;
            margin-top: 2px;
        }

        .radiologi-recipient-total {
            color: var(--rad-main);
            font-size: 13px;
            font-weight: 900;
            text-align: right;
        }

        .radiologi-recipient-status {
            background: #ecfdf5;
            border: 1px solid #99f6e4;
            border-radius: 999px;
            color: #0f766e;
            display: inline-flex;
            font-size: 10px;
            font-weight: 900;
            padding: 4px 8px;
        }

        .radiologi-empty-state {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            color: #64748b;
            font-size: 12px;
            padding: 12px;
        }

        @media (max-width: 991.98px) {
            .radiologi-hero,
            .radiologi-panel-head,
            .radiologi-filter-bar {
                align-items: stretch;
                flex-direction: column;
            }

            .radiologi-hero-controls,
            .radiologi-panel-actions,
            .radiologi-search {
                flex-basis: auto;
                max-width: none;
                width: 100%;
            }

            .radiologi-summary-grid,
            .radiologi-formula-strip,
            .radiologi-workflow,
            .radiologi-config-overview,
            .radiologi-preview-grid,
            .radiologi-config-grid,
            .radiologi-detail-grid {
                grid-template-columns: 1fr;
            }

            .radiologi-modal-hero,
            .radiologi-detail-hero,
            .radiologi-simple-head {
                align-items: flex-start;
                flex-direction: column;
            }

            .radiologi-modal-badges {
                align-items: flex-start;
            }

            .radiologi-live-formula-body,
            .radiologi-ledger-row,
            .radiologi-recipient-item,
            .radiologi-info-row {
                grid-template-columns: 1fr;
            }

            .radiologi-ledger-value,
            .radiologi-recipient-total,
            .radiologi-info-value {
                text-align: left;
            }
        }
    </style>
@endpush

@section("content")
    <div class="radiologi-page">
        <nav class="page-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route("backOffice.keuangan.hitungPremi") }}">Generate Premi</a>
                </li>
                <li class="breadcrumb-item active">Generate Radiologi</li>
            </ol>
        </nav>

        <section class="radiologi-hero">
            <div class="radiologi-hero-main">
                <div class="radiologi-hero-icon">
                    <i class="mdi mdi-radioactive-circle-outline"></i>
                </div>
                <div>
                    <div class="radiologi-eyebrow">Otomatis dari Khanza</div>
                    <h4 class="radiologi-title">Generate Premi Radiologi</h4>
                    <p class="radiologi-description">
                        Sistem membaca transaksi periksa radiologi per periode, memisahkan UMUM dan BPJS berdasarkan
                        penjamin, lalu menghitung premi petugas dan premi bersama dari konfigurasi aktif. Khusus BPJS,
                        data Khanza yang dibaca adalah bulan sebelumnya dari periode generate.
                    </p>
                </div>
            </div>
            <div class="radiologi-hero-controls">
                <div class="radiologi-control-box">
                    <label for="periodeRadiologi">Periode Perhitungan</label>
                    <div class="radiologi-period-picker">
                        <button type="button" class="radiologi-period-step" id="btnPrevPeriodRadiologi">
                            <i class="mdi mdi-chevron-left"></i>
                        </button>
                        <input type="month" id="periodeRadiologi" class="form-control">
                        <button type="button" class="radiologi-period-step" id="btnNextPeriodRadiologi">
                            <i class="mdi mdi-chevron-right"></i>
                        </button>
                    </div>
                    <button type="button" class="radiologi-period-current" id="btnCurrentPeriodRadiologi">
                        <i class="mdi mdi-calendar-today-outline"></i>
                        Bulan Ini
                    </button>
                </div>
            </div>
        </section>

        <div class="radiologi-type-tabs">
            <button type="button" class="radiologi-type-tab active" data-type="umum">
                <i class="mdi mdi-account-cash-outline"></i>
                <span>
                    <strong>UMUM</strong>
                    <small>kd_pj selain BPJ dan -</small>
                </span>
            </button>
            <button type="button" class="radiologi-type-tab" data-type="bpjs">
                <i class="mdi mdi-shield-account-outline"></i>
                <span>
                    <strong>BPJS Kesehatan</strong>
                    <small>kd_pj BPJ, data bulan kemarin</small>
                </span>
            </button>
        </div>

        <section class="radiologi-panel">
            <div class="radiologi-panel-head">
                <div>
                    <div class="radiologi-panel-title">Ringkasan Generate Radiologi</div>
                    <div class="radiologi-panel-subtitle" id="summaryRadiologiSubtitle">
                        Data dihitung dari hasil generate periode aktif.
                    </div>
                </div>
                <div class="radiologi-panel-actions">
                    <button type="button" class="radiologi-btn radiologi-btn-light" id="btnRefreshRadiologi">
                        <i class="mdi mdi-refresh"></i>
                        Refresh
                    </button>
                    <button type="button" class="radiologi-btn radiologi-btn-light" id="btnOpenConfigRadiologi">
                        <i class="mdi mdi-cog-outline"></i>
                        Konfigurasi
                    </button>
                    <button type="button" class="radiologi-btn radiologi-btn-primary" id="btnOpenGenerateRadiologi">
                        <i class="mdi mdi-calculator-variant-outline"></i>
                        Preview dan Generate
                    </button>
                </div>
            </div>
            <div class="radiologi-summary-grid">
                <div class="radiologi-summary-card">
                    <div class="radiologi-summary-label">Tindakan</div>
                    <div class="radiologi-summary-value" id="summaryTindakanRadiologi">0</div>
                </div>
                <div class="radiologi-summary-card">
                    <div class="radiologi-summary-label">Pasien</div>
                    <div class="radiologi-summary-value" id="summaryPasienRadiologi">0</div>
                </div>
                <div class="radiologi-summary-card total">
                    <div class="radiologi-summary-label">Premi Petugas</div>
                    <div class="radiologi-summary-value" id="summaryPetugasRadiologi">Rp 0</div>
                </div>
                <div class="radiologi-summary-card">
                    <div class="radiologi-summary-label">Premi Bersama</div>
                    <div class="radiologi-summary-value" id="summaryBersamaRadiologi">Rp 0</div>
                </div>
                <div class="radiologi-summary-card">
                    <div class="radiologi-summary-label">Terkunci</div>
                    <div class="radiologi-summary-value" id="summaryLockedRadiologi">0</div>
                </div>
            </div>
            <div class="radiologi-formula-strip" id="formulaRadiologiStrip">
                <div class="radiologi-formula-chip">
                    <div class="radiologi-formula-title">Basis Petugas</div>
                    <div class="radiologi-formula-text">Konfigurasi belum dimuat.</div>
                </div>
            </div>
        </section>

        <section class="radiologi-panel">
            <div class="radiologi-filter-bar">
                <div>
                    <div class="radiologi-panel-title" id="resultRadiologiTitle">Hasil Generate Radiologi Umum</div>
                    <div class="radiologi-panel-subtitle">Satu periode dan jenis hanya menyimpan satu hasil aktif.</div>
                </div>
                <div class="radiologi-search input-group">
                    <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                    <input type="text" class="form-control" id="searchGenerateRadiologi"
                        placeholder="Cari periode / jenis...">
                </div>
            </div>
            <div class="radiologi-table-wrap">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100" id="tableGenerateRadiologi">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Periode</th>
                                <th>Periode Data</th>
                                <th>Jenis</th>
                                <th class="text-center">Tindakan</th>
                                <th class="text-end">Basis Petugas</th>
                                <th class="text-end">Manajemen</th>
                                <th class="text-end">Premi Petugas</th>
                                <th class="text-end">Premi Bersama</th>
                                <th class="text-center">Penerima</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </section>
    </div>

    @include("simrs.backOffice.keuangan.hitungPremi.generateRadiologi.modal")
@endsection

@push("scripts")
    @include("simrs.backOffice.keuangan.hitungPremi.generateRadiologi.jsMain")
@endpush
