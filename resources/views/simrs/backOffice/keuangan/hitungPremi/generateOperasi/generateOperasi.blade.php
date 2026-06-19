@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.select2")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")

    <style>
        .operasi-page {
            --operasi-blue: #0891b2;
            --operasi-teal: #0f766e;
            --operasi-muted: #64748b;
            --operasi-line: #e2e8f0;
            color: #172033;
        }

        .operasi-page .breadcrumb {
            background: transparent;
            font-size: 13px;
            margin-bottom: 0;
            padding: 0;
        }

        .operasi-hero {
            align-items: stretch;
            background: linear-gradient(135deg, #0891b2 0%, #0f766e 58%, #2563eb 100%);
            border-radius: 16px;
            box-shadow: 0 16px 34px rgba(8, 145, 178, .18);
            color: #fff;
            display: flex;
            gap: 22px;
            justify-content: space-between;
            margin-top: 15px;
            overflow: hidden;
            padding: 22px;
        }

        .operasi-hero-main {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            min-width: 0;
        }

        .operasi-hero-icon {
            align-items: center;
            background: #fff;
            border-radius: 13px;
            color: var(--operasi-blue);
            display: flex;
            flex: 0 0 auto;
            font-size: 30px;
            height: 58px;
            justify-content: center;
            width: 58px;
        }

        .operasi-eyebrow {
            color: #cffafe;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .operasi-title {
            font-size: 24px;
            font-weight: 800;
            margin: 3px 0 5px;
        }

        .operasi-description {
            color: rgba(255, 255, 255, .82);
            font-size: 13px;
            margin: 0;
            max-width: 720px;
        }

        .operasi-hero-facts {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 13px;
        }

        .operasi-hero-fact {
            align-items: center;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 999px;
            color: #fff;
            display: inline-flex;
            font-size: 11px;
            font-weight: 800;
            gap: 6px;
            padding: 7px 10px;
        }

        .operasi-hero-controls {
            display: grid;
            flex: 0 0 320px;
            gap: 10px;
        }

        .operasi-control-box {
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 12px;
            padding: 12px;
        }

        .operasi-control-box label {
            color: #cffafe;
            display: block;
            font-size: 10px;
            font-weight: 800;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .operasi-control-box .form-control {
            border: 0;
            height: 39px;
            text-align: center;
        }

        .operasi-period-picker {
            align-items: center;
            display: grid;
            gap: 7px;
            grid-template-columns: 38px minmax(0, 1fr) 38px;
        }

        .operasi-period-step,
        .operasi-period-current {
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

        .operasi-period-current {
            font-size: 11px;
            font-weight: 800;
            gap: 5px;
            margin-top: 8px;
            width: 100%;
        }

        .operasi-type-tabs {
            background: #fff;
            border: 1px solid var(--operasi-line);
            border-radius: 14px;
            display: flex;
            gap: 8px;
            margin: 16px 0;
            padding: 8px;
        }

        .operasi-type-tab {
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

        .operasi-type-tab i {
            font-size: 23px;
        }

        .operasi-type-tab strong,
        .operasi-type-tab small {
            display: block;
        }

        .operasi-type-tab small {
            font-size: 11px;
        }

        .operasi-type-tab.active {
            background: #ecfeff;
            box-shadow: inset 0 0 0 1px #a5f3fc;
            color: var(--operasi-blue);
        }

        .operasi-workflow-band {
            display: grid;
            gap: 11px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-bottom: 16px;
        }

        .operasi-workflow-card {
            align-items: flex-start;
            background: #fff;
            border: 1px solid var(--operasi-line);
            border-radius: 12px;
            display: flex;
            gap: 11px;
            padding: 13px;
        }

        .operasi-workflow-icon {
            align-items: center;
            background: #ecfeff;
            border-radius: 10px;
            color: #0891b2;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 22px;
            height: 42px;
            justify-content: center;
            width: 42px;
        }

        .operasi-workflow-title {
            color: #0f172a;
            font-size: 13px;
            font-weight: 900;
        }

        .operasi-workflow-text {
            color: #64748b;
            font-size: 11px;
            margin-top: 3px;
        }

        .operasi-command-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: minmax(0, 1.15fr) minmax(280px, .85fr);
            margin-bottom: 16px;
        }

        .operasi-command-card {
            background: #fff;
            border: 1px solid var(--operasi-line);
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .04);
            overflow: hidden;
        }

        .operasi-command-main {
            align-items: center;
            background: linear-gradient(135deg, #ecfeff, #f0fdfa);
            display: grid;
            gap: 14px;
            grid-template-columns: minmax(0, 1fr) auto;
            padding: 16px;
        }

        .operasi-command-eyebrow {
            color: #0891b2;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .operasi-command-title {
            color: #0f172a;
            font-size: 18px;
            font-weight: 900;
            margin-top: 3px;
        }

        .operasi-command-text {
            color: #475569;
            font-size: 12px;
            margin-top: 4px;
        }

        .operasi-command-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .operasi-context-strip {
            border-top: 1px solid #e2e8f0;
            display: grid;
            gap: 0;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .operasi-context-item {
            border-right: 1px solid #e2e8f0;
            padding: 12px 14px;
        }

        .operasi-context-item:last-child {
            border-right: 0;
        }

        .operasi-context-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .operasi-context-value {
            color: #0f172a;
            font-size: 14px;
            font-weight: 900;
            margin-top: 3px;
        }

        .operasi-formula-card {
            padding: 14px;
        }

        .operasi-formula-title {
            color: #0f172a;
            font-size: 14px;
            font-weight: 900;
        }

        .operasi-formula-subtitle {
            color: #64748b;
            font-size: 11px;
            margin-top: 2px;
        }

        .operasi-formula-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .operasi-formula-chip {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            padding: 7px 10px;
        }

        .operasi-formula-chip strong {
            color: #0891b2;
        }

        .operasi-panel {
            background: #fff;
            border: 1px solid var(--operasi-line);
            border-radius: 15px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
            margin-bottom: 16px;
            overflow: hidden;
        }

        .operasi-panel-head,
        .operasi-filter-bar {
            align-items: center;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            padding: 15px 17px;
        }

        .operasi-panel-head {
            border-bottom: 1px solid #eef2f7;
        }

        .operasi-panel-title {
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
        }

        .operasi-panel-subtitle {
            color: var(--operasi-muted);
            font-size: 11px;
        }

        .operasi-panel-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .operasi-btn {
            align-items: center;
            border-radius: 9px;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 6px;
            padding: 10px 12px;
        }

        .operasi-btn-light {
            background: #fff;
            border: 1px solid var(--operasi-line);
            color: #475569;
        }

        .operasi-btn-primary {
            background: var(--operasi-blue);
            border: 0;
            color: #fff;
        }

        .operasi-summary-grid {
            display: grid;
            gap: 11px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            padding: 15px 17px;
        }

        .operasi-summary-card {
            background: linear-gradient(180deg, #fff, #f8fafc);
            border: 1px solid var(--operasi-line);
            border-radius: 11px;
            padding: 13px;
        }

        .operasi-summary-card.total {
            background: linear-gradient(135deg, #0891b2, #0f766e);
            border: 0;
            color: #fff;
        }

        .operasi-summary-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .operasi-summary-card.total .operasi-summary-label {
            color: #cffafe;
        }

        .operasi-summary-value {
            color: #0f172a;
            font-size: 19px;
            font-weight: 900;
            margin-top: 4px;
        }

        .operasi-summary-card.total .operasi-summary-value {
            color: #fff;
        }

        .operasi-filter-bar {
            border-bottom: 1px solid #eef2f7;
            padding: 13px 17px;
        }

        .operasi-search {
            max-width: 340px;
            width: 100%;
        }

        .operasi-table-wrap {
            padding: 8px 17px 17px;
        }

        .dataTables_wrapper .dataTables_filter {
            display: none;
        }

        .operasi-badge,
        .operasi-lock {
            border-radius: 999px;
            display: inline-flex;
            font-size: 10px;
            font-weight: 800;
            gap: 4px;
            padding: 4px 8px;
        }

        .operasi-badge.umum {
            background: #dcfce7;
            color: #166534;
        }

        .operasi-badge.bpjs {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .operasi-lock.locked {
            background: #fef3c7;
            color: #92400e;
        }

        .operasi-lock.open {
            background: #f1f5f9;
            color: #64748b;
        }

        .operasi-actions {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .operasi-actions .btn {
            align-items: center;
            display: inline-flex;
            height: 31px;
            justify-content: center;
            padding: 0;
            width: 31px;
        }

        .operasi-preview-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }

        .operasi-modal-hero {
            align-items: center;
            background: linear-gradient(135deg, #ecfeff, #f0fdfa);
            border: 1px solid #a5f3fc;
            border-radius: 12px;
            display: flex;
            gap: 12px;
            margin-bottom: 14px;
            padding: 13px;
        }

        .operasi-modal-hero-icon {
            align-items: center;
            background: #0891b2;
            border-radius: 10px;
            color: #fff;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 24px;
            height: 44px;
            justify-content: center;
            width: 44px;
        }

        .operasi-modal-hero-title {
            color: #0f172a;
            font-size: 14px;
            font-weight: 900;
        }

        .operasi-modal-hero-text {
            color: #475569;
            font-size: 12px;
            margin-top: 2px;
        }

        .operasi-modal-hero-side {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-left: auto;
        }

        .operasi-generate-layout {
            display: grid;
            gap: 14px;
            grid-template-columns: minmax(260px, .85fr) minmax(0, 1.4fr);
        }

        .operasi-modal-card {
            background: #fff;
            border: 1px solid var(--operasi-line);
            border-radius: 12px;
            overflow: hidden;
        }

        .operasi-modal-card-head {
            align-items: center;
            background: #f8fafc;
            border-bottom: 1px solid #eef2f7;
            display: flex;
            gap: 9px;
            justify-content: space-between;
            padding: 11px 12px;
        }

        .operasi-modal-card-title {
            color: #0f172a;
            font-size: 13px;
            font-weight: 900;
        }

        .operasi-modal-card-subtitle {
            color: #64748b;
            font-size: 11px;
        }

        .operasi-modal-card-body {
            padding: 12px;
        }

        .operasi-mini-stat-grid {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-bottom: 10px;
        }

        .operasi-mini-stat {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 9px;
        }

        .operasi-mini-stat-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .operasi-mini-stat-value {
            color: #0f172a;
            font-size: 16px;
            font-weight: 900;
            margin-top: 3px;
        }

        .operasi-checklist {
            display: grid;
            gap: 7px;
            margin-top: 12px;
        }

        .operasi-checklist-item {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            color: #475569;
            display: flex;
            font-size: 11px;
            font-weight: 800;
            gap: 8px;
            padding: 8px;
        }

        .operasi-checklist-item i {
            color: #0f766e;
            font-size: 16px;
        }

        .operasi-preview-status {
            border-radius: 12px;
            display: grid;
            gap: 8px;
            margin-top: 12px;
            padding: 12px;
        }

        .operasi-preview-status.success {
            background: #ecfdf5;
            border: 1px solid #99f6e4;
            color: #065f46;
        }

        .operasi-preview-status.warning {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
        }

        .operasi-preview-status-title {
            align-items: center;
            display: flex;
            font-size: 13px;
            font-weight: 900;
            gap: 7px;
        }

        .operasi-preview-status-text {
            font-size: 11px;
            line-height: 1.45;
        }

        .operasi-preview-insight {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-bottom: 12px;
        }

        .operasi-preview-insight-card {
            background: #fff;
            border: 1px solid var(--operasi-line);
            border-radius: 12px;
            padding: 12px;
        }

        .operasi-preview-insight-card.primary {
            background: linear-gradient(135deg, #0891b2, #0f766e);
            border: 0;
            color: #fff;
        }

        .operasi-preview-insight-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .operasi-preview-insight-card.primary .operasi-preview-insight-label {
            color: #cffafe;
        }

        .operasi-preview-insight-value {
            color: #0f172a;
            font-size: 17px;
            font-weight: 900;
            margin-top: 4px;
        }

        .operasi-preview-insight-card.primary .operasi-preview-insight-value {
            color: #fff;
        }

        .operasi-flow {
            display: grid;
            gap: 8px;
        }

        .operasi-flow-row {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            display: grid;
            gap: 8px;
            grid-template-columns: 34px minmax(0, 1fr) auto;
            padding: 9px;
        }

        .operasi-flow-icon {
            align-items: center;
            background: #e0f2fe;
            border-radius: 9px;
            color: #0369a1;
            display: inline-flex;
            font-size: 18px;
            height: 34px;
            justify-content: center;
            width: 34px;
        }

        .operasi-flow-title {
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
        }

        .operasi-flow-subtitle {
            color: #64748b;
            font-size: 11px;
        }

        .operasi-flow-value {
            color: #0891b2;
            font-size: 13px;
            font-weight: 900;
            white-space: nowrap;
        }

        .operasi-preview-item,
        .operasi-recipient-item {
            background: #f8fafc;
            border: 1px solid var(--operasi-line);
            border-radius: 9px;
            padding: 10px;
        }

        .operasi-preview-item.highlight {
            background: linear-gradient(135deg, #0891b2, #0f766e);
            border: 0;
            color: #fff;
        }

        .operasi-preview-label,
        .operasi-recipient-role {
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .operasi-preview-value {
            color: #0f172a;
            font-size: 16px;
            font-weight: 900;
            margin-top: 4px;
        }

        .operasi-preview-item.highlight .operasi-preview-label,
        .operasi-preview-item.highlight .operasi-preview-value {
            color: #fff;
        }

        .operasi-recipient-group {
            background: #fff;
            border: 1px solid var(--operasi-line);
            border-radius: 12px;
            overflow: hidden;
        }

        .operasi-recipient-group-head {
            align-items: center;
            background: #f8fafc;
            border-bottom: 1px solid #eef2f7;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            padding: 10px 12px;
        }

        .operasi-recipient-list {
            display: grid;
            gap: 8px;
            padding: 10px;
        }

        .operasi-recipient-name {
            color: #0f172a;
            font-size: 12px;
            font-weight: 800;
            margin-top: 4px;
        }

        .operasi-recipient-total {
            color: var(--operasi-teal);
            font-size: 15px;
            font-weight: 900;
            margin-top: 4px;
        }

        .operasi-config-badge {
            background: #ecfeff;
            border: 1px solid #a5f3fc;
            border-radius: 999px;
            color: #0e7490;
            display: inline-flex;
            font-size: 11px;
            font-weight: 900;
            gap: 5px;
            padding: 5px 9px;
        }

        .operasi-config-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .operasi-config-overview {
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            margin-bottom: 14px;
        }

        .operasi-config-overview-card {
            background: #fff;
            border: 1px solid var(--operasi-line);
            border-radius: 12px;
            padding: 12px;
        }

        .operasi-config-overview-title {
            color: #0f172a;
            font-size: 13px;
            font-weight: 900;
        }

        .operasi-config-overview-text {
            color: #64748b;
            font-size: 11px;
            margin-top: 3px;
        }

        .operasi-config-health {
            display: grid;
            gap: 7px;
            margin-top: 10px;
        }

        .operasi-config-health-row {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            display: flex;
            font-size: 11px;
            font-weight: 800;
            gap: 8px;
            padding: 8px;
        }

        .operasi-config-health-row.ok i {
            color: #0f766e;
        }

        .operasi-config-health-row.warn i {
            color: #d97706;
        }

        .operasi-config-section {
            border: 1px solid var(--operasi-line);
            border-radius: 10px;
            padding: 12px;
        }

        .operasi-config-section h6 {
            color: #0f172a;
            font-size: 13px;
            font-weight: 900;
            margin-bottom: 10px;
        }

        .operasi-config-flow {
            display: grid;
            gap: 9px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-bottom: 14px;
        }

        .operasi-config-step {
            background: #f8fafc;
            border: 1px solid var(--operasi-line);
            border-radius: 10px;
            padding: 10px;
        }

        .operasi-config-step-number {
            align-items: center;
            background: #0891b2;
            border-radius: 999px;
            color: #fff;
            display: inline-flex;
            font-size: 11px;
            font-weight: 900;
            height: 23px;
            justify-content: center;
            margin-bottom: 8px;
            width: 23px;
        }

        .operasi-config-step-title {
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
        }

        .operasi-config-step-text {
            color: #64748b;
            font-size: 11px;
            margin-top: 3px;
        }

        .operasi-config-field {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            display: grid;
            gap: 8px;
            grid-template-columns: minmax(0, 1fr) 100px;
            margin-bottom: 8px;
            padding: 8px;
        }

        .operasi-config-field .form-label {
            color: #0f172a;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 1px;
        }

        .operasi-config-field small {
            color: #64748b;
            display: block;
            font-size: 10px;
        }

        .operasi-config-field .form-control {
            font-weight: 900;
            text-align: right;
        }

        .operasi-recipient-counter {
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            margin-top: 6px;
        }

        .operasi-detail-dashboard {
            display: grid;
            gap: 14px;
        }

        .operasi-detail-hero {
            align-items: center;
            background: linear-gradient(135deg, #0891b2, #0f766e);
            border-radius: 14px;
            color: #fff;
            display: grid;
            gap: 14px;
            grid-template-columns: minmax(0, 1fr) auto;
            padding: 18px;
        }

        .operasi-detail-hero-main {
            align-items: center;
            display: flex;
            gap: 12px;
            min-width: 0;
        }

        .operasi-detail-hero-icon {
            align-items: center;
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 12px;
            display: inline-flex;
            flex: 0 0 48px;
            font-size: 25px;
            height: 48px;
            justify-content: center;
            width: 48px;
        }

        .operasi-detail-hero-side {
            align-items: flex-end;
            display: grid;
            gap: 7px;
            justify-items: end;
        }

        .operasi-detail-eyebrow {
            color: #cffafe;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .operasi-detail-title {
            font-size: 22px;
            font-weight: 900;
            line-height: 1.2;
            margin-top: 2px;
        }

        .operasi-detail-meta {
            color: rgba(255, 255, 255, .84);
            font-size: 12px;
            margin-top: 4px;
        }

        .operasi-detail-status {
            align-items: center;
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .24);
            border-radius: 999px;
            display: inline-flex;
            font-size: 11px;
            font-weight: 900;
            gap: 5px;
            padding: 7px 10px;
            white-space: nowrap;
        }

        .operasi-detail-status-note {
            color: rgba(255, 255, 255, .78);
            font-size: 11px;
            font-weight: 800;
            text-align: right;
        }

        .operasi-detail-kpi-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .operasi-detail-kpi {
            align-items: center;
            background: #fff;
            border: 1px solid var(--operasi-line);
            border-radius: 12px;
            display: grid;
            gap: 10px;
            grid-template-columns: 38px minmax(0, 1fr);
            min-width: 0;
            padding: 12px;
        }

        .operasi-detail-kpi.primary {
            background: #ecfeff;
            border-color: #a5f3fc;
        }

        .operasi-detail-kpi-icon {
            align-items: center;
            background: #ecfeff;
            border-radius: 10px;
            color: #0e7490;
            display: inline-flex;
            height: 38px;
            justify-content: center;
            width: 38px;
        }

        .operasi-detail-kpi.primary .operasi-detail-kpi-icon {
            background: #0891b2;
            color: #fff;
        }

        .operasi-detail-kpi-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .operasi-detail-kpi-value {
            color: #0f172a;
            font-size: 18px;
            font-weight: 900;
            line-height: 1.15;
            margin-top: 3px;
            overflow-wrap: anywhere;
        }

        .operasi-detail-kpi-note {
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            margin-top: 3px;
        }

        .operasi-detail-focus-grid,
        .operasi-detail-section-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: minmax(0, 1.15fr) minmax(320px, .85fr);
        }

        .operasi-detail-section-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .operasi-detail-ledger {
            display: grid;
            gap: 10px;
        }

        .operasi-detail-ledger-row {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            display: grid;
            gap: 8px 10px;
            grid-template-columns: 38px minmax(0, 1fr) auto;
            padding: 10px;
        }

        .operasi-detail-ledger-row.total {
            background: #ecfeff;
            border-color: #a5f3fc;
        }

        .operasi-detail-ledger-icon {
            align-items: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            color: #0891b2;
            display: inline-flex;
            height: 38px;
            justify-content: center;
            width: 38px;
        }

        .operasi-detail-ledger-title {
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
        }

        .operasi-detail-ledger-note {
            color: #64748b;
            font-size: 10px;
            margin-top: 2px;
        }

        .operasi-detail-ledger-value {
            color: #0f766e;
            font-size: 14px;
            font-weight: 900;
            text-align: right;
            white-space: nowrap;
        }

        .operasi-detail-ledger-share {
            color: #64748b;
            font-size: 10px;
            font-weight: 900;
            text-align: right;
        }

        .operasi-detail-ledger-track {
            background: #e2e8f0;
            border-radius: 999px;
            grid-column: 2 / 4;
            height: 7px;
            overflow: hidden;
        }

        .operasi-detail-ledger-bar {
            background: linear-gradient(90deg, #0891b2, #0f766e);
            border-radius: inherit;
            display: block;
            height: 100%;
            min-width: 4px;
        }

        .operasi-detail-insights {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .operasi-detail-insight {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px;
        }

        .operasi-detail-insight-icon {
            align-items: center;
            background: #ecfeff;
            border-radius: 10px;
            color: #0e7490;
            display: inline-flex;
            height: 32px;
            justify-content: center;
            margin-bottom: 8px;
            width: 32px;
        }

        .operasi-detail-insight-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .operasi-detail-insight-value {
            color: #0f172a;
            font-size: 16px;
            font-weight: 900;
            line-height: 1.2;
            margin-top: 3px;
            overflow-wrap: anywhere;
        }

        .operasi-detail-insight-note {
            color: #64748b;
            font-size: 10px;
            margin-top: 3px;
        }

        .operasi-detail-role-strip {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .operasi-detail-role-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #0891b2;
            border-radius: 12px;
            min-width: 0;
            padding: 11px;
        }

        .operasi-detail-role-top {
            align-items: center;
            display: flex;
            gap: 8px;
            min-width: 0;
        }

        .operasi-detail-role-top .min-w-0 {
            min-width: 0;
        }

        .operasi-detail-role-icon {
            align-items: center;
            background: #ecfeff;
            border-radius: 10px;
            color: #0e7490;
            display: inline-flex;
            flex: 0 0 34px;
            height: 34px;
            justify-content: center;
            width: 34px;
        }

        .operasi-detail-role-title {
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .operasi-detail-role-count {
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            margin-top: 1px;
        }

        .operasi-detail-role-total {
            color: #0891b2;
            font-size: 16px;
            font-weight: 900;
            margin-top: 9px;
            overflow-wrap: anywhere;
        }

        .operasi-detail-role-pool {
            color: #64748b;
            font-size: 10px;
            margin-top: 3px;
        }

        .operasi-detail-role-meta {
            display: grid;
            gap: 6px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 9px;
        }

        .operasi-detail-role-meta span {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            padding: 6px;
        }

        .operasi-detail-role-strip .operasi-detail-empty {
            grid-column: 1 / -1;
        }

        .operasi-detail-percent-grid {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .operasi-detail-percent-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 11px;
            min-width: 0;
            padding: 10px;
        }

        .operasi-detail-percent-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .operasi-detail-percent-value {
            color: #0f172a;
            font-size: 16px;
            font-weight: 900;
            margin-top: 4px;
        }

        .operasi-detail-percent-note {
            color: #64748b;
            font-size: 10px;
            margin-top: 2px;
        }

        .operasi-detail-percent-track {
            background: #e2e8f0;
            border-radius: 999px;
            height: 6px;
            margin-top: 8px;
            overflow: hidden;
        }

        .operasi-detail-percent-bar {
            background: #0891b2;
            border-radius: inherit;
            display: block;
            height: 100%;
            min-width: 4px;
        }

        .operasi-detail-formula-flow .operasi-flow-row {
            grid-template-columns: 38px minmax(0, 1fr) auto;
        }

        .operasi-detail-pool-list,
        .operasi-detail-timeline,
        .operasi-detail-recipient-list {
            display: grid;
            gap: 10px;
        }

        .operasi-detail-pool-row {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            display: grid;
            gap: 8px;
            grid-template-columns: minmax(0, 1fr) auto;
            padding: 10px;
        }

        .operasi-detail-pool-name {
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
        }

        .operasi-detail-pool-note {
            color: #64748b;
            font-size: 10px;
            margin-top: 2px;
        }

        .operasi-detail-pool-value {
            color: #0891b2;
            font-size: 14px;
            font-weight: 900;
            overflow-wrap: anywhere;
            text-align: right;
        }

        .operasi-detail-audit-row {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            display: grid;
            gap: 10px;
            grid-template-columns: 34px minmax(0, 1fr);
            padding: 10px;
        }

        .operasi-detail-audit-icon {
            align-items: center;
            background: #ecfeff;
            border-radius: 9px;
            color: #0e7490;
            display: inline-flex;
            height: 34px;
            justify-content: center;
            width: 34px;
        }

        .operasi-detail-audit-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .operasi-detail-audit-value {
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
            margin-top: 2px;
            overflow-wrap: anywhere;
        }

        .operasi-detail-recipient-card {
            min-width: 0;
        }

        .operasi-detail-recipient-group {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }

        .operasi-detail-recipient-head {
            align-items: center;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(0, 1fr) auto;
            padding: 11px;
        }

        .operasi-detail-recipient-members {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            padding: 10px;
        }

        .operasi-detail-recipient-member {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            min-width: 0;
            padding: 10px;
        }

        .operasi-detail-recipient-name {
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        .operasi-detail-recipient-position {
            color: #64748b;
            font-size: 10px;
            margin-top: 2px;
        }

        .operasi-detail-recipient-meta {
            color: #64748b;
            font-size: 10px;
            margin-top: 7px;
        }

        .operasi-detail-recipient-total {
            color: #0f766e;
            font-size: 16px;
            font-weight: 900;
            margin-top: 5px;
            overflow-wrap: anywhere;
        }

        .operasi-detail-recipient-percent {
            align-items: center;
            background: #ecfeff;
            border: 1px solid #a5f3fc;
            border-radius: 999px;
            color: #0e7490;
            display: inline-flex;
            font-size: 10px;
            font-weight: 900;
            gap: 4px;
            margin-top: 8px;
            padding: 4px 7px;
        }

        .operasi-detail-empty {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 10px;
            color: #92400e;
            font-size: 12px;
            padding: 12px;
        }

        .operasi-detail-simple {
            display: grid;
            gap: 14px;
        }

        .operasi-detail-simple .operasi-detail-hero {
            align-items: start;
            grid-template-columns: minmax(0, 1fr) auto;
            padding: 16px;
        }

        .operasi-detail-simple .operasi-detail-kpi {
            display: block;
            min-width: 0;
        }

        .operasi-detail-simple .operasi-detail-kpi-value {
            margin-top: 5px;
        }

        .operasi-detail-note {
            background: #ecfeff;
            border: 1px solid #a5f3fc;
            border-radius: 10px;
            color: #155e75;
            font-size: 12px;
            line-height: 1.5;
            padding: 11px 12px;
        }

        .operasi-detail-simple-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        }

        .operasi-detail-steps {
            counter-reset: detail-step;
            display: grid;
            gap: 9px;
        }

        .operasi-detail-step {
            align-items: start;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            counter-increment: detail-step;
            display: grid;
            gap: 9px;
            grid-template-columns: 26px minmax(0, 1fr);
            padding: 10px;
        }

        .operasi-detail-step::before {
            align-items: center;
            background: #0891b2;
            border-radius: 999px;
            color: #fff;
            content: counter(detail-step);
            display: inline-flex;
            font-size: 11px;
            font-weight: 900;
            height: 26px;
            justify-content: center;
            width: 26px;
        }

        .operasi-detail-step-title {
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
        }

        .operasi-detail-step-text {
            color: #475569;
            font-size: 11px;
            line-height: 1.45;
            margin-top: 2px;
        }

        .operasi-detail-simple-table {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .operasi-detail-simple-row {
            align-items: center;
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(0, 1fr) auto;
            padding: 10px 11px;
        }

        .operasi-detail-simple-row:last-child {
            border-bottom: 0;
        }

        .operasi-detail-simple-row.highlight {
            background: #ecfeff;
        }

        .operasi-detail-simple-label {
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
        }

        .operasi-detail-simple-note {
            color: #64748b;
            font-size: 10px;
            margin-top: 2px;
        }

        .operasi-detail-simple-value {
            color: #0f766e;
            font-size: 13px;
            font-weight: 900;
            text-align: right;
            white-space: nowrap;
        }

        .operasi-detail-simple .operasi-detail-recipient-group {
            border-radius: 10px;
        }

        .operasi-detail-simple .operasi-detail-recipient-head {
            grid-template-columns: minmax(0, 1fr) auto;
        }

        .operasi-detail-simple .operasi-detail-recipient-members {
            grid-template-columns: 1fr;
        }

        .operasi-detail-simple .operasi-detail-recipient-member {
            align-items: center;
            display: grid;
            gap: 8px;
            grid-template-columns: minmax(0, 1fr) auto;
        }

        .operasi-detail-simple .operasi-detail-recipient-total {
            margin-top: 0;
            text-align: right;
            white-space: nowrap;
        }

        @media (max-width: 991.98px) {

            .operasi-hero,
            .operasi-panel-head,
            .operasi-filter-bar {
                align-items: stretch;
                flex-direction: column;
            }

            .operasi-hero-controls,
            .operasi-panel-actions,
            .operasi-search {
                flex-basis: auto;
                width: 100%;
            }

            .operasi-summary-grid,
            .operasi-config-grid,
            .operasi-config-flow,
            .operasi-config-overview,
            .operasi-generate-layout,
            .operasi-detail-hero,
            .operasi-detail-focus-grid,
            .operasi-detail-section-grid,
            .operasi-detail-simple-grid,
            .operasi-detail-kpi-grid,
            .operasi-detail-grid,
            .operasi-detail-percent-grid,
            .operasi-detail-role-strip,
            .operasi-detail-insights,
            .operasi-workflow-band,
            .operasi-mini-stat-grid,
            .operasi-command-grid,
            .operasi-command-main,
            .operasi-context-strip,
            .operasi-preview-insight {
                grid-template-columns: 1fr;
            }

            .operasi-command-actions {
                justify-content: stretch;
            }

            .operasi-command-actions .operasi-btn {
                justify-content: center;
                width: 100%;
            }

            .operasi-context-item {
                border-bottom: 1px solid #e2e8f0;
                border-right: 0;
            }

            .operasi-context-item:last-child {
                border-bottom: 0;
            }

            .operasi-detail-hero-side {
                align-items: flex-start;
                justify-items: start;
            }

            .operasi-detail-ledger-row,
            .operasi-detail-recipient-head {
                grid-template-columns: 1fr;
            }

            .operasi-detail-ledger-track {
                grid-column: 1;
            }

            .operasi-detail-ledger-value,
            .operasi-detail-ledger-share,
            .operasi-detail-simple-value,
            .operasi-detail-pool-value {
                text-align: left;
            }

            .operasi-detail-simple .operasi-detail-recipient-head,
            .operasi-detail-simple .operasi-detail-recipient-member,
            .operasi-detail-simple-row {
                grid-template-columns: 1fr;
            }

            .operasi-detail-simple .operasi-detail-recipient-total {
                text-align: left;
            }
        }
    </style>
@endpush

@section("content")
    <div class="operasi-page">
        <nav class="page-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route("backOffice.keuangan.hitungPremi") }}">Generate Premi</a>
                </li>
                <li class="breadcrumb-item active">Generate Operasi</li>
            </ol>
        </nav>

        <section class="operasi-hero">
            <div class="operasi-hero-main">
                <div class="operasi-hero-icon">
                    <i class="mdi mdi-knife"></i>
                </div>
                <div>
                    <div class="operasi-eyebrow">Semi Otomatis</div>
                    <h4 class="operasi-title">Generate Premi Operasi</h4>
                    <p class="operasi-description">
                        Input total nominal pendapatan operasi, sistem mengambil konfigurasi persentase dan penerima
                        premi untuk menampilkan preview sebelum hasil disimpan.
                    </p>
                    <div class="operasi-hero-facts">
                        <span class="operasi-hero-fact">
                            <i class="mdi mdi-eye-check-outline"></i>
                            Preview wajib sebelum simpan
                        </span>
                        <span class="operasi-hero-fact">
                            <i class="mdi mdi-cog-sync-outline"></i>
                            Konfigurasi UMUM/BPJS terpisah
                        </span>
                        <span class="operasi-hero-fact">
                            <i class="mdi mdi-shield-check-outline"></i>
                            Snapshot rumus tersimpan
                        </span>
                    </div>
                </div>
            </div>
            <div class="operasi-hero-controls">
                <div class="operasi-control-box">
                    <label for="periodeOperasi">Periode Perhitungan</label>
                    <div class="operasi-period-picker">
                        <button type="button" class="operasi-period-step" id="btnPrevPeriodOperasi">
                            <i class="mdi mdi-chevron-left"></i>
                        </button>
                        <input type="month" id="periodeOperasi" class="form-control">
                        <button type="button" class="operasi-period-step" id="btnNextPeriodOperasi">
                            <i class="mdi mdi-chevron-right"></i>
                        </button>
                    </div>
                    <button type="button" class="operasi-period-current" id="btnCurrentPeriodOperasi">
                        <i class="mdi mdi-calendar-today-outline"></i>
                        Bulan Ini
                    </button>
                </div>
            </div>
        </section>

        <div class="operasi-type-tabs">
            <button type="button" class="operasi-type-tab active" data-type="umum">
                <i class="mdi mdi-account-cash-outline"></i>
                <span>
                    <strong>UMUM</strong>
                    <small>Konfigurasi dan hasil operasi umum</small>
                </span>
            </button>
            <button type="button" class="operasi-type-tab" data-type="bpjs">
                <i class="mdi mdi-shield-account-outline"></i>
                <span>
                    <strong>BPJS</strong>
                    <small>Konfigurasi dan hasil operasi BPJS</small>
                </span>
            </button>
        </div>

        <div class="operasi-workflow-band">
            <div class="operasi-workflow-card">
                <div class="operasi-workflow-icon"><i class="mdi mdi-tune-variant"></i></div>
                <div>
                    <div class="operasi-workflow-title">1. Pastikan konfigurasi</div>
                    <div class="operasi-workflow-text">
                        Atur persentase dan penerima sesuai jenis aktif sebelum generate bulanan.
                    </div>
                </div>
            </div>
            <div class="operasi-workflow-card">
                <div class="operasi-workflow-icon"><i class="mdi mdi-cash-multiple"></i></div>
                <div>
                    <div class="operasi-workflow-title">2. Input total operasi</div>
                    <div class="operasi-workflow-text">
                        Masukkan total nominal pendapatan operasi sesuai periode yang sedang diproses.
                    </div>
                </div>
            </div>
            <div class="operasi-workflow-card">
                <div class="operasi-workflow-icon"><i class="mdi mdi-file-eye-outline"></i></div>
                <div>
                    <div class="operasi-workflow-title">3. Preview dan simpan</div>
                    <div class="operasi-workflow-text">
                        Cek pool, penerima, premi bersama, dan sisa sebelum hasil generate disimpan.
                    </div>
                </div>
            </div>
        </div>

        <div class="operasi-command-grid">
            <div class="operasi-command-card">
                <div class="operasi-command-main">
                    <div>
                        <div class="operasi-command-eyebrow">Aksi Utama</div>
                        <div class="operasi-command-title">Siapkan dan generate premi operasi</div>
                        <div class="operasi-command-text">
                            Gunakan konfigurasi aktif, masukkan total nominal pendapatan operasi, lalu cek preview
                            sebelum hasil disimpan.
                        </div>
                    </div>
                    <div class="operasi-command-actions">
                        <button type="button" class="operasi-btn operasi-btn-light" id="btnCommandConfigOperasi">
                            <i class="mdi mdi-cog-outline"></i>
                            Atur Konfigurasi
                        </button>
                        <button type="button" class="operasi-btn operasi-btn-primary" id="btnCommandGenerateOperasi">
                            <i class="mdi mdi-calculator-variant-outline"></i>
                            Mulai Generate
                        </button>
                    </div>
                </div>
                <div class="operasi-context-strip">
                    <div class="operasi-context-item">
                        <div class="operasi-context-label">Periode Aktif</div>
                        <div class="operasi-context-value" id="contextPeriodeOperasi">-</div>
                    </div>
                    <div class="operasi-context-item">
                        <div class="operasi-context-label">Jenis Aktif</div>
                        <div class="operasi-context-value" id="contextJenisOperasi">Umum</div>
                    </div>
                    <div class="operasi-context-item">
                        <div class="operasi-context-label">Status Proses</div>
                        <div class="operasi-context-value" id="contextStatusOperasi">Siap preview</div>
                    </div>
                </div>
            </div>

            <div class="operasi-command-card operasi-formula-card">
                <div class="operasi-formula-title">Komposisi default</div>
                <div class="operasi-formula-subtitle">
                    Nilai ini tetap bisa berbeda untuk UMUM dan BPJS lewat konfigurasi.
                </div>
                <div class="operasi-formula-chips">
                    <span class="operasi-formula-chip"><strong>10%</strong> instrumen</span>
                    <span class="operasi-formula-chip"><strong>20%</strong> premi bersama</span>
                    <span class="operasi-formula-chip"><strong>80%</strong> petugas instrumen</span>
                    <span class="operasi-formula-chip"><strong>40%</strong> anastesi</span>
                    <span class="operasi-formula-chip"><strong>10%</strong> perawat anastesi</span>
                </div>
            </div>
        </div>

        <section class="operasi-panel">
            <div class="operasi-panel-head">
                <div>
                    <div class="operasi-panel-title">Ringkasan Generate Operasi</div>
                    <div class="operasi-panel-subtitle" id="summaryOperasiSubtitle">
                        Data dihitung dari hasil generate operasi per periode.
                    </div>
                </div>
                <div class="operasi-panel-actions">
                    <button type="button" class="operasi-btn operasi-btn-light" id="btnRefreshOperasi">
                        <i class="mdi mdi-refresh"></i>
                        Refresh
                    </button>
                    <button type="button" class="operasi-btn operasi-btn-light" id="btnOpenConfigOperasi">
                        <i class="mdi mdi-cog-outline"></i>
                        Konfigurasi Premi
                    </button>
                    <button type="button" class="operasi-btn operasi-btn-primary" id="btnOpenGenerateOperasi">
                        <i class="mdi mdi-plus-circle-outline"></i>
                        Generate Operasi
                    </button>
                </div>
            </div>
            <div class="operasi-summary-grid">
                <div class="operasi-summary-card total">
                    <div class="operasi-summary-label">Total Operasi</div>
                    <div class="operasi-summary-value" id="summaryTotalOperasi">Rp 0</div>
                </div>
                <div class="operasi-summary-card">
                    <div class="operasi-summary-label">Total Dibagikan</div>
                    <div class="operasi-summary-value" id="summaryDibagikanOperasi">Rp 0</div>
                </div>
                <div class="operasi-summary-card">
                    <div class="operasi-summary-label">Premi Bersama</div>
                    <div class="operasi-summary-value" id="summaryBersamaOperasi">Rp 0</div>
                </div>
                <div class="operasi-summary-card">
                    <div class="operasi-summary-label">Data Terkunci</div>
                    <div class="operasi-summary-value" id="summaryLockedOperasi">0</div>
                </div>
            </div>
        </section>

        <section class="operasi-panel">
            <div class="operasi-filter-bar">
                <div>
                    <div class="operasi-panel-title" id="resultOperasiTitle">Hasil Generate Operasi Umum</div>
                    <div class="operasi-panel-subtitle">Satu periode dan jenis hanya menyimpan satu hasil aktif.</div>
                </div>
                <div class="operasi-search input-group">
                    <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                    <input type="text" class="form-control" id="searchGenerateOperasi"
                        placeholder="Cari periode / jenis...">
                </div>
            </div>
            <div class="operasi-table-wrap">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100" id="tableGenerateOperasi">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Periode</th>
                                <th>Jenis</th>
                                <th>Total Operasi</th>
                                <th>Premi Bersama</th>
                                <th>Dibagikan</th>
                                <th>Penerima</th>
                                <th>Status</th>
                                <th>Generate</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </section>
    </div>

    @include("simrs.backOffice.keuangan.hitungPremi.generateOperasi.modal")
@endsection

@push("scripts")
    @include("simrs.backOffice.keuangan.hitungPremi.generateOperasi.jsMain")
@endpush
