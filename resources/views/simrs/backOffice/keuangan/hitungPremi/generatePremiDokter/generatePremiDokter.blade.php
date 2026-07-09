@extends("template.partials.app")

@push("style")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.select2")
    <style>
        .pd-page {
            --pd-primary: #0f766e;
            --pd-secondary: #1d4ed8;
            --pd-accent: #b45309;
            --pd-ink: #111827;
            --pd-muted: #64748b;
            --pd-line: #e2e8f0;
            color: #172033;
        }

        .pd-page .breadcrumb {
            background: transparent;
            font-size: 13px;
            margin-bottom: 0;
            padding: 0;
        }

        .pd-hero {
            align-items: stretch;
            background: linear-gradient(135deg, #0f172a 0%, #0f766e 58%, #b45309 100%);
            border-radius: 8px;
            color: #fff;
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(0, 1fr) 340px;
            margin-bottom: 14px;
            padding: 22px;
        }

        .pd-hero-title {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 0;
            margin-bottom: 6px;
        }

        .pd-hero-subtitle {
            color: rgba(255, 255, 255, .78);
            font-size: 13px;
            line-height: 1.55;
            margin: 0;
            max-width: 820px;
        }

        .pd-hero-panel {
            background: rgba(255, 255, 255, .13);
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 8px;
            padding: 14px;
        }

        .pd-hero-panel label {
            color: #f3e8ff;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .pd-toolbar,
        .pd-panel,
        .pd-metric,
        .pd-type-card {
            background: #fff;
            border: 1px solid var(--pd-line);
            border-radius: 8px;
        }

        .pd-toolbar {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 14px;
            padding: 12px;
        }

        .pd-type-switch {
            background: #f8fafc;
            border: 1px solid var(--pd-line);
            border-radius: 8px;
            display: inline-flex;
            padding: 4px;
        }

        .pd-type-btn {
            border: 0;
            border-radius: 6px;
            color: var(--pd-muted);
            font-size: 12px;
            font-weight: 800;
            min-width: 82px;
            padding: 8px 12px;
        }

        .pd-type-btn.active {
            background: var(--pd-primary);
            color: #fff;
        }

        .pd-action-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .pd-manual-input-panel {
            align-items: center;
            background: #fff;
            border: 1px solid var(--pd-line);
            border-radius: 8px;
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(260px, 420px) minmax(0, 1fr);
            margin-bottom: 14px;
            padding: 12px;
        }

        .pd-manual-input-panel label {
            color: var(--pd-muted);
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .pd-manual-input-note {
            color: var(--pd-muted);
            font-size: 12px;
            line-height: 1.45;
        }

        .pd-manual-volume-panel {
            align-items: start;
        }

        .pd-manual-volume-rows {
            display: grid;
            gap: 8px;
            grid-column: 1 / -1;
        }

        .pd-manual-volume-row {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: grid;
            gap: 10px;
            grid-template-columns: 34px minmax(0, 1fr) 190px 150px;
            padding: 9px;
        }

        .pd-type-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(9, minmax(0, 1fr));
            margin-bottom: 14px;
        }

        .pd-type-card {
            min-height: 74px;
            padding: 11px;
        }

        .pd-type-card.active {
            border-color: rgba(15, 118, 110, .55);
            box-shadow: 0 10px 24px rgba(15, 118, 110, .12);
        }

        .pd-type-label {
            color: var(--pd-ink);
            font-size: 12px;
            font-weight: 800;
            line-height: 1.3;
        }

        .pd-type-status {
            color: var(--pd-muted);
            font-size: 10px;
            font-weight: 800;
            margin-top: 8px;
            text-transform: uppercase;
        }

        .pd-type-card.active .pd-type-status {
            color: var(--pd-primary);
        }

        .pd-summary-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            margin-bottom: 14px;
        }

        .pd-metric {
            padding: 13px;
        }

        .pd-metric-label {
            color: var(--pd-muted);
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .pd-metric-value {
            color: var(--pd-ink);
            font-size: 18px;
            font-weight: 900;
            line-height: 1.2;
        }

        .pd-metric-foot {
            color: var(--pd-muted);
            font-size: 11px;
            margin-top: 5px;
        }

        .pd-panel {
            margin-bottom: 14px;
            overflow: hidden;
        }

        .pd-panel-head {
            align-items: center;
            border-bottom: 1px solid #eef2f7;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: space-between;
            padding: 13px 15px;
        }

        .pd-panel-title {
            color: var(--pd-ink);
            font-size: 15px;
            font-weight: 800;
            margin: 0;
        }

        .pd-panel-note {
            color: var(--pd-muted);
            font-size: 12px;
        }

        .pd-panel-body {
            padding: 15px;
        }

        .pd-step-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            margin-bottom: 14px;
        }

        .pd-step {
            border: 1px solid var(--pd-line);
            border-radius: 8px;
            padding: 11px;
        }

        .pd-step.success {
            background: #ecfdf5;
            border-color: #bbf7d0;
        }

        .pd-step.warning {
            background: #fffbeb;
            border-color: #fde68a;
        }

        .pd-step.danger {
            background: #fef2f2;
            border-color: #fecaca;
        }

        .pd-step-label {
            color: var(--pd-muted);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .pd-step-value {
            color: var(--pd-ink);
            font-size: 14px;
            font-weight: 800;
            margin-top: 4px;
        }

        .pd-actions {
            display: inline-flex;
            gap: 6px;
        }

        .pd-config-modal-dialog {
            max-width: min(1360px, calc(100vw - 28px));
        }

        .pd-config-modal {
            border: 0;
            border-radius: 8px;
            overflow: hidden;
        }

        .pd-config-modal .modal-header {
            align-items: flex-start;
            background: linear-gradient(135deg, #0f172a 0%, #0f766e 62%, #b45309 100%);
            border-bottom: 0;
            color: #fff;
            padding: 18px 20px;
        }

        .pd-config-modal .btn-close {
            filter: invert(1) grayscale(100%);
            opacity: .88;
        }

        .pd-config-modal .modal-title {
            font-size: 19px;
            font-weight: 900;
            letter-spacing: 0;
            margin: 0;
        }

        .pd-config-kicker {
            color: #99f6e4;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .08em;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .pd-config-header-meta {
            color: rgba(255, 255, 255, .76);
            font-size: 12px;
            margin-top: 4px;
        }

        .pd-config-modal .modal-body {
            background: #f8fafc;
            padding: 0;
        }

        .pd-config-modal .modal-footer {
            background: #fff;
            border-top: 1px solid #e2e8f0;
            padding: 12px 16px;
        }

        .pd-config-toolbar {
            background: rgba(255, 255, 255, .96);
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 16px;
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .pd-config-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .pd-config-tab {
            align-items: center;
            background: #fff;
            border: 1px solid #dbe3ed;
            border-radius: 8px;
            color: #475569;
            display: inline-flex;
            font-size: 12px;
            font-weight: 900;
            gap: 6px;
            min-height: 38px;
            padding: 8px 12px;
            transition: background .18s ease, border-color .18s ease, color .18s ease, box-shadow .18s ease;
        }

        .pd-config-tab.active {
            background: var(--pd-primary);
            border-color: var(--pd-primary);
            box-shadow: 0 8px 18px rgba(15, 118, 110, .18);
            color: #fff;
        }

        .pd-config-body {
            padding: 14px;
        }

        .pd-config-summary-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            margin: 14px 14px 10px;
        }

        .pd-config-summary-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            min-width: 0;
            padding: 12px;
        }

        .pd-config-summary-label {
            color: var(--pd-muted);
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .pd-config-summary-value {
            color: var(--pd-ink);
            font-size: 14px;
            font-weight: 900;
            line-height: 1.3;
            margin-top: 4px;
            overflow-wrap: anywhere;
        }

        .pd-config-scope-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: 1fr 1.15fr 1fr;
            margin: 0 14px 14px;
        }

        .pd-config-scope-card {
            align-items: center;
            background: #fff;
            border: 1px solid #dbe3ed;
            border-radius: 8px;
            display: grid;
            gap: 10px;
            grid-template-columns: 36px minmax(0, 1fr);
            min-width: 0;
            padding: 11px;
        }

        .pd-config-scope-card-control {
            border-color: rgba(15, 118, 110, .32);
            box-shadow: 0 10px 22px rgba(15, 118, 110, .07);
        }

        .pd-scope-icon {
            align-items: center;
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            color: var(--pd-primary);
            display: inline-flex;
            height: 36px;
            justify-content: center;
            width: 36px;
        }

        .pd-config-scope-value {
            color: var(--pd-ink);
            font-size: 13px;
            font-weight: 900;
            line-height: 1.25;
            margin-top: 4px;
            overflow-wrap: anywhere;
        }

        .pd-config-scope-card label {
            display: block;
            margin-bottom: 5px;
        }

        .pd-config-pane {
            display: none;
        }

        .pd-config-pane.active {
            display: block;
        }

        .pd-config-section {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 14px;
            padding: 14px;
        }

        .pd-config-section-head {
            align-items: flex-start;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .pd-config-title {
            align-items: center;
            color: var(--pd-ink);
            display: flex;
            font-size: 14px;
            font-weight: 800;
            gap: 6px;
            margin: 0;
        }

        .pd-config-subtitle {
            color: var(--pd-muted);
            font-size: 11px;
            margin-top: 3px;
        }

        .pd-config-badge {
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            border-radius: 999px;
            color: #047857;
            flex: 0 0 auto;
            font-size: 11px;
            font-weight: 900;
            padding: 5px 8px;
        }

        .pd-config-field-grid,
        .pd-config-two-column {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .pd-config-two-column {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .pd-config-three-column {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .pd-config-field label,
        .pd-config-mini-label,
        .pd-special-panel label {
            display: block;
            color: var(--pd-muted);
            font-size: 11px;
            font-weight: 900;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .pd-config-help-text {
            color: var(--pd-muted);
            font-size: 11px;
            line-height: 1.4;
        }

        .pd-config-field .form-check-label {
            color: var(--pd-ink);
            display: inline;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 0;
            text-transform: none;
        }

        .pd-config-modal .form-control,
        .pd-config-modal .form-select,
        .pd-config-modal .input-group-text {
            border-color: #dbe3ed;
            font-size: 12px;
        }

        .pd-config-modal .form-control,
        .pd-config-modal .form-select {
            min-height: 38px;
        }

        .pd-empty-state {
            align-items: center;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            color: var(--pd-muted);
            display: flex;
            font-size: 12px;
            font-weight: 700;
            gap: 8px;
            margin-top: 8px;
            padding: 12px;
        }

        .pd-empty-state i {
            color: var(--pd-primary);
            font-size: 18px;
        }

        .pd-doctor-row {
            align-items: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: grid;
            gap: 10px;
            grid-template-columns: 34px minmax(0, 1fr) 150px;
            margin-top: 8px;
            padding: 9px;
            transition: border-color .18s ease, box-shadow .18s ease;
        }

        .pd-doctor-row:hover,
        .pd-rj-mapping-row:hover {
            border-color: rgba(15, 118, 110, .32);
            box-shadow: 0 10px 22px rgba(15, 118, 110, .08);
        }

        .pd-row-icon {
            align-items: center;
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            color: var(--pd-primary);
            display: inline-flex;
            height: 34px;
            justify-content: center;
            width: 34px;
        }

        .pd-row-chip {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            color: #475569;
            font-size: 11px;
            font-weight: 900;
            justify-self: end;
            padding: 6px 9px;
            text-align: center;
            white-space: nowrap;
        }

        .pd-rj-mapping-row {
            align-items: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(0, 1fr) 140px 170px 130px;
            margin-top: 8px;
            padding: 9px;
            transition: border-color .18s ease, box-shadow .18s ease;
        }

        .pd-rj-mapping-main {
            align-items: center;
            display: grid;
            gap: 9px;
            grid-template-columns: 34px minmax(0, 1fr);
            min-width: 0;
        }

        .pd-rj-preview {
            color: var(--pd-primary);
            font-size: 11px;
            font-weight: 900;
            text-align: right;
            white-space: nowrap;
        }

        .pd-config-rj-split {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .pd-config-rj-split-card {
            background: #f8fafc;
            border: 1px solid #dbe3ed;
            border-radius: 8px;
            padding: 11px;
        }

        .pd-config-rj-split-card span {
            color: var(--pd-muted);
            display: block;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .pd-config-rj-split-card strong {
            color: var(--pd-ink);
            display: block;
            font-size: 13px;
            line-height: 1.3;
            margin-top: 4px;
            overflow-wrap: anywhere;
        }

        .pd-rj-special-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .pd-special-panel {
            min-width: 0;
        }

        .pd-doctor-name {
            color: var(--pd-ink);
            font-size: 12px;
            font-weight: 800;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pd-doctor-meta {
            color: var(--pd-muted);
            font-size: 11px;
        }

        .pd-detail-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .pd-detail-layout {
            display: grid;
            gap: 14px;
            grid-template-columns: 380px minmax(0, 1fr);
        }

        .pd-detail-layout .pd-detail-grid {
            grid-template-columns: 1fr;
            max-height: 690px;
            overflow-y: auto;
            padding-right: 3px;
        }

        .pd-detail-modal-dialog {
            max-width: min(1480px, calc(100vw - 28px));
        }

        .pd-detail-modal {
            border: 0;
            border-radius: 8px;
            overflow: hidden;
        }

        .pd-detail-modal .modal-header {
            align-items: flex-start;
            background:
                linear-gradient(135deg, #0f172a 0%, #0f766e 65%, #b45309 100%);
            border-bottom: 0;
            color: #fff;
            padding: 18px 20px;
        }

        .pd-detail-modal .btn-close {
            filter: invert(1) grayscale(100%);
            opacity: .85;
        }

        .pd-detail-kicker {
            color: #99f6e4;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .08em;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .pd-detail-modal .modal-title {
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 0;
        }

        .pd-detail-modal-meta {
            color: rgba(255, 255, 255, .76);
            font-size: 12px;
            margin-top: 3px;
        }

        .pd-detail-modal .modal-body {
            background: #f8fafc;
            padding: 14px;
        }

        .pd-detail-summary-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            margin-bottom: 14px;
        }

        .pd-detail-metric {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
        }

        .pd-detail-metric .pd-metric-label {
            margin-bottom: 6px;
        }

        .pd-detail-metric .pd-metric-value {
            font-size: 17px;
        }

        .pd-detail-card {
            background: #fff;
            border: 1px solid var(--pd-line);
            border-radius: 8px;
            padding: 12px;
            transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
        }

        .pd-detail-card.active {
            background: #ecfdf5;
            border-color: rgba(15, 118, 110, .45);
            box-shadow: 0 12px 24px rgba(15, 118, 110, .1);
        }

        .pd-detail-card-title {
            color: var(--pd-ink);
            font-size: 13px;
            font-weight: 800;
        }

        .pd-detail-card-meta {
            color: var(--pd-muted);
            font-size: 11px;
            margin-top: 3px;
        }

        .pd-detail-card-value {
            color: var(--pd-primary);
            font-size: 17px;
            font-weight: 900;
            margin-top: 8px;
        }

        .pd-detail-card-kpis {
            display: grid;
            gap: 7px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 10px;
        }

        .pd-detail-card-kpi {
            background: rgba(248, 250, 252, .9);
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px;
        }

        .pd-detail-card-kpi span {
            color: var(--pd-muted);
            display: block;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .pd-detail-card-kpi strong {
            color: var(--pd-ink);
            display: block;
            font-size: 12px;
            line-height: 1.25;
            margin-top: 2px;
            overflow-wrap: anywhere;
        }

        .pd-detail-side-head {
            align-items: center;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .pd-detail-side-title {
            color: var(--pd-ink);
            font-size: 13px;
            font-weight: 900;
        }

        .pd-detail-side-count {
            background: #e0f2fe;
            border: 1px solid #bae6fd;
            border-radius: 999px;
            color: #0369a1;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 8px;
        }

        .pd-rawat-panel {
            background: #fff;
            border: 1px solid var(--pd-line);
            border-radius: 8px;
            min-width: 0;
            overflow: hidden;
        }

        .pd-rawat-head {
            align-items: center;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            padding: 12px;
        }

        .pd-rawat-filter-panel {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(190px, 1.35fr) repeat(3, minmax(140px, 1fr)) repeat(2, minmax(130px, .85fr)) auto;
            padding: 12px;
        }

        .pd-rawat-filter-panel .form-control,
        .pd-rawat-filter-panel .form-select {
            border-color: #dbe3ed;
            font-size: 12px;
            height: 36px;
        }

        .pd-rawat-filter-label {
            color: #64748b;
            display: block;
            font-size: 10px;
            font-weight: 800;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .pd-rawat-filter-reset {
            align-self: end;
            height: 36px;
            width: 40px;
        }

        .pd-rawat-insights {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            padding: 10px 12px;
        }

        .pd-rawat-insight {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            min-width: 0;
            padding: 9px;
        }

        .pd-rawat-insight-label {
            color: var(--pd-muted);
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .pd-rawat-insight-value {
            color: var(--pd-ink);
            font-size: 13px;
            font-weight: 900;
            line-height: 1.25;
            margin-top: 3px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pd-rawat-title {
            color: var(--pd-ink);
            font-size: 13px;
            font-weight: 800;
        }

        .pd-rawat-note {
            color: var(--pd-muted);
            font-size: 11px;
        }

        .pd-rawat-table {
            font-size: 12px;
            margin-bottom: 0;
        }

        .pd-rawat-table-wrap {
            max-height: 520px;
            overflow: auto;
        }

        .pd-rawat-table th {
            background: #fff;
            color: #475569;
            font-size: 11px;
            position: sticky;
            top: 0;
            text-transform: uppercase;
            z-index: 1;
        }

        .pd-filter-pill-row {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            padding: 10px 12px 0;
        }

        .pd-filter-pill {
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            border-radius: 999px;
            color: #3730a3;
            font-size: 11px;
            font-weight: 800;
            padding: 5px 8px;
        }

        .pd-config-modal .select2-container {
            width: 100% !important;
        }

        .pd-config-modal .select2-container--default .select2-selection--multiple,
        .pd-config-modal .select2-container--default .select2-selection--single {
            border-color: var(--pd-line);
            min-height: 38px;
        }

        @media (max-width: 1199.98px) {
            .pd-type-grid,
            .pd-summary-grid,
            .pd-detail-summary-grid,
            .pd-config-summary-grid,
            .pd-config-scope-grid,
            .pd-config-field-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .pd-detail-layout,
            .pd-manual-input-panel,
            .pd-manual-volume-row,
            .pd-rj-mapping-row,
            .pd-rj-special-grid,
            .pd-config-two-column,
            .pd-config-three-column,
            .pd-rawat-filter-panel {
                grid-template-columns: 1fr;
            }

            .pd-rawat-filter-reset {
                width: 100%;
            }
        }

        @media (max-width: 767.98px) {
            .pd-hero {
                grid-template-columns: 1fr;
            }

            .pd-type-grid,
            .pd-summary-grid,
            .pd-detail-summary-grid,
            .pd-config-summary-grid,
            .pd-config-scope-grid,
            .pd-config-field-grid,
            .pd-config-three-column,
            .pd-config-rj-split,
            .pd-step-grid,
            .pd-detail-grid,
            .pd-detail-layout,
            .pd-rawat-insights {
                grid-template-columns: 1fr;
            }

            .pd-config-toolbar {
                position: static;
            }

            .pd-config-tab {
                flex: 1 1 calc(50% - 8px);
                justify-content: center;
            }

            .pd-doctor-row,
            .pd-manual-volume-row {
                grid-template-columns: 34px minmax(0, 1fr);
            }

            .pd-doctor-row .input-group,
            .pd-manual-volume-row .input-group,
            .pd-doctor-row .pd-row-chip,
            .pd-manual-volume-row .pd-row-chip,
            .pd-doctor-row > .text-end {
                grid-column: 1 / -1;
                justify-self: stretch;
                text-align: left !important;
            }

            .pd-toolbar {
                align-items: stretch;
                flex-direction: column;
            }
        }
    </style>
@endpush

@section("content")
    <div class="pd-page">
        <nav class="page-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route("backOffice.keuangan.hitungPremi") }}">Generate Premi</a></li>
                <li class="breadcrumb-item active" aria-current="page">Generate Premi Dokter</li>
            </ol>
        </nav>

        <section class="pd-hero">
            <div>
                <h4 class="pd-hero-title">Generate Premi Dokter</h4>
                <p class="pd-hero-subtitle">
                    Modul premi dokter dengan konfigurasi penerima, Jasa Visite, Jasa Rawat Jalan, Jasa Poli, Jasa ECG, Konsul WA UMUM/BPJS, Kebersamaan, dan input manual Jasa Operasi, IGD, serta Kehadiran.
                </p>
            </div>
            <div class="pd-hero-panel">
                <label for="periodePremiDokter">Periode Generate</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="mdi mdi-calendar-month-outline"></i></span>
                    <input type="month" class="form-control" id="periodePremiDokter"
                        value="{{ request("periode", now()->format("Y-m")) }}">
                </div>
            </div>
        </section>

        <div class="pd-toolbar">
            <div class="pd-type-switch" id="jenisPelayananSwitch">
                <button type="button" class="pd-type-btn active" data-type="umum">UMUM</button>
                <button type="button" class="pd-type-btn" data-type="bpjs">BPJS</button>
            </div>
            <div class="pd-action-group">
                <button type="button" class="btn btn-outline-secondary" id="btnConfigPremiDokter">
                    <i class="mdi mdi-tune-variant"></i> Konfigurasi
                </button>
                <button type="button" class="btn btn-primary" id="btnGeneratePremiDokter">
                    <i class="mdi mdi-play-circle-outline"></i> Generate UMUM
                </button>
            </div>
        </div>

        <div class="pd-manual-input-panel d-none" id="operasiNominalPanel">
            <div>
                <label for="nominalOperasiPremiDokter">Nominal Jasa Operasi</label>
                <div class="input-group">
                    <span class="input-group-text bg-white">Rp</span>
                    <input type="number" class="form-control text-end" id="nominalOperasiPremiDokter"
                        min="0" step="1" value="0" inputmode="numeric">
                </div>
            </div>
            <div class="pd-manual-input-note">
                Grand total Jasa Operasi mengikuti nominal ini. Premi per dokter dihitung dari nominal dikali persentase dokter pada konfigurasi.
            </div>
        </div>

        <div class="pd-manual-input-panel pd-manual-volume-panel d-none" id="manualVolumePanel">
            <div>
                <label for="manualVolumeDoctorSelect" id="manualVolumeDoctorLabel">Dokter Jasa IGD</label>
                <select id="manualVolumeDoctorSelect" class="form-select" multiple></select>
            </div>
            <div class="pd-manual-input-note" id="manualVolumeNote">
                Total Jasa IGD dihitung dari jumlah pasien per dokter dikali nominal pada konfigurasi.
            </div>
            <div class="pd-manual-volume-rows" id="manualVolumeRows"></div>
        </div>

        <div class="pd-type-grid" id="premiDokterTypeGrid">
            <div class="pd-type-card" data-premi-type="kebersamaan"><div class="pd-type-label">Kebersamaan</div><div class="pd-type-status">Aktif</div></div>
            <div class="pd-type-card" data-premi-type="jasa_operasi"><div class="pd-type-label">Jasa Operasi</div><div class="pd-type-status">Aktif</div></div>
            <div class="pd-type-card" data-premi-type="jasa_rawat_jalan"><div class="pd-type-label">Jasa Rawat Jalan</div><div class="pd-type-status">Aktif</div></div>
            <div class="pd-type-card active" data-premi-type="visite"><div class="pd-type-label">Jasa Visite</div><div class="pd-type-status">Aktif</div></div>
            <div class="pd-type-card" data-premi-type="jasa_poli"><div class="pd-type-label">Jasa Poli</div><div class="pd-type-status">Aktif</div></div>
            <div class="pd-type-card" data-premi-type="jasa_igd"><div class="pd-type-label">Jasa IGD</div><div class="pd-type-status">Aktif</div></div>
            <div class="pd-type-card" data-premi-type="jasa_ecg"><div class="pd-type-label">Jasa ECG</div><div class="pd-type-status">Aktif</div></div>
            <div class="pd-type-card" data-premi-type="konsul_wa"><div class="pd-type-label">Konsul WA</div><div class="pd-type-status">Aktif</div></div>
            <div class="pd-type-card" data-premi-type="kehadiran"><div class="pd-type-label">Kehadiran</div><div class="pd-type-status">Aktif</div></div>
        </div>

        <div class="pd-summary-grid">
            <div class="pd-metric">
                <div class="pd-metric-label">Total Premi</div>
                <div class="pd-metric-value" id="summaryTotalPremi">Rp 0</div>
                <div class="pd-metric-foot" id="summaryFormula">-</div>
            </div>
            <div class="pd-metric">
                <div class="pd-metric-label">Grand Total</div>
                <div class="pd-metric-value" id="summaryGrandTotal">Rp 0</div>
                <div class="pd-metric-foot">Dasar hitung sebelum persen</div>
            </div>
            <div class="pd-metric">
                <div class="pd-metric-label">Transaksi</div>
                <div class="pd-metric-value" id="summaryTransaksi">0</div>
                <div class="pd-metric-foot" id="summaryPasien">0 pasien</div>
            </div>
            <div class="pd-metric">
                <div class="pd-metric-label">Dokter</div>
                <div class="pd-metric-value" id="summaryDokter">0</div>
                <div class="pd-metric-foot" id="summaryDokterFoot">Penerima visite</div>
            </div>
            <div class="pd-metric">
                <div class="pd-metric-label">Mapping</div>
                <div class="pd-metric-value" id="summaryMapping">0</div>
                <div class="pd-metric-foot" id="summaryMappingFoot">Tindakan visite</div>
            </div>
            <div class="pd-metric">
                <div class="pd-metric-label">Tidak Masuk</div>
                <div class="pd-metric-value" id="summarySkipped">0</div>
                <div class="pd-metric-foot" id="summarySkippedFoot">Baris dokter belum dikonfigurasi</div>
            </div>
        </div>

        <div id="readinessSteps" class="pd-step-grid"></div>

        <section class="pd-panel">
            <div class="pd-panel-head">
                <div>
                    <h5 class="pd-panel-title" id="previewPanelTitle">Preview Jasa Visite</h5>
                    <div class="pd-panel-note" id="summaryMessage">Memuat preview...</div>
                </div>
            </div>
            <div class="pd-panel-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Dokter</th>
                                <th>Kategori</th>
                                <th class="text-end">Data</th>
                                <th class="text-end">Grand Total</th>
                                <th class="text-end">Persen</th>
                                <th class="text-end">Premi</th>
                            </tr>
                        </thead>
                        <tbody id="previewPremiDokterRows">
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Memuat preview...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="pd-panel">
            <div class="pd-panel-head">
                <div>
                    <h5 class="pd-panel-title" id="historyPanelTitle">History Generate Premi Dokter</h5>
                    <div class="pd-panel-note">Hasil tersimpan per periode, tipe premi, dan jenis pelayanan.</div>
                </div>
            </div>
            <div class="pd-panel-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100" id="tablePremiDokter">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Periode</th>
                                <th>Jenis</th>
                                <th>Dokter</th>
                                <th>Transaksi</th>
                                <th>Total Premi</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </section>
    </div>

    @include("simrs.backOffice.keuangan.hitungPremi.generatePremiDokter.modal")
@endsection

@push("scripts")
    @include("simrs.backOffice.keuangan.hitungPremi.generatePremiDokter.jsMain")
@endpush
