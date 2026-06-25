@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.select2")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")

    <style>
        .icu-page {
            --icu-main: #0891b2;
            --icu-deep: #164e63;
            --icu-accent: #0f766e;
            --icu-amber: #d97706;
            --icu-success: #16a34a;
            --icu-ink: #172033;
            --icu-muted: #64748b;
            --icu-line: #e2e8f0;
            --icu-soft: #cffafe;
            --icu-teal: #0f766e;
            --icu-blue: #2563eb;
            color: var(--icu-ink);
        }

        .icu-page .btn {
            align-items: center;
            border-radius: 8px;
            display: inline-flex;
            font-weight: 700;
            gap: 5px;
            justify-content: center;
            transition: background-color .18s ease, border-color .18s ease, box-shadow .18s ease, transform .18s ease;
        }

        .icu-page .btn:hover {
            transform: translateY(-1px);
        }

        .icu-page .breadcrumb {
            background: transparent;
            font-size: 13px;
            margin-bottom: 0;
            padding: 0;
        }

        .icu-hero {
            align-items: stretch;
            background: linear-gradient(135deg, #fff 0%, #cffafe 48%, #ecfeff 100%);
            border: 1px solid var(--icu-line);
            border-left: 5px solid var(--icu-main);
            border-radius: 8px;
            box-shadow: 0 16px 38px rgba(15, 23, 42, .08);
            display: flex;
            gap: 18px;
            justify-content: space-between;
            margin-top: 14px;
            padding: 18px;
        }

        .icu-hero-main {
            align-items: flex-start;
            display: flex;
            gap: 13px;
            min-width: 0;
        }

        .icu-hero-icon,
        .icu-mini-icon {
            align-items: center;
            border-radius: 8px;
            display: inline-flex;
            justify-content: center;
        }

        .icu-hero-icon {
            background: #cffafe;
            color: var(--icu-main);
            flex: 0 0 auto;
            font-size: 29px;
            height: 54px;
            width: 54px;
        }

        .icu-eyebrow {
            color: var(--icu-main);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .icu-title {
            color: #111827;
            font-size: 23px;
            font-weight: 800;
            margin: 3px 0 5px;
        }

        .icu-description {
            color: var(--icu-muted);
            font-size: 13px;
            line-height: 1.55;
            margin: 0;
            max-width: 760px;
        }

        .icu-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 12px;
        }

        .icu-metric-chip {
            align-items: center;
            background: rgba(255, 255, 255, .86);
            border: 1px solid rgba(226, 232, 240, .95);
            border-radius: 999px;
            color: #475569;
            display: inline-flex;
            font-size: 11px;
            font-weight: 800;
            gap: 6px;
            min-height: 30px;
            padding: 6px 10px;
        }

        .icu-metric-chip i {
            color: var(--icu-main);
            font-size: 15px;
        }

        .icu-metric-chip strong {
            color: #0f172a;
            font-weight: 850;
        }

        .icu-hero-controls {
            display: grid;
            flex: 0 0 320px;
            gap: 10px;
        }

        .icu-control-box {
            background: #f8fafc;
            border: 1px solid var(--icu-line);
            border-radius: 8px;
            padding: 11px;
        }

        .icu-control-box label {
            color: #475569;
            display: block;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .icu-period-picker {
            align-items: center;
            display: grid;
            gap: 7px;
            grid-template-columns: 38px minmax(0, 1fr) 38px;
        }

        .icu-period-picker .form-control {
            height: 38px;
            text-align: center;
        }

        .icu-period-step {
            align-items: center;
            border-radius: 8px;
            display: inline-flex;
            font-size: 17px;
            height: 38px;
            justify-content: center;
            padding: 0;
        }

        .icu-type-tabs {
            background: #fff;
            border: 1px solid var(--icu-line);
            border-radius: 8px;
            display: flex;
            gap: 8px;
            margin: 15px 0;
            padding: 8px;
        }

        .icu-type-tab {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 8px;
            color: #64748b;
            display: flex;
            flex: 1;
            gap: 10px;
            padding: 10px 12px;
            text-align: left;
            transition: background-color .18s ease, box-shadow .18s ease, color .18s ease;
        }

        .icu-type-tab i {
            font-size: 22px;
        }

        .icu-type-tab strong,
        .icu-type-tab small {
            display: block;
        }

        .icu-type-tab small {
            font-size: 11px;
        }

        .icu-type-tab:hover {
            background: #f8fafc;
            color: #334155;
        }

        .icu-type-tab.active {
            background: #cffafe;
            box-shadow: inset 0 0 0 1px #67e8f9;
            color: #0e7490;
        }

        .icu-panel {
            background: #fff;
            border: 1px solid var(--icu-line);
            border-radius: 8px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, .06);
            margin-bottom: 16px;
            overflow: hidden;
        }

        .icu-live-strip {
            background: #f8fafc;
            border-bottom: 1px solid #eef2f7;
            display: grid;
            gap: 1px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .icu-live-item {
            background: #fff;
            min-width: 0;
            padding: 12px 16px;
        }

        .icu-live-label {
            color: #64748b;
            font-size: 11px;
            font-weight: 850;
            text-transform: uppercase;
        }

        .icu-live-value {
            align-items: center;
            color: #0f172a;
            display: flex;
            font-size: 13px;
            font-weight: 850;
            gap: 6px;
            margin-top: 4px;
            min-width: 0;
        }

        .icu-live-value i {
            color: var(--icu-main);
            font-size: 17px;
        }

        .icu-summary-board {
            display: grid;
            gap: 12px;
            padding: 14px 16px 16px;
        }

        .icu-summary-spotlight {
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(0, 1.35fr) minmax(280px, .65fr);
        }

        .icu-summary-total-card,
        .icu-summary-status-card,
        .icu-summary-distribution {
            border: 1px solid var(--icu-line);
            border-radius: 8px;
            min-width: 0;
        }

        .icu-summary-total-card {
            background: linear-gradient(135deg, #cffafe 0%, #fff 44%, #ecfeff 100%);
            border-left: 5px solid var(--icu-main);
            display: grid;
            gap: 12px;
            padding: 14px;
        }

        .icu-summary-eyebrow {
            color: var(--icu-main);
            font-size: 11px;
            font-weight: 850;
            text-transform: uppercase;
        }

        .icu-summary-main-value {
            color: #0f172a;
            font-size: 31px;
            font-weight: 900;
            line-height: 1.1;
            word-break: break-word;
        }

        .icu-summary-caption {
            color: #64748b;
            font-size: 12px;
            line-height: 1.45;
            max-width: 760px;
        }

        .icu-summary-pill-row {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .icu-summary-pill {
            align-items: center;
            background: rgba(255, 255, 255, .9);
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            color: #475569;
            display: inline-flex;
            font-size: 11px;
            font-weight: 850;
            gap: 5px;
            padding: 6px 10px;
        }

        .icu-summary-pill i {
            color: var(--icu-main);
            font-size: 15px;
        }

        .icu-summary-status-card {
            background: #fff;
            display: grid;
            gap: 10px;
            padding: 14px;
        }

        .icu-summary-status-top {
            align-items: center;
            display: flex;
            gap: 10px;
        }

        .icu-summary-status-icon {
            align-items: center;
            border-radius: 8px;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 22px;
            height: 42px;
            justify-content: center;
            width: 42px;
        }

        .icu-summary-status-card.success .icu-summary-status-icon {
            background: #dcfce7;
            color: #15803d;
        }

        .icu-summary-status-card.warning .icu-summary-status-icon {
            background: #fffbeb;
            color: #b45309;
        }

        .icu-summary-status-card.neutral .icu-summary-status-icon {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .icu-summary-status-title {
            color: #0f172a;
            font-size: 14px;
            font-weight: 850;
        }

        .icu-summary-status-copy {
            color: #64748b;
            font-size: 12px;
            line-height: 1.45;
        }

        .icu-summary-health-grid {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .icu-summary-health-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            min-width: 0;
            padding: 9px;
        }

        .icu-summary-health-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 850;
            text-transform: uppercase;
        }

        .icu-summary-health-value {
            color: #0f172a;
            font-size: 13px;
            font-weight: 850;
            margin-top: 3px;
            word-break: break-word;
        }

        .icu-summary-toolbar {
            align-items: center;
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }

        .icu-summary-view-group {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: inline-flex;
            gap: 5px;
            padding: 5px;
        }

        .icu-summary-view {
            background: transparent;
            border: 0;
            border-radius: 8px;
            color: #64748b;
            font-size: 12px;
            font-weight: 850;
            min-height: 32px;
            padding: 6px 11px;
        }

        .icu-summary-view.active {
            background: #fff;
            box-shadow: 0 7px 16px rgba(15, 23, 42, .08);
            color: var(--icu-main);
        }

        .icu-summary-note {
            color: #64748b;
            font-size: 12px;
        }

        .icu-summary-insights {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .icu-summary-insight {
            align-items: flex-start;
            background: #fff;
            border: 1px solid var(--icu-line);
            border-radius: 8px;
            display: flex;
            gap: 10px;
            min-width: 0;
            padding: 12px;
        }

        .icu-summary-insight-icon {
            align-items: center;
            background: #f8fafc;
            border-radius: 8px;
            color: var(--icu-main);
            display: inline-flex;
            flex: 0 0 auto;
            height: 34px;
            justify-content: center;
            width: 34px;
        }

        .icu-summary-insight-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 850;
            text-transform: uppercase;
        }

        .icu-summary-insight-value {
            color: #0f172a;
            font-size: 15px;
            font-weight: 850;
            line-height: 1.25;
            margin-top: 2px;
            word-break: break-word;
        }

        .icu-summary-insight-note {
            color: #64748b;
            font-size: 11px;
            line-height: 1.35;
            margin-top: 3px;
        }

        .icu-summary-distribution {
            background: #fff;
            display: grid;
            gap: 10px;
            padding: 12px;
        }

        .icu-summary-distribution-head {
            align-items: center;
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }

        .icu-summary-distribution-title {
            color: #0f172a;
            font-size: 13px;
            font-weight: 850;
        }

        .icu-summary-distribution-subtitle {
            color: #64748b;
            font-size: 11px;
        }

        .icu-summary-bars {
            display: grid;
            gap: 9px;
        }

        .icu-summary-bar-top {
            align-items: center;
            display: flex;
            font-size: 11px;
            font-weight: 850;
            justify-content: space-between;
        }

        .icu-summary-bar-track {
            background: #e2e8f0;
            border-radius: 999px;
            height: 9px;
            overflow: hidden;
        }

        .icu-summary-bar-fill {
            background: var(--icu-main);
            border-radius: inherit;
            height: 100%;
            min-width: 3px;
        }

        .icu-summary-bar-fill.teal {
            background: var(--icu-teal);
        }

        .icu-summary-bar-fill.blue {
            background: var(--icu-blue);
        }

        .icu-summary-bar-fill.amber {
            background: var(--icu-amber);
        }

        .icu-panel-head,
        .icu-filter-bar {
            align-items: center;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 14px 16px;
        }

        .icu-panel-head {
            border-bottom: 1px solid #eef2f7;
        }

        .icu-panel-title {
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
        }

        .icu-panel-subtitle {
            color: var(--icu-muted);
            font-size: 12px;
        }

        .icu-command-row {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .icu-summary-grid,
        .icu-preview-grid,
        .icu-detail-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            padding: 14px 16px;
        }

        .icu-summary-board .icu-summary-grid {
            padding: 0;
        }

        .icu-summary-card-hidden {
            display: none;
        }

        .icu-mini-card {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            min-width: 0;
            padding: 12px;
            transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
        }

        .icu-mini-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 12px 24px rgba(15, 23, 42, .07);
            transform: translateY(-1px);
        }

        .icu-mini-card.total {
            background: linear-gradient(135deg, #cffafe 0%, #fff 100%);
            border-color: #67e8f9;
        }

        .icu-mini-top {
            align-items: center;
            display: flex;
            justify-content: space-between;
        }

        .icu-mini-label {
            color: var(--icu-muted);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .icu-mini-icon {
            background: #fff;
            color: var(--icu-main);
            font-size: 18px;
            height: 30px;
            width: 30px;
        }

        .icu-mini-value {
            color: #0f172a;
            font-size: 20px;
            font-weight: 850;
            margin-top: 8px;
            word-break: break-word;
        }

        .icu-mini-note {
            color: #64748b;
            font-size: 11px;
            margin-top: 4px;
        }

        .icu-table-wrap {
            padding: 0 16px 16px;
        }

        .icu-table-wrap table {
            width: 100% !important;
        }

        .icu-actions {
            display: inline-flex;
            gap: 5px;
        }

        .icu-actions .btn {
            align-items: center;
            border-radius: 8px;
            display: inline-flex;
            height: 32px;
            justify-content: center;
            padding: 0;
            width: 32px;
        }

        .icu-actions .btn-detail-icu {
            padding: 0 10px;
            width: auto;
        }

        .icu-status {
            border-radius: 999px;
            display: inline-flex;
            font-size: 11px;
            font-weight: 800;
            padding: 5px 9px;
        }

        .icu-status.locked {
            background: #cffafe;
            color: #b91c1c;
        }

        .icu-status.open {
            background: #dcfce7;
            color: #15803d;
        }

        .icu-modal-content {
            border: 0;
            border-radius: 8px;
            box-shadow: 0 28px 70px rgba(15, 23, 42, .24);
            overflow: hidden;
        }

        .icu-modal-header {
            background: #fff;
            border-bottom: 1px solid #eef2f7;
            padding: 14px 16px;
        }

        .icu-modal-content .modal-body {
            background: #fbfdff;
        }

        .icu-modal-content .modal-footer {
            background: #fff;
            border-top: 1px solid #eef2f7;
        }

        .icu-simple-head {
            align-items: center;
            background: linear-gradient(135deg, #f8fafc 0%, #fff 100%);
            border: 1px solid var(--icu-line);
            border-radius: 8px;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 14px;
            padding: 13px;
        }

        .icu-simple-main {
            align-items: center;
            display: flex;
            gap: 11px;
            min-width: 0;
        }

        .icu-simple-icon {
            align-items: center;
            background: #cffafe;
            border-radius: 8px;
            color: var(--icu-main);
            display: flex;
            flex: 0 0 auto;
            font-size: 24px;
            height: 44px;
            justify-content: center;
            width: 44px;
        }

        .icu-simple-title {
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
        }

        .icu-simple-text,
        .icu-simple-section-subtitle {
            color: var(--icu-muted);
            font-size: 12px;
        }

        .icu-simple-badge {
            background: #fff;
            border: 1px solid #67e8f9;
            border-radius: 999px;
            color: #0e7490;
            flex: 0 0 auto;
            font-size: 11px;
            font-weight: 800;
            padding: 6px 10px;
        }

        .icu-simple-badge.locked {
            background: #fee2e2;
            border-color: #fecaca;
            color: #b91c1c;
        }

        .icu-simple-badge.open {
            background: #dcfce7;
            border-color: #bbf7d0;
            color: #15803d;
        }

        .icu-simple-badge.info {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .icu-state-banner {
            align-items: flex-start;
            border: 1px solid var(--icu-line);
            border-radius: 8px;
            display: flex;
            gap: 10px;
            margin-bottom: 14px;
            padding: 12px;
        }

        .icu-state-banner.compact {
            align-items: center;
            margin-bottom: 12px;
            padding: 9px 11px;
        }

        .icu-state-banner.compact i {
            font-size: 18px;
        }

        .icu-state-banner.compact .icu-state-text {
            margin-top: 0;
        }

        .icu-state-banner i {
            flex: 0 0 auto;
            font-size: 22px;
            margin-top: 1px;
        }

        .icu-state-title {
            color: #0f172a;
            font-size: 13px;
            font-weight: 850;
        }

        .icu-state-text {
            color: #64748b;
            font-size: 12px;
            line-height: 1.45;
            margin-top: 2px;
        }

        .icu-state-banner.success {
            background: #ecfdf5;
            border-color: #bbf7d0;
        }

        .icu-state-banner.success i {
            color: var(--icu-success);
        }

        .icu-state-banner.warning {
            background: #fffbeb;
            border-color: #fde68a;
        }

        .icu-state-banner.warning i {
            color: var(--icu-amber);
        }

        .icu-state-banner.danger {
            background: #fff1f2;
            border-color: #fecdd3;
        }

        .icu-state-banner.danger i {
            color: #be123c;
        }

        .icu-state-banner.neutral {
            background: #f8fafc;
        }

        .icu-state-banner.neutral i {
            color: #2563eb;
        }

        .icu-flow {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .icu-flow-step {
            align-items: flex-start;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: flex;
            gap: 10px;
            min-width: 0;
            padding: 11px;
        }

        .icu-flow-step-icon {
            align-items: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: #64748b;
            display: inline-flex;
            flex: 0 0 auto;
            height: 32px;
            justify-content: center;
            width: 32px;
        }

        .icu-flow-step-title {
            color: #0f172a;
            font-size: 12px;
            font-weight: 850;
        }

        .icu-flow-step-note {
            color: #64748b;
            font-size: 11px;
            line-height: 1.45;
            margin-top: 2px;
        }

        .icu-flow-step.done {
            background: #ecfdf5;
            border-color: #bbf7d0;
        }

        .icu-flow-step.done .icu-flow-step-icon {
            color: var(--icu-success);
        }

        .icu-flow-step.warning {
            background: #fffbeb;
            border-color: #fde68a;
        }

        .icu-flow-step.warning .icu-flow-step-icon {
            color: var(--icu-amber);
        }

        .icu-flow-step.active {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .icu-flow-step.active .icu-flow-step-icon {
            color: #2563eb;
        }

        .icu-insight-strip {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .icu-insight-card {
            background: #fff;
            border: 1px solid var(--icu-line);
            border-radius: 8px;
            display: flex;
            gap: 10px;
            min-width: 0;
            padding: 12px;
        }

        .icu-insight-icon {
            align-items: center;
            background: #f8fafc;
            border-radius: 8px;
            color: var(--icu-main);
            display: inline-flex;
            flex: 0 0 auto;
            height: 36px;
            justify-content: center;
            width: 36px;
        }

        .icu-insight-label {
            color: #64748b;
            font-size: 11px;
            font-weight: 850;
            text-transform: uppercase;
        }

        .icu-insight-value {
            color: #0f172a;
            font-size: 17px;
            font-weight: 850;
            line-height: 1.2;
            margin-top: 2px;
            word-break: break-word;
        }

        .icu-insight-note {
            color: #64748b;
            font-size: 11px;
            line-height: 1.35;
            margin-top: 3px;
        }

        .icu-simple-section {
            border: 1px solid var(--icu-line);
            border-radius: 8px;
            padding: 13px;
        }

        .icu-simple-section-title {
            color: #0f172a;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .icu-info-list {
            display: grid;
            gap: 8px;
        }

        .icu-info-list.compact {
            gap: 6px;
        }

        .icu-info-list.compact .icu-info-row {
            padding: 8px 9px;
        }

        .icu-info-row {
            align-items: center;
            background: #f8fafc;
            border-radius: 8px;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 10px;
        }

        .icu-info-label {
            color: #475569;
            font-size: 12px;
            font-weight: 800;
        }

        .icu-info-note {
            color: #64748b;
            font-size: 11px;
            margin-top: 2px;
        }

        .icu-info-value {
            color: #0f172a;
            font-size: 12px;
            font-weight: 800;
            text-align: right;
        }

        .icu-warning {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            color: #92400e;
            font-size: 12px;
            font-weight: 700;
            margin-top: 10px;
            padding: 10px;
        }

        .icu-empty-state {
            align-items: center;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            color: #64748b;
            display: flex;
            gap: 10px;
            padding: 13px;
        }

        .icu-empty-state i {
            color: #94a3b8;
            font-size: 24px;
        }

        .icu-empty-title {
            color: #334155;
            font-size: 12px;
            font-weight: 850;
        }

        .icu-empty-text {
            font-size: 11px;
            line-height: 1.35;
            margin-top: 2px;
        }

        .icu-detail-table {
            border: 1px solid var(--icu-line);
            border-radius: 8px;
            max-height: 360px;
            overflow: auto;
        }

        .icu-detail-table table {
            margin-bottom: 0;
        }

        .icu-detail-table.compact {
            max-height: 260px;
        }

        .icu-detail-table.premium {
            background: #fff;
            max-height: 390px;
        }

        .icu-detail-table.premium.compact {
            max-height: 300px;
        }

        .icu-detail-split,
        .icu-detail-overview {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .icu-detail-overview {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .icu-detail-premium {
            display: grid;
            gap: 12px;
        }

        .icu-detail-hero {
            align-items: flex-start;
            background: linear-gradient(135deg, #fff 0%, #cffafe 50%, #ecfeff 100%);
            border: 1px solid #e2e8f0;
            border-left: 5px solid var(--icu-main);
            border-radius: 8px;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 13px;
        }

        .icu-detail-hero-main {
            align-items: center;
            display: flex;
            gap: 11px;
            min-width: 0;
        }

        .icu-detail-title {
            color: #0f172a;
            font-size: 16px;
            font-weight: 850;
        }

        .icu-detail-subtitle {
            color: #64748b;
            font-size: 12px;
            line-height: 1.45;
            margin-top: 1px;
        }

        .icu-detail-badge-row {
            align-items: center;
            display: flex;
            flex: 0 0 auto;
            flex-wrap: wrap;
            gap: 7px;
            justify-content: flex-end;
        }

        .icu-detail-kpi-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .icu-detail-kpi {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            min-width: 0;
            padding: 11px;
        }

        .icu-detail-kpi.primary {
            background: linear-gradient(135deg, #cffafe 0%, #fff 100%);
            border-color: #67e8f9;
        }

        .icu-detail-kpi-top {
            align-items: center;
            display: flex;
            justify-content: space-between;
        }

        .icu-detail-kpi-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 850;
            text-transform: uppercase;
        }

        .icu-detail-kpi-icon {
            align-items: center;
            background: #f8fafc;
            border-radius: 8px;
            color: var(--icu-main);
            display: inline-flex;
            height: 28px;
            justify-content: center;
            width: 28px;
        }

        .icu-detail-kpi-value {
            color: #0f172a;
            font-size: 18px;
            font-weight: 850;
            line-height: 1.2;
            margin-top: 6px;
            word-break: break-word;
        }

        .icu-detail-kpi-note {
            color: #64748b;
            font-size: 11px;
            margin-top: 3px;
        }

        .icu-detail-tabs {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: grid;
            gap: 6px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            padding: 6px;
        }

        .icu-detail-tabs .nav-item,
        .icu-detail-tabs .nav-link {
            width: 100%;
        }

        .icu-detail-tabs .nav-link {
            align-items: center;
            border-radius: 8px;
            color: #64748b;
            display: inline-flex;
            font-size: 12px;
            font-weight: 850;
            gap: 6px;
            justify-content: center;
            min-height: 38px;
        }

        .icu-detail-tabs .nav-link.active {
            background: #fff;
            box-shadow: 0 8px 18px rgba(15, 23, 42, .08);
            color: var(--icu-main);
        }

        .icu-detail-tab-content {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
        }

        .icu-detail-summary-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .icu-detail-tab-head {
            align-items: center;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .icu-detail-search {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: flex;
            flex: 0 0 300px;
            gap: 6px;
            padding: 5px 8px;
        }

        .icu-detail-search i {
            color: #64748b;
            font-size: 17px;
        }

        .icu-detail-search .form-control {
            background: transparent;
            border: 0;
            box-shadow: none;
            min-width: 0;
            padding-left: 0;
        }

        .icu-detail-filter-note {
            color: #64748b;
            font-size: 11px;
            margin-top: 8px;
        }

        .icu-audit-tile {
            background: #fff;
            border: 1px solid var(--icu-line);
            border-radius: 8px;
            padding: 12px;
        }

        .icu-audit-label {
            color: var(--icu-muted);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .icu-audit-value {
            color: #0f172a;
            font-size: 14px;
            font-weight: 850;
            margin-top: 5px;
            word-break: break-word;
        }

        .icu-audit-note {
            color: #64748b;
            font-size: 11px;
            line-height: 1.45;
            margin-top: 3px;
        }

        .icu-chip-list {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .icu-chip {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            color: #334155;
            display: inline-flex;
            font-size: 11px;
            font-weight: 800;
            gap: 5px;
            max-width: 100%;
            padding: 5px 9px;
        }

        .icu-chip span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .icu-chip.success {
            background: #ecfdf5;
            border-color: #bbf7d0;
            color: #047857;
        }

        .icu-chip.warning {
            background: #fffbeb;
            border-color: #fde68a;
            color: #92400e;
        }

        .icu-chip.danger {
            background: #fff1f2;
            border-color: #fecdd3;
            color: #be123c;
        }

        .icu-chip.info {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .icu-detail-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 850;
            position: sticky;
            text-transform: uppercase;
            top: 0;
            z-index: 1;
        }

        .icu-detail-table td {
            font-size: 12px;
            vertical-align: top;
        }

        .icu-cell-title {
            color: #0f172a;
            font-weight: 850;
        }

        .icu-cell-muted {
            color: #64748b;
            font-size: 11px;
            line-height: 1.45;
            margin-top: 2px;
        }

        .icu-config-shell {
            display: grid;
            gap: 14px;
            grid-template-columns: minmax(280px, 360px) minmax(0, 1fr);
        }

        .icu-config-rail,
        .icu-config-main-card {
            border: 1px solid var(--icu-line);
            border-radius: 8px;
            padding: 14px;
        }

        .icu-config-rail {
            background: linear-gradient(180deg, #cffafe 0%, #f8fafc 100%);
            border-color: #67e8f9;
        }

        .icu-config-main-card {
            background: #fff;
        }

        .icu-field-grid>[class*="col-"] {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
        }

        .icu-field-grid .form-label {
            color: #475569;
            font-size: 11px;
            font-weight: 850;
            text-transform: uppercase;
        }

        .icu-config-heading {
            color: #0f172a;
            font-size: 14px;
            font-weight: 850;
            margin-bottom: 4px;
        }

        .icu-config-copy {
            color: #64748b;
            font-size: 12px;
            line-height: 1.55;
            margin-bottom: 12px;
        }

        .icu-config-pill-row {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 12px;
        }

        .icu-config-pill {
            background: #fff;
            border: 1px solid #67e8f9;
            border-radius: 999px;
            color: #0e7490;
            font-size: 11px;
            font-weight: 800;
            padding: 5px 9px;
        }

        .icu-config-section-title {
            align-items: center;
            color: #111827;
            display: flex;
            font-size: 13px;
            font-weight: 850;
            gap: 7px;
            margin-bottom: 10px;
        }

        .icu-config-section-title i {
            color: var(--icu-main);
            font-size: 18px;
        }

        .icu-recipient-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .icu-recipient-panel {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
        }

        .icu-recipient-panel .form-label {
            align-items: center;
            color: #0f172a;
            display: flex;
            font-size: 12px;
            font-weight: 850;
            gap: 6px;
        }

        .icu-recipient-help {
            color: #64748b;
            font-size: 11px;
            margin-top: 8px;
        }

        .icu-recipient-help.ok {
            color: #15803d;
            font-weight: 800;
        }

        .icu-recipient-help.warn {
            color: #b45309;
            font-weight: 800;
        }

        .icu-config-dashboard {
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        }

        .icu-bar-list {
            display: grid;
            gap: 9px;
        }

        .icu-bar-top {
            align-items: center;
            display: flex;
            font-size: 11px;
            font-weight: 850;
            justify-content: space-between;
        }

        .icu-bar-track {
            background: #e2e8f0;
            border-radius: 999px;
            height: 8px;
            overflow: hidden;
        }

        .icu-bar-fill {
            background: var(--icu-main);
            border-radius: inherit;
            height: 100%;
            min-width: 3px;
        }

        .icu-bar-fill.teal {
            background: var(--icu-teal);
        }

        .icu-bar-fill.blue {
            background: var(--icu-blue);
        }

        .icu-bar-fill.amber {
            background: var(--icu-amber);
        }

        .icu-config-live-total {
            background: var(--icu-deep);
            border-radius: 8px;
            color: #fff;
            display: grid;
            gap: 4px;
            margin-top: 12px;
            padding: 12px;
        }

        .icu-config-live-total small {
            color: #a7f3d0;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .icu-config-live-total strong {
            font-size: 18px;
        }

        .icu-loading-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            padding: 14px 16px;
        }

        .icu-summary-grid>.icu-loading-grid {
            grid-column: 1 / -1;
            padding: 0;
            width: 100%;
        }

        .icu-summary-spotlight>.icu-loading-grid,
        .icu-summary-insights>.icu-loading-grid {
            grid-column: 1 / -1;
            padding: 0;
            width: 100%;
        }

        .icu-skeleton {
            animation: icuPulse 1.1s ease-in-out infinite;
            background: linear-gradient(90deg, #f1f5f9 0%, #e2e8f0 50%, #f1f5f9 100%);
            background-size: 200% 100%;
            border-radius: 8px;
            height: 90px;
        }

        @keyframes icuPulse {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        @media (max-width: 1199.98px) {

            .icu-summary-grid,
            .icu-preview-grid,
            .icu-detail-grid,
            .icu-detail-overview,
            .icu-detail-kpi-grid,
            .icu-detail-summary-grid,
            .icu-insight-strip,
            .icu-live-strip,
            .icu-summary-spotlight,
            .icu-summary-insights,
            .icu-loading-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {

            .icu-hero,
            .icu-panel-head,
            .icu-filter-bar,
            .icu-type-tabs,
            .icu-detail-hero,
            .icu-detail-tab-head,
            .icu-summary-toolbar,
            .icu-summary-distribution-head {
                align-items: stretch;
                flex-direction: column;
            }

            .icu-hero-controls {
                flex-basis: auto;
            }

            .icu-detail-badge-row {
                justify-content: flex-start;
            }

            .icu-detail-search {
                flex-basis: auto;
            }

            .icu-summary-grid,
            .icu-preview-grid,
            .icu-detail-grid,
            .icu-detail-overview,
            .icu-detail-kpi-grid,
            .icu-detail-summary-grid,
            .icu-detail-split,
            .icu-flow,
            .icu-insight-strip,
            .icu-live-strip,
            .icu-summary-spotlight,
            .icu-summary-health-grid,
            .icu-summary-insights,
            .icu-config-dashboard,
            .icu-loading-grid {
                grid-template-columns: 1fr;
            }

            .icu-summary-main-value {
                font-size: 25px;
            }

            .icu-summary-view-group {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                width: 100%;
            }

            .icu-detail-tabs {
                grid-template-columns: 1fr;
            }

            .icu-config-shell,
            .icu-recipient-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section("content")
    <div class="icu-page">
        <nav class="page-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route("backOffice.keuangan.hitungPremi") }}">Generate Premi</a></li>
                <li class="breadcrumb-item active" aria-current="page">Generate NICU</li>
            </ol>
        </nav>

        <section class="icu-hero">
            <div class="icu-hero-main">
                <div class="icu-hero-icon"><i class="mdi mdi-baby-face-outline"></i></div>
                <div>
                    <div class="icu-eyebrow">NICU Premium Generator</div>
                    <h4 class="icu-title">Generate Premi NICU</h4>
                    <p class="icu-description">
                        Data diambil dari pasien rawat inap dengan riwayat NICU, tindakan rawat jalan/inap yang sesuai
                        mapping,
                        dan tindakan kritikal NICU khusus.
                    </p>
                    <div class="icu-hero-meta">
                        <span class="icu-metric-chip">
                            <i class="mdi mdi-hospital-building"></i>
                            Mode <strong id="heroIcuType">Umum</strong>
                        </span>
                        <span class="icu-metric-chip">
                            <i class="mdi mdi-calendar-sync-outline"></i>
                            Sumber <strong id="heroIcuSource">-</strong>
                        </span>
                        <span class="icu-metric-chip">
                            <i class="mdi mdi-database-check-outline"></i>
                            Status <strong id="heroIcuStatus">Memuat</strong>
                        </span>
                    </div>
                </div>
            </div>
            <div class="icu-hero-controls">
                <div class="icu-control-box">
                    <label>Periode Generate</label>
                    <div class="icu-period-picker">
                        <button type="button" class="btn btn-light icu-period-step" id="btnPrevIcu"
                            title="Periode sebelumnya">
                            <i class="mdi mdi-chevron-left"></i>
                        </button>
                        <input type="month" class="form-control" id="periodeIcu">
                        <button type="button" class="btn btn-light icu-period-step" id="btnNextIcu"
                            title="Periode berikutnya">
                            <i class="mdi mdi-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <div class="icu-type-tabs">
            <button type="button" class="icu-type-tab active" data-type="umum">
                <i class="mdi mdi-account-cash-outline"></i>
                <span>
                    <strong>Umum</strong>
                    <small>kd_pj selain BPJ dan -</small>
                </span>
            </button>
            <button type="button" class="icu-type-tab" data-type="bpjs">
                <i class="mdi mdi-shield-check-outline"></i>
                <span>
                    <strong>BPJS</strong>
                    <small>data sumber bulan sebelumnya</small>
                </span>
            </button>
        </div>

        <section class="icu-panel">
            <div class="icu-panel-head">
                <div>
                    <div class="icu-panel-title">Ringkasan Periode</div>
                    <div class="icu-panel-subtitle" id="icuSourceRule">Memuat aturan sumber data...</div>
                </div>
                <div class="icu-command-row">
                    <button type="button" class="btn btn-outline-secondary" id="btnConfigIcu">
                        <i class="mdi mdi-tune-variant"></i> Konfigurasi
                    </button>
                    <button type="button" class="btn btn-primary" id="btnPreviewIcu">
                        <i class="mdi mdi-play-circle-outline"></i> Preview Generate
                    </button>
                </div>
            </div>
            <div class="icu-live-strip" id="icuLiveStrip"></div>
            <div class="icu-summary-board">
                <div class="icu-summary-spotlight" id="summaryIcuSpotlight"></div>
                <div class="icu-summary-toolbar">
                    <div class="icu-summary-view-group" aria-label="Filter ringkasan">
                        <button type="button" class="icu-summary-view active" data-summary-view="all">Semua</button>
                        <button type="button" class="icu-summary-view" data-summary-view="nominal">Nominal</button>
                        <button type="button" class="icu-summary-view" data-summary-view="data">Data</button>
                        <button type="button" class="icu-summary-view" data-summary-view="distribusi">Distribusi</button>
                    </div>
                    <div class="icu-summary-note" id="summaryIcuNote">Memuat ringkasan...</div>
                </div>
                <div class="icu-summary-insights" id="summaryIcuInsights"></div>
                <div class="icu-summary-distribution" id="summaryIcuDistribution"></div>
                <div class="icu-summary-grid" id="summaryIcuGrid"></div>
            </div>
        </section>

        <section class="icu-panel">
            <div class="icu-filter-bar">
                <div>
                    <div class="icu-panel-title">Riwayat Generate NICU</div>
                    <div class="icu-panel-subtitle">Hasil generate per periode dan jenis pelayanan.</div>
                </div>
                <button type="button" class="btn btn-light" id="btnRefreshIcu" title="Refresh">
                    <i class="mdi mdi-refresh"></i>
                </button>
            </div>
            <div class="icu-table-wrap">
                <table class="table table-hover table-striped align-middle" id="tableGenerateIcu">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Periode</th>
                            <th>Jenis</th>
                            <th>Sumber</th>
                            <th>Mapping</th>
                            <th>Pasien</th>
                            <th>Tindakan</th>
                            <th>Grand Total</th>
                            <th>Perawat NICU</th>
                            <th>Medis/Orang</th>
                            <th>Bersama</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </section>
    </div>

    @include("simrs.backOffice.keuangan.hitungPremi.generateNicu.modal")
@endsection

@push("scripts")
    @include("simrs.backOffice.keuangan.hitungPremi.generateNicu.jsMain")
@endpush
