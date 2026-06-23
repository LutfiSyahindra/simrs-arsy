@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.select2")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")

    <style>
        .laboratorium-page {
            --rad-main: #0f766e;
            --rad-blue: #2563eb;
            --rad-deep: #0f172a;
            --rad-line: #e2e8f0;
            --rad-muted: #64748b;
            --rad-soft: #f0fdfa;
            --rad-shadow: 0 18px 45px rgba(15, 23, 42, .10);
            color: #172033;
        }

        .laboratorium-page .breadcrumb {
            background: transparent;
            font-size: 13px;
            margin-bottom: 0;
            padding: 0;
        }

        .laboratorium-hero {
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

        .laboratorium-hero::after {
            background: radial-gradient(circle, rgba(255, 255, 255, .20), transparent 58%);
            content: "";
            height: 210px;
            position: absolute;
            right: -70px;
            top: -80px;
            width: 210px;
        }

        .laboratorium-hero-main {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            min-width: 0;
            position: relative;
            z-index: 1;
        }

        .laboratorium-hero-icon {
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

        .laboratorium-title {
            font-size: 24px;
            font-weight: 800;
            margin: 3px 0 5px;
        }

        .laboratorium-eyebrow,
        .laboratorium-control-box label {
            color: #ccfbf1;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .laboratorium-description {
            color: rgba(255, 255, 255, .82);
            font-size: 13px;
            margin: 0;
            max-width: 760px;
        }

        .laboratorium-hero-controls {
            display: grid;
            flex: 0 0 320px;
            gap: 10px;
            position: relative;
            z-index: 1;
        }

        .laboratorium-control-box {
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .26);
            border-radius: 14px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .18);
            padding: 12px;
        }

        .laboratorium-control-box label {
            display: block;
            margin-bottom: 6px;
        }

        .laboratorium-period-picker {
            align-items: center;
            display: grid;
            gap: 7px;
            grid-template-columns: 38px minmax(0, 1fr) 38px;
        }

        .laboratorium-period-picker .form-control {
            border: 0;
            height: 39px;
            text-align: center;
        }

        .laboratorium-period-step,
        .laboratorium-period-current {
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

        .laboratorium-period-current {
            font-size: 11px;
            font-weight: 800;
            gap: 5px;
            margin-top: 8px;
            width: 100%;
        }

        .laboratorium-type-tabs {
            background: #fff;
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .045);
            display: flex;
            gap: 8px;
            margin: 16px 0;
            padding: 8px;
        }

        .laboratorium-type-tab {
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

        .laboratorium-type-tab i {
            font-size: 23px;
        }

        .laboratorium-type-tab strong,
        .laboratorium-type-tab small {
            display: block;
        }

        .laboratorium-type-tab small {
            font-size: 11px;
        }

        .laboratorium-type-tab.active {
            background: linear-gradient(135deg, #ecfdf5, #eff6ff);
            box-shadow: inset 0 0 0 1px #99f6e4, 0 8px 18px rgba(15, 118, 110, .08);
            color: var(--rad-main);
        }

        .laboratorium-panel {
            background: #fff;
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 16px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, .06);
            margin-bottom: 16px;
            overflow: hidden;
        }

        .laboratorium-panel-head,
        .laboratorium-filter-bar {
            align-items: center;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            padding: 15px 17px;
        }

        .laboratorium-panel-head {
            border-bottom: 1px solid #eef2f7;
        }

        .laboratorium-panel-title {
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
        }

        .laboratorium-panel-subtitle {
            color: var(--rad-muted);
            font-size: 11px;
        }

        .laboratorium-panel-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .laboratorium-btn {
            align-items: center;
            border-radius: 9px;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 6px;
            padding: 10px 12px;
        }

        .laboratorium-btn-light {
            background: #fff;
            border: 1px solid var(--rad-line);
            color: #475569;
        }

        .laboratorium-btn-primary {
            background: linear-gradient(135deg, #0f766e, #2563eb);
            border: 0;
            box-shadow: 0 10px 22px rgba(15, 118, 110, .20);
            color: #fff;
        }

        .laboratorium-summary-grid {
            display: grid;
            gap: 11px;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            padding: 15px 17px;
        }

        .laboratorium-summary-card,
        .laboratorium-mini-card {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 12px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .045);
            padding: 14px;
        }

        #modalGenerateLaboratorium .modal-content,
        #modalConfigLaboratorium .modal-content,
        #modalDetailLaboratorium .modal-content {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 38%, #f1f5f9 100%);
            border: 1px solid rgba(226, 232, 240, .85);
            border-radius: 16px;
            box-shadow: var(--rad-shadow);
            overflow: hidden;
            position: relative;
        }

        #modalGenerateLaboratorium .modal-content::before,
        #modalConfigLaboratorium .modal-content::before,
        #modalDetailLaboratorium .modal-content::before {
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

        #modalGenerateLaboratorium .modal-dialog,
        #modalConfigLaboratorium .modal-dialog,
        #modalDetailLaboratorium .modal-dialog {
            max-width: 980px;
        }

        #modalGenerateLaboratorium .modal-header,
        #modalConfigLaboratorium .modal-header,
        #modalDetailLaboratorium .modal-header {
            background: rgba(255, 255, 255, .92);
            border-bottom: 1px solid rgba(226, 232, 240, .9);
            padding: 17px 18px 14px;
            position: relative;
            z-index: 1;
        }

        #modalGenerateLaboratorium .modal-footer,
        #modalConfigLaboratorium .modal-footer,
        #modalDetailLaboratorium .modal-footer {
            background: rgba(255, 255, 255, .92);
            border-top: 1px solid rgba(226, 232, 240, .9);
            padding: 13px 18px;
            position: relative;
            z-index: 1;
        }

        #modalGenerateLaboratorium .modal-body,
        #modalConfigLaboratorium .modal-body,
        #modalDetailLaboratorium .modal-body {
            padding: 18px;
            position: relative;
            z-index: 1;
        }

        .laboratorium-summary-card.total {
            background: linear-gradient(135deg, #0f766e, #2563eb);
            border: 0;
            color: #fff;
        }

        .laboratorium-summary-label,
        .laboratorium-mini-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .laboratorium-summary-card.total .laboratorium-summary-label {
            color: #ccfbf1;
        }

        .laboratorium-summary-value,
        .laboratorium-mini-value {
            color: #0f172a;
            font-size: 18px;
            font-weight: 900;
            margin-top: 4px;
            overflow-wrap: anywhere;
        }

        .laboratorium-summary-card.total .laboratorium-summary-value {
            color: #fff;
        }

        .laboratorium-mini-card.total {
            background: linear-gradient(135deg, #0f766e, #2563eb);
            border: 0;
            box-shadow: 0 16px 32px rgba(15, 118, 110, .20);
            color: #fff;
        }

        .laboratorium-mini-card.total .laboratorium-mini-label,
        .laboratorium-mini-card.total .laboratorium-mini-value,
        .laboratorium-mini-card.total .laboratorium-mini-note {
            color: #fff;
        }

        .laboratorium-mini-card {
            min-width: 0;
        }

        .laboratorium-mini-top {
            align-items: center;
            display: flex;
            gap: 8px;
            justify-content: space-between;
        }

        .laboratorium-mini-icon {
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

        .laboratorium-mini-card.total .laboratorium-mini-icon {
            background: rgba(255, 255, 255, .16);
            border-color: rgba(255, 255, 255, .24);
            color: #fff;
        }

        .laboratorium-mini-note {
            color: #64748b;
            font-size: 10px;
            line-height: 1.35;
            margin-top: 5px;
        }

        .laboratorium-formula-strip {
            border-top: 1px solid #eef2f7;
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            padding: 15px 17px;
        }

        .laboratorium-formula-chip {
            background: #f8fafc;
            border: 1px solid var(--rad-line);
            border-radius: 10px;
            padding: 11px;
        }

        .laboratorium-formula-chip.primary {
            background: #ecfdf5;
            border-color: #99f6e4;
        }

        .laboratorium-formula-top {
            align-items: center;
            display: flex;
            gap: 8px;
            margin-bottom: 6px;
        }

        .laboratorium-formula-icon {
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

        .laboratorium-formula-title {
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
        }

        .laboratorium-formula-text {
            color: #64748b;
            font-size: 11px;
            margin-top: 3px;
        }

        .laboratorium-source-note {
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

        .laboratorium-source-note i {
            font-size: 20px;
            line-height: 1;
            margin-top: 1px;
        }

        .laboratorium-source-note strong {
            display: block;
            font-size: 12px;
            margin-bottom: 2px;
        }

        .laboratorium-source-note span {
            display: block;
            font-size: 11px;
            line-height: 1.45;
        }

        .laboratorium-simple-head,
        .laboratorium-simple-section {
            background: #fff;
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 14px;
            padding: 15px;
        }

        .laboratorium-simple-head {
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

        .laboratorium-simple-head::after {
            background: radial-gradient(circle, rgba(255, 255, 255, .22), transparent 58%);
            content: "";
            height: 120px;
            position: absolute;
            right: -30px;
            top: -45px;
            width: 120px;
        }

        .laboratorium-simple-main {
            align-items: center;
            display: flex;
            gap: 12px;
            min-width: 0;
            position: relative;
            z-index: 1;
        }

        .laboratorium-simple-icon {
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

        .laboratorium-simple-title {
            color: inherit;
            font-size: 15px;
            font-weight: 900;
        }

        .laboratorium-simple-text,
        .laboratorium-simple-section-subtitle {
            color: #64748b;
            font-size: 12px;
            line-height: 1.45;
            margin-top: 3px;
        }

        .laboratorium-simple-head .laboratorium-simple-text {
            color: rgba(255, 255, 255, .78);
        }

        .laboratorium-simple-badge {
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

        .laboratorium-simple-section-title {
            align-items: center;
            color: #0f172a;
            display: flex;
            font-size: 13px;
            font-weight: 900;
            gap: 7px;
            margin-bottom: 10px;
        }

        .laboratorium-simple-section-title::before {
            background: linear-gradient(135deg, #0f766e, #2563eb);
            border-radius: 999px;
            content: "";
            height: 8px;
            width: 8px;
        }

        .laboratorium-info-list {
            display: grid;
            gap: 8px;
        }

        .laboratorium-info-row {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #eef2f7;
            border-radius: 10px;
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(0, 1fr) auto;
            padding: 10px 11px;
        }

        .laboratorium-info-row:last-child {
            border-bottom: 1px solid #eef2f7;
        }

        .laboratorium-info-label {
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
        }

        .laboratorium-info-note {
            color: #64748b;
            font-size: 11px;
            margin-top: 2px;
        }

        .laboratorium-info-value {
            color: #0f766e;
            font-size: 12px;
            font-weight: 900;
            overflow-wrap: anywhere;
            text-align: right;
        }

        .laboratorium-workflow {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-bottom: 14px;
        }

        .laboratorium-workflow-item,
        .laboratorium-config-info {
            background: #fff;
            border: 1px solid var(--rad-line);
            border-radius: 12px;
            padding: 12px;
        }

        .laboratorium-workflow-item {
            display: grid;
            gap: 8px;
            grid-template-columns: auto minmax(0, 1fr);
        }

        .laboratorium-workflow-number {
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

        .laboratorium-workflow-title,
        .laboratorium-config-info-title {
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
        }

        .laboratorium-workflow-text,
        .laboratorium-config-info-text {
            color: #64748b;
            font-size: 11px;
            line-height: 1.45;
            margin-top: 3px;
        }

        .laboratorium-workflow-text {
            grid-column: 2;
            margin-top: 0;
        }

        .laboratorium-config-overview {
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            margin-bottom: 14px;
        }

        .laboratorium-config-health {
            display: grid;
            gap: 7px;
            margin-top: 10px;
        }

        .laboratorium-config-health-row {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            display: flex;
            gap: 8px;
            padding: 8px;
        }

        .laboratorium-config-health-row.ok {
            background: #ecfdf5;
            border-color: #bbf7d0;
            color: #166534;
        }

        .laboratorium-config-health-row.warn {
            background: #fffbeb;
            border-color: #fde68a;
            color: #92400e;
        }

        .laboratorium-config-health-row i {
            font-size: 17px;
        }

        .laboratorium-config-health-row span {
            font-size: 11px;
            font-weight: 800;
        }

        .laboratorium-config-section {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 14px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .055);
            padding: 15px;
        }

        .laboratorium-config-section h6 {
            align-items: center;
            color: #0f172a;
            display: flex;
            gap: 6px;
            font-size: 13px;
            font-weight: 900;
            margin-bottom: 12px;
        }

        .laboratorium-config-section h6 i {
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

        .laboratorium-config-section .form-control,
        .laboratorium-config-section .form-select,
        .laboratorium-config-section .select2-container--default .select2-selection--multiple {
            border-color: #dbe4ee;
            border-radius: 10px;
            min-height: 40px;
        }

        .laboratorium-config-section .input-group-text {
            background: #f8fafc;
            border-color: #dbe4ee;
            color: #475569;
            font-weight: 800;
        }

        .laboratorium-config-section .form-label {
            color: #334155;
            font-size: 11px;
            font-weight: 900;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .laboratorium-field-help {
            color: #64748b;
            display: block;
            font-size: 10px;
            line-height: 1.4;
            margin-top: 5px;
        }

        .laboratorium-quality {
            border-radius: 10px;
            font-size: 12px;
            line-height: 1.5;
            margin-top: 12px;
            padding: 11px 12px;
        }

        .laboratorium-quality.ok {
            background: linear-gradient(135deg, #ecfdf5, #eff6ff);
            border: 1px solid #99f6e4;
            color: #065f46;
        }

        .laboratorium-quality.warn {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
        }

        .laboratorium-live-formula {
            background: linear-gradient(135deg, #f0fdfa, #eff6ff);
            border: 1px solid #99f6e4;
            border-radius: 12px;
            margin-bottom: 14px;
            overflow: hidden;
        }

        .laboratorium-live-formula-head {
            align-items: center;
            border-bottom: 1px solid #bfdbfe;
            display: flex;
            gap: 9px;
            justify-content: space-between;
            padding: 11px 12px;
        }

        .laboratorium-live-formula-title {
            color: #0f172a;
            font-size: 13px;
            font-weight: 900;
        }

        .laboratorium-live-formula-subtitle {
            color: #475569;
            font-size: 11px;
        }

        .laboratorium-live-formula-body {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            padding: 12px;
        }

        .laboratorium-live-formula-row {
            background: #fff;
            border: 1px solid #dbeafe;
            border-radius: 10px;
            padding: 10px;
        }

        .laboratorium-live-formula-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .laboratorium-live-formula-value {
            color: #0f172a;
            font-size: 13px;
            font-weight: 900;
            margin-top: 3px;
        }

        .laboratorium-ledger {
            background: #fff;
            border: 1px solid var(--rad-line);
            border-radius: 12px;
            overflow: hidden;
        }

        .laboratorium-ledger-row {
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            display: grid;
            gap: 12px;
            grid-template-columns: 34px minmax(0, 1fr) auto;
            padding: 10px 12px;
        }

        .laboratorium-ledger-row:last-child {
            border-bottom: 0;
        }

        .laboratorium-ledger-row.highlight {
            background: #ecfdf5;
        }

        .laboratorium-ledger-row.highlight .laboratorium-ledger-icon {
            background: #0f766e;
            color: #fff;
        }

        .laboratorium-ledger-icon {
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

        .laboratorium-ledger-label {
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
        }

        .laboratorium-ledger-note {
            color: #64748b;
            font-size: 10px;
            margin-top: 2px;
        }

        .laboratorium-ledger-value {
            color: #0f766e;
            font-size: 13px;
            font-weight: 900;
            text-align: right;
            white-space: nowrap;
        }

        .laboratorium-filter-bar {
            border-bottom: 1px solid #eef2f7;
            padding: 13px 17px;
        }

        .laboratorium-search {
            max-width: 340px;
            width: 100%;
        }

        .laboratorium-table-wrap {
            padding: 8px 17px 17px;
        }

        .dataTables_wrapper .dataTables_filter {
            display: none;
        }

        .laboratorium-badge,
        .laboratorium-lock {
            border-radius: 999px;
            display: inline-flex;
            font-size: 10px;
            font-weight: 800;
            gap: 4px;
            padding: 4px 8px;
        }

        .laboratorium-badge.umum {
            background: #dcfce7;
            color: #166534;
        }

        .laboratorium-badge.bpjs {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .laboratorium-lock.locked {
            background: #fef3c7;
            color: #92400e;
        }

        .laboratorium-lock.open {
            background: #f1f5f9;
            color: #64748b;
        }

        .laboratorium-actions {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .laboratorium-actions .btn {
            align-items: center;
            display: inline-flex;
            height: 31px;
            justify-content: center;
            padding: 0;
            width: 31px;
        }

        .laboratorium-modal-hero,
        .laboratorium-detail-hero {
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

        .laboratorium-modal-main {
            align-items: center;
            display: flex;
            gap: 12px;
            min-width: 0;
        }

        .laboratorium-modal-badges {
            align-items: flex-end;
            display: flex;
            flex: 0 0 auto;
            flex-direction: column;
            gap: 6px;
        }

        .laboratorium-modal-badge {
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

        .laboratorium-modal-icon {
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

        .laboratorium-modal-title {
            color: #0f172a;
            font-size: 14px;
            font-weight: 900;
        }

        .laboratorium-modal-text {
            color: #475569;
            font-size: 12px;
            margin-top: 2px;
        }

        .laboratorium-preview-grid,
        .laboratorium-config-grid,
        .laboratorium-detail-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .laboratorium-config-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .laboratorium-recipient-list {
            display: grid;
            gap: 8px;
        }

        .laboratorium-recipient-item {
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

        .laboratorium-recipient-avatar {
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

        .laboratorium-recipient-name {
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
        }

        .laboratorium-recipient-meta {
            color: #64748b;
            font-size: 11px;
            margin-top: 2px;
        }

        .laboratorium-recipient-total {
            color: var(--rad-main);
            font-size: 13px;
            font-weight: 900;
            text-align: right;
        }

        .laboratorium-recipient-status {
            background: #ecfdf5;
            border: 1px solid #99f6e4;
            border-radius: 999px;
            color: #0f766e;
            display: inline-flex;
            font-size: 10px;
            font-weight: 900;
            padding: 4px 8px;
        }

        .laboratorium-empty-state {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            color: #64748b;
            font-size: 12px;
            padding: 12px;
        }

        @media (max-width: 991.98px) {
            .laboratorium-hero,
            .laboratorium-panel-head,
            .laboratorium-filter-bar {
                align-items: stretch;
                flex-direction: column;
            }

            .laboratorium-hero-controls,
            .laboratorium-panel-actions,
            .laboratorium-search {
                flex-basis: auto;
                max-width: none;
                width: 100%;
            }

            .laboratorium-summary-grid,
            .laboratorium-formula-strip,
            .laboratorium-workflow,
            .laboratorium-config-overview,
            .laboratorium-preview-grid,
            .laboratorium-config-grid,
            .laboratorium-detail-grid {
                grid-template-columns: 1fr;
            }

            .laboratorium-modal-hero,
            .laboratorium-detail-hero,
            .laboratorium-simple-head {
                align-items: flex-start;
                flex-direction: column;
            }

            .laboratorium-modal-badges {
                align-items: flex-start;
            }

            .laboratorium-live-formula-body,
            .laboratorium-ledger-row,
            .laboratorium-recipient-item,
            .laboratorium-info-row {
                grid-template-columns: 1fr;
            }

            .laboratorium-ledger-value,
            .laboratorium-recipient-total,
            .laboratorium-info-value {
                text-align: left;
            }
        }
    </style>
@endpush

@section("content")
    <div class="laboratorium-page">
        <nav class="page-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route("backOffice.keuangan.hitungPremi") }}">Generate Premi</a>
                </li>
                <li class="breadcrumb-item active">Generate Laboratorium</li>
            </ol>
        </nav>

        <section class="laboratorium-hero">
            <div class="laboratorium-hero-main">
                <div class="laboratorium-hero-icon">
                    <i class="mdi mdi-radioactive-circle-outline"></i>
                </div>
                <div>
                    <div class="laboratorium-eyebrow">Otomatis dari Khanza</div>
                    <h4 class="laboratorium-title">Generate Premi Laboratorium</h4>
                    <p class="laboratorium-description">
                        Sistem membaca detail_periksa_lab per periode, memisahkan UMUM dan BPJS berdasarkan
                        penjamin, lalu menghitung premi petugas dan premi bersama dari konfigurasi aktif. Khusus BPJS,
                        premi bersama berasal dari bagian_rs dan premi petugas dari premi bersama yang dibagi.
                    </p>
                </div>
            </div>
            <div class="laboratorium-hero-controls">
                <div class="laboratorium-control-box">
                    <label for="periodeLaboratorium">Periode Perhitungan</label>
                    <div class="laboratorium-period-picker">
                        <button type="button" class="laboratorium-period-step" id="btnPrevPeriodLaboratorium">
                            <i class="mdi mdi-chevron-left"></i>
                        </button>
                        <input type="month" id="periodeLaboratorium" class="form-control">
                        <button type="button" class="laboratorium-period-step" id="btnNextPeriodLaboratorium">
                            <i class="mdi mdi-chevron-right"></i>
                        </button>
                    </div>
                    <button type="button" class="laboratorium-period-current" id="btnCurrentPeriodLaboratorium">
                        <i class="mdi mdi-calendar-today-outline"></i>
                        Bulan Ini
                    </button>
                </div>
            </div>
        </section>

        <div class="laboratorium-type-tabs">
            <button type="button" class="laboratorium-type-tab active" data-type="umum">
                <i class="mdi mdi-account-cash-outline"></i>
                <span>
                    <strong>UMUM</strong>
                    <small>kd_pj selain BPJ dan -</small>
                </span>
            </button>
            <button type="button" class="laboratorium-type-tab" data-type="bpjs">
                <i class="mdi mdi-shield-account-outline"></i>
                <span>
                    <strong>BPJS Kesehatan</strong>
                    <small>kd_pj BPJ, data bulan kemarin</small>
                </span>
            </button>
        </div>

        <section class="laboratorium-panel">
            <div class="laboratorium-panel-head">
                <div>
                    <div class="laboratorium-panel-title">Ringkasan Generate Laboratorium</div>
                    <div class="laboratorium-panel-subtitle" id="summaryLaboratoriumSubtitle">
                        Data dihitung dari hasil generate periode aktif.
                    </div>
                </div>
                <div class="laboratorium-panel-actions">
                    <button type="button" class="laboratorium-btn laboratorium-btn-light" id="btnRefreshLaboratorium">
                        <i class="mdi mdi-refresh"></i>
                        Refresh
                    </button>
                    <button type="button" class="laboratorium-btn laboratorium-btn-light" id="btnOpenConfigLaboratorium">
                        <i class="mdi mdi-cog-outline"></i>
                        Konfigurasi
                    </button>
                    <button type="button" class="laboratorium-btn laboratorium-btn-primary" id="btnOpenGenerateLaboratorium">
                        <i class="mdi mdi-calculator-variant-outline"></i>
                        Preview dan Generate
                    </button>
                </div>
            </div>
            <div class="laboratorium-summary-grid">
                <div class="laboratorium-summary-card">
                    <div class="laboratorium-summary-label">Tindakan</div>
                    <div class="laboratorium-summary-value" id="summaryTindakanLaboratorium">0</div>
                </div>
                <div class="laboratorium-summary-card">
                    <div class="laboratorium-summary-label">Pasien</div>
                    <div class="laboratorium-summary-value" id="summaryPasienLaboratorium">0</div>
                </div>
                <div class="laboratorium-summary-card total">
                    <div class="laboratorium-summary-label">Premi Petugas</div>
                    <div class="laboratorium-summary-value" id="summaryPetugasLaboratorium">Rp 0</div>
                </div>
                <div class="laboratorium-summary-card">
                    <div class="laboratorium-summary-label">Premi Bersama</div>
                    <div class="laboratorium-summary-value" id="summaryBersamaLaboratorium">Rp 0</div>
                </div>
                <div class="laboratorium-summary-card">
                    <div class="laboratorium-summary-label">Terkunci</div>
                    <div class="laboratorium-summary-value" id="summaryLockedLaboratorium">0</div>
                </div>
            </div>
            <div class="laboratorium-formula-strip" id="formulaLaboratoriumStrip">
                <div class="laboratorium-formula-chip">
                    <div class="laboratorium-formula-title">Basis Petugas</div>
                    <div class="laboratorium-formula-text">Konfigurasi belum dimuat.</div>
                </div>
            </div>
        </section>

        <section class="laboratorium-panel">
            <div class="laboratorium-filter-bar">
                <div>
                    <div class="laboratorium-panel-title" id="resultLaboratoriumTitle">Hasil Generate Laboratorium Umum</div>
                    <div class="laboratorium-panel-subtitle">Satu periode dan jenis hanya menyimpan satu hasil aktif.</div>
                </div>
                <div class="laboratorium-search input-group">
                    <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                    <input type="text" class="form-control" id="searchGenerateLaboratorium"
                        placeholder="Cari periode / jenis...">
                </div>
            </div>
            <div class="laboratorium-table-wrap">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100" id="tableGenerateLaboratorium">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Periode</th>
                                <th>Periode Data</th>
                                <th>Jenis</th>
                                <th class="text-center">Tindakan</th>
                                <th class="text-end">Basis Petugas</th>
                                <th class="text-end">Basis Bersama</th>
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

    @include("simrs.backOffice.keuangan.hitungPremi.generateLaboratorium.modal")
@endsection

@push("scripts")
    @include("simrs.backOffice.keuangan.hitungPremi.generateLaboratorium.jsMain")
@endpush
