@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.select2")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")

    <style>
        .vk-page {
            --vk-rose: #be123c;
            --vk-teal: #0f766e;
            --vk-blue: #2563eb;
            --vk-muted: #64748b;
            --vk-line: #e2e8f0;
            color: #172033;
        }

        .vk-page .breadcrumb {
            background: transparent;
            font-size: 13px;
            margin-bottom: 0;
            padding: 0;
        }

        .vk-hero {
            align-items: stretch;
            background: linear-gradient(135deg, #be123c 0%, #0e7490 58%, #2563eb 100%);
            border-radius: 16px;
            box-shadow: 0 16px 34px rgba(190, 18, 60, .18);
            color: #fff;
            display: flex;
            gap: 22px;
            justify-content: space-between;
            margin-top: 15px;
            overflow: hidden;
            padding: 22px;
            position: relative;
        }

        .vk-hero-main {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            min-width: 0;
        }

        .vk-hero-icon {
            align-items: center;
            background: #fff;
            border-radius: 13px;
            box-shadow: 0 12px 25px rgba(15, 23, 42, .18);
            color: var(--vk-rose);
            display: flex;
            flex: 0 0 auto;
            font-size: 30px;
            height: 58px;
            justify-content: center;
            width: 58px;
        }

        .vk-eyebrow {
            color: #ffe4e6;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .vk-title {
            font-size: 24px;
            font-weight: 800;
            margin: 3px 0 5px;
        }

        .vk-description {
            color: rgba(255, 255, 255, .78);
            font-size: 13px;
            margin: 0;
            max-width: 720px;
        }

        .vk-hero-controls {
            display: grid;
            flex: 0 0 320px;
            gap: 10px;
        }

        .vk-control-box {
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 12px;
            padding: 12px;
        }

        .vk-control-box label {
            color: #ffe4e6;
            display: block;
            font-size: 10px;
            font-weight: 800;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .vk-control-box .form-control {
            border: 0;
            height: 39px;
        }

        .vk-period-picker {
            align-items: center;
            display: grid;
            gap: 7px;
            grid-template-columns: 38px minmax(0, 1fr) 38px;
        }

        .vk-period-picker .form-control {
            font-weight: 800;
            text-align: center;
        }

        .vk-period-step,
        .vk-period-current {
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

        .vk-period-step:hover,
        .vk-period-current:hover {
            background: #fff;
            color: var(--vk-rose);
        }

        .vk-period-current {
            font-size: 11px;
            font-weight: 800;
            gap: 5px;
            margin-top: 8px;
            padding: 0 10px;
            width: 100%;
        }

        .vk-type-tabs {
            background: #fff;
            border: 1px solid var(--vk-line);
            border-radius: 14px;
            display: flex;
            gap: 8px;
            margin: 16px 0;
            padding: 8px;
        }

        .vk-type-tab {
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

        .vk-type-tab i {
            font-size: 23px;
        }

        .vk-type-tab strong,
        .vk-type-tab small {
            display: block;
        }

        .vk-type-tab small {
            font-size: 11px;
        }

        .vk-type-tab.active {
            background: #fff1f2;
            box-shadow: inset 0 0 0 1px #fecdd3;
            color: var(--vk-rose);
        }

        .vk-panel {
            background: #fff;
            border: 1px solid var(--vk-line);
            border-radius: 15px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
            margin-bottom: 16px;
            overflow: hidden;
        }

        .vk-panel-head,
        .vk-filter-bar {
            align-items: center;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            padding: 15px 17px;
        }

        .vk-panel-head {
            border-bottom: 1px solid #eef2f7;
        }

        .vk-panel-title {
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
        }

        .vk-panel-subtitle {
            color: var(--vk-muted);
            font-size: 11px;
        }

        .vk-panel-actions {
            align-items: center;
            display: flex;
            gap: 8px;
        }

        .vk-refresh-btn,
        .vk-copy-btn,
        .vk-lock-all-btn,
        .vk-config-btn,
        .vk-generate-btn {
            align-items: center;
            border-radius: 9px;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 6px;
            padding: 10px 12px;
        }

        .vk-refresh-btn,
        .vk-copy-btn,
        .vk-lock-all-btn,
        .vk-config-btn {
            background: #fff;
            border: 1px solid var(--vk-line);
            color: #475569;
        }

        .vk-copy-btn {
            border-color: #fecdd3;
            color: var(--vk-rose);
        }

        .vk-copy-btn:hover {
            background: #fff1f2;
            color: #9f1239;
        }

        .vk-lock-all-btn {
            border-color: #fde68a;
            color: #92400e;
        }

        .vk-lock-all-btn:hover {
            background: #fffbeb;
            color: #78350f;
        }

        .vk-config-btn {
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .vk-config-btn:hover {
            background: #eff6ff;
            color: #1e40af;
        }

        .vk-generate-btn {
            background: var(--vk-rose);
            border: 0;
            color: #fff;
            padding: 10px 14px;
        }

        .vk-summary-grid {
            display: grid;
            gap: 11px;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            padding: 15px 17px;
        }

        .vk-summary-card {
            background: linear-gradient(180deg, #fff, #f8fafc);
            border: 1px solid var(--vk-line);
            border-radius: 11px;
            padding: 13px;
        }

        .vk-summary-card.total {
            background: linear-gradient(135deg, #be123c, #2563eb);
            border: 0;
            color: #fff;
        }

        .vk-summary-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .vk-summary-card.total .vk-summary-label {
            color: #ffe4e6;
        }

        .vk-summary-value {
            color: #0f172a;
            font-size: 19px;
            font-weight: 900;
            margin-top: 4px;
        }

        .vk-summary-card.total .vk-summary-value {
            color: #fff;
        }

        .vk-ploting-summary {
            border-top: 1px solid #eef2f7;
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            padding: 15px 17px;
        }

        .vk-ploting-item {
            background: #f8fafc;
            border: 1px solid var(--vk-line);
            border-radius: 10px;
            padding: 11px;
        }

        .vk-ploting-name {
            color: #0f172a;
            font-size: 12px;
            font-weight: 800;
        }

        .vk-ploting-meta {
            color: var(--vk-muted);
            font-size: 11px;
            margin-top: 2px;
        }

        .vk-ploting-total {
            color: var(--vk-rose);
            font-size: 17px;
            font-weight: 900;
            margin-top: 7px;
        }

        .vk-ploting-empty {
            color: var(--vk-muted);
            font-size: 12px;
            grid-column: 1 / -1;
            padding: 4px 0;
        }

        .vk-filter-bar {
            border-bottom: 1px solid #eef2f7;
        }

        .vk-search {
            max-width: 340px;
            width: 100%;
        }

        .vk-search .form-control,
        .vk-search .input-group-text {
            border-color: var(--vk-line);
            height: 38px;
        }

        .vk-table-wrap {
            padding: 8px 17px 17px;
        }

        .dataTables_wrapper .dataTables_filter {
            display: none;
        }

        .vk-badge,
        .vk-lock,
        .vk-source-badge {
            border-radius: 999px;
            display: inline-flex;
            font-size: 10px;
            font-weight: 800;
            gap: 4px;
            padding: 4px 8px;
        }

        .vk-badge.umum {
            background: #dcfce7;
            color: #166534;
        }

        .vk-badge.bpjs {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .vk-source-badge {
            background: #f1f5f9;
            color: #475569;
        }

        .vk-lock.locked {
            background: #fef3c7;
            color: #92400e;
        }

        .vk-lock.open {
            background: #f1f5f9;
            color: #64748b;
        }

        .vk-actions {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .vk-actions .btn {
            align-items: center;
            display: inline-flex;
            height: 31px;
            justify-content: center;
            padding: 0;
            width: 31px;
        }

        .vk-detail-modal .modal-dialog {
            max-width: 1120px;
            width: calc(100% - 1rem);
        }

        .vk-detail-modal .modal-content {
            max-height: calc(100vh - 1rem);
            max-height: calc(100dvh - 1rem);
        }

        .vk-detail-modal .modal-dialog-scrollable .modal-content {
            overflow: hidden;
        }

        .vk-detail-shell {
            background: #f8fafc;
            color: #172033;
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 1rem);
            max-height: calc(100dvh - 1rem);
            min-height: 0;
        }

        .vk-detail-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 55%, #be123c 100%);
            color: #fff;
            flex: 0 0 auto;
            padding: 22px;
        }

        .vk-detail-hero-top {
            align-items: flex-start;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            min-width: 0;
        }

        .vk-detail-hero-top > .d-flex:first-child {
            flex: 1 1 auto;
            min-width: 0;
        }

        .vk-detail-hero-top > .d-flex:last-child {
            flex: 0 0 auto;
        }

        .vk-detail-icon {
            align-items: center;
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 12px;
            display: inline-flex;
            font-size: 28px;
            flex: 0 0 auto;
            height: 52px;
            justify-content: center;
            width: 52px;
        }

        .vk-detail-title {
            font-size: 20px;
            font-weight: 900;
            margin: 2px 0 5px;
            overflow-wrap: anywhere;
        }

        .vk-detail-meta {
            color: rgba(255, 255, 255, .75);
            font-size: 12px;
            overflow-wrap: anywhere;
        }

        .vk-detail-status {
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 999px;
            color: #fff;
            display: inline-flex;
            font-size: 11px;
            font-weight: 800;
            gap: 6px;
            padding: 8px 11px;
            white-space: nowrap;
        }

        .vk-detail-kpis {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-top: 18px;
        }

        .vk-detail-kpi {
            background: rgba(255, 255, 255, .13);
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 11px;
            min-width: 0;
            padding: 12px;
        }

        .vk-detail-kpi span {
            color: rgba(255, 255, 255, .72);
            display: block;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .vk-detail-kpi strong {
            color: #fff;
            display: block;
            font-size: 18px;
            font-weight: 900;
            margin-top: 4px;
            overflow-wrap: anywhere;
        }

        .vk-detail-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-x: hidden;
            overflow-y: auto;
            padding: 16px;
        }

        .vk-detail-tabs {
            background: #fff;
            border: 1px solid var(--vk-line);
            border-radius: 12px;
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            padding: 7px;
        }

        .vk-detail-tab {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 9px;
            color: #64748b;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 7px;
            justify-content: center;
            min-width: 0;
            padding: 10px;
        }

        .vk-detail-tab i {
            flex: 0 0 auto;
        }

        .vk-detail-tab.active {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .vk-detail-view {
            display: none;
            margin-top: 14px;
        }

        .vk-detail-view.active {
            display: block;
        }

        .vk-detail-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(0, 1.1fr) minmax(320px, .9fr);
        }

        .vk-detail-panel {
            background: #fff;
            border: 1px solid var(--vk-line);
            border-radius: 12px;
            min-width: 0;
            padding: 14px;
        }

        .vk-detail-panel-title {
            align-items: center;
            color: #0f172a;
            display: flex;
            font-size: 13px;
            font-weight: 900;
            gap: 7px;
            margin-bottom: 10px;
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .vk-formula-flow {
            display: grid;
            gap: 8px;
        }

        .vk-formula-step {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            display: grid;
            gap: 10px;
            grid-template-columns: 34px minmax(0, 1fr) auto;
            min-width: 0;
            padding: 10px;
        }

        .vk-formula-step-number {
            align-items: center;
            background: #dbeafe;
            border-radius: 9px;
            color: #1d4ed8;
            display: inline-flex;
            font-size: 12px;
            font-weight: 900;
            height: 34px;
            justify-content: center;
            width: 34px;
        }

        .vk-formula-step-label {
            color: #0f172a;
            font-size: 12px;
            font-weight: 800;
        }

        .vk-formula-step-note {
            color: #64748b;
            font-size: 11px;
            margin-top: 2px;
            overflow-wrap: anywhere;
        }

        .vk-formula-step-value {
            color: #be123c;
            font-size: 14px;
            font-weight: 900;
            min-width: 0;
            overflow-wrap: anywhere;
            text-align: right;
            white-space: nowrap;
        }

        .vk-detail-total-line {
            align-items: center;
            background: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 11px;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            margin-top: 10px;
            min-width: 0;
            padding: 12px;
        }

        .vk-detail-total-line span {
            color: #9f1239;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .vk-detail-total-line strong {
            color: #be123c;
            font-size: 20px;
            font-weight: 900;
            overflow-wrap: anywhere;
            text-align: right;
        }

        .vk-recipient-toolbar {
            align-items: center;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .vk-recipient-search {
            max-width: 320px;
            width: 100%;
        }

        .vk-recipient-list {
            display: grid;
            gap: 8px;
            max-height: 430px;
            overflow: auto;
            padding-right: 2px;
        }

        .vk-recipient-item {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 11px;
            display: grid;
            gap: 10px;
            grid-template-columns: 42px minmax(0, 1fr) auto;
            min-width: 0;
            padding: 10px;
        }

        .vk-recipient-avatar {
            align-items: center;
            background: #dbeafe;
            border-radius: 11px;
            color: #1d4ed8;
            display: inline-flex;
            font-size: 15px;
            font-weight: 900;
            height: 42px;
            justify-content: center;
            width: 42px;
        }

        .vk-recipient-name {
            color: #0f172a;
            font-size: 13px;
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        .vk-recipient-meta {
            color: #64748b;
            font-size: 11px;
            margin-top: 1px;
            overflow-wrap: anywhere;
        }

        .vk-recipient-amount {
            color: #0f766e;
            font-size: 14px;
            font-weight: 900;
            min-width: 0;
            overflow-wrap: anywhere;
            text-align: right;
            white-space: nowrap;
        }

        .vk-recipient-share {
            color: #64748b;
            display: block;
            font-size: 10px;
            font-weight: 700;
            margin-top: 2px;
        }

        .vk-detail-empty {
            color: #64748b;
            font-size: 12px;
            padding: 14px;
            text-align: center;
        }

        .vk-detail-copy {
            align-items: center;
            background: #fff;
            border: 1px solid #bfdbfe;
            border-radius: 9px;
            color: #1d4ed8;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 6px;
            padding: 9px 11px;
        }

        .vk-detail-modal .modal-dialog {
            max-width: 1180px;
        }

        .vk-detail-shell {
            background: #eef4fb;
        }

        .vk-detail-hero {
            background:
                linear-gradient(135deg, rgba(15, 23, 42, .94), rgba(30, 64, 175, .88)),
                linear-gradient(90deg, #0f766e, #be123c);
            padding: 20px 22px 18px;
        }

        .vk-detail-identity,
        .vk-detail-actions {
            align-items: center;
            display: flex;
            gap: 12px;
            min-width: 0;
        }

        .vk-detail-identity {
            flex: 1 1 auto;
        }

        .vk-detail-actions {
            flex: 0 0 auto;
        }

        .vk-detail-icon {
            background: #fff;
            border: 0;
            box-shadow: 0 12px 24px rgba(15, 23, 42, .2);
            color: #1d4ed8;
        }

        .vk-detail-title {
            font-size: 21px;
            line-height: 1.2;
        }

        .vk-detail-status,
        .vk-detail-copy {
            backdrop-filter: blur(8px);
            min-height: 38px;
        }

        .vk-detail-copy {
            background: rgba(255, 255, 255, .15);
            border-color: rgba(255, 255, 255, .3);
            color: #fff;
        }

        .vk-detail-copy:hover {
            background: #fff;
            color: #1d4ed8;
        }

        .vk-detail-kpis {
            gap: 9px;
            margin-top: 16px;
        }

        .vk-detail-kpi {
            background: rgba(255, 255, 255, .1);
            border-color: rgba(255, 255, 255, .18);
            border-radius: 8px;
            padding: 11px 12px;
        }

        .vk-detail-kpi strong {
            font-size: 17px;
        }

        .vk-detail-body {
            background: #eef4fb;
            padding: 14px;
        }

        .vk-detail-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-bottom: 10px;
        }

        .vk-detail-badge {
            align-items: center;
            background: #fff;
            border: 1px solid #d8e3ef;
            border-radius: 999px;
            color: #334155;
            display: inline-flex;
            font-size: 11px;
            font-weight: 800;
            gap: 6px;
            min-height: 32px;
            padding: 7px 10px;
        }

        .vk-detail-badge i {
            color: #1d4ed8;
            font-size: 15px;
        }

        .vk-detail-tabs {
            background: #dbe7f3;
            border: 0;
            border-radius: 8px;
            padding: 5px;
        }

        .vk-detail-tab {
            border-radius: 7px;
            min-height: 38px;
        }

        .vk-detail-tab.active {
            background: #fff;
            box-shadow: 0 8px 18px rgba(15, 23, 42, .08);
        }

        .vk-detail-overview-grid,
        .vk-detail-recipient-layout {
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(0, 1.35fr) minmax(300px, .65fr);
        }

        .vk-detail-recipient-layout {
            grid-template-columns: minmax(260px, .45fr) minmax(0, 1fr);
        }

        .vk-detail-side {
            display: grid;
            gap: 12px;
        }

        .vk-detail-panel {
            border: 1px solid #d8e3ef;
            border-radius: 8px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .04);
            padding: 13px;
        }

        .vk-detail-panel-main {
            min-height: 100%;
        }

        .vk-formula-flow-wide {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .vk-formula-flow-wide .vk-formula-step {
            align-items: stretch;
            grid-template-columns: 1fr;
            padding: 12px;
            position: relative;
        }

        .vk-formula-flow-wide .vk-formula-step:not(:last-child)::after {
            color: #94a3b8;
            content: "\F142";
            font-family: "Material Design Icons";
            font-size: 20px;
            position: absolute;
            right: -17px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
        }

        .vk-formula-flow-wide .vk-formula-step-number {
            height: 30px;
            width: 30px;
        }

        .vk-formula-flow-wide .vk-formula-step-value {
            color: #0f766e;
            font-size: 16px;
            text-align: left;
            white-space: normal;
        }

        .vk-detail-total-line {
            background: #0f766e;
            border: 0;
            color: #fff;
        }

        .vk-detail-total-line span,
        .vk-detail-total-line strong {
            color: #fff;
        }

        .vk-detail-composition {
            display: grid;
            gap: 9px;
        }

        .vk-composition-row {
            display: grid;
            gap: 7px;
        }

        .vk-composition-head {
            align-items: center;
            color: #334155;
            display: flex;
            font-size: 11px;
            font-weight: 800;
            justify-content: space-between;
        }

        .vk-composition-track {
            background: #e2e8f0;
            border-radius: 999px;
            height: 8px;
            overflow: hidden;
        }

        .vk-composition-bar {
            background: linear-gradient(90deg, #1d4ed8, #0f766e);
            border-radius: 999px;
            height: 100%;
            min-width: 2px;
        }

        .vk-detail-stat-grid,
        .vk-detail-recipient-metrics {
            display: grid;
            gap: 8px;
        }

        .vk-detail-stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .vk-detail-stat {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            min-width: 0;
            padding: 10px;
        }

        .vk-detail-stat span {
            color: #64748b;
            display: block;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .vk-detail-stat strong {
            color: #0f172a;
            display: block;
            font-size: 13px;
            font-weight: 900;
            margin-top: 3px;
            overflow-wrap: anywhere;
        }

        .vk-recipient-toolbar {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 9px;
            padding: 10px;
        }

        .vk-recipient-list {
            max-height: 470px;
        }

        .vk-recipient-item {
            background: #fff;
            border-radius: 8px;
            grid-template-columns: 42px minmax(0, 1fr) minmax(150px, auto);
        }

        .vk-recipient-avatar {
            background: #ecfdf5;
            color: #0f766e;
        }

        .vk-recipient-amount {
            color: #1d4ed8;
        }

        #modalGenerateVk .modal-dialog {
            max-height: calc(100vh - 1rem);
            max-height: calc(100dvh - 1rem);
            margin-bottom: .5rem;
            margin-top: .5rem;
        }

        #modalGenerateVk .modal-content {
            display: flex;
            max-height: calc(100vh - 1rem);
            max-height: calc(100dvh - 1rem);
            overflow: hidden;
        }

        #modalGenerateVk .vk-generate-form {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }

        #modalGenerateVk .modal-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
        }

        #modalGenerateVk .modal-footer {
            background: #fff;
            flex: 0 0 auto;
        }

        .vk-input-rows {
            display: grid;
            gap: 10px;
        }

        .vk-input-row {
            align-items: start;
            background: #f8fafc;
            border: 1px solid var(--vk-line);
            border-radius: 8px;
            display: grid;
            gap: 10px;
            grid-template-columns: 34px minmax(260px, 1.8fr) minmax(160px, 1fr) minmax(110px, .7fr) minmax(145px, .8fr) minmax(120px, .8fr) 34px;
            padding: 10px;
        }

        .vk-row-number,
        .vk-remove-row {
            align-items: center;
            border-radius: 8px;
            display: inline-flex;
            height: 34px;
            justify-content: center;
            width: 34px;
        }

        .vk-row-number {
            background: #ffe4e6;
            color: #be123c;
            font-size: 12px;
            font-weight: 900;
        }

        .vk-remove-row {
            background: #fff;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .vk-row-field label {
            color: #475569;
            font-size: 10px;
            font-weight: 800;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .vk-row-total {
            color: var(--vk-rose);
            font-size: 13px;
            font-weight: 900;
            padding-top: 8px;
        }

        .vk-preview-total {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 8px;
            color: var(--vk-rose);
            font-size: 18px;
            font-weight: 900;
            padding: 12px;
            text-align: center;
        }

        .vk-bpjs-preview {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            color: #1e3a8a;
            display: grid;
            gap: 6px;
            padding: 11px;
        }

        .vk-bpjs-preview-row {
            align-items: center;
            display: flex;
            font-size: 12px;
            gap: 10px;
            justify-content: space-between;
            min-width: 0;
        }

        .vk-bpjs-preview-row span,
        .vk-bpjs-preview-row strong {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .vk-bpjs-preview-row strong {
            font-size: 13px;
            text-align: right;
        }

        .vk-action-option {
            line-height: 1.35;
        }

        .vk-action-option-title {
            color: #0f172a;
            font-size: 12px;
            font-weight: 800;
        }

        .vk-action-option-meta {
            color: #64748b;
            font-size: 11px;
        }

        @media (max-width: 1199.98px) {
            #modalGenerateVk .vk-input-row {
                grid-template-columns: 34px minmax(0, 1fr) 34px;
            }

            #modalGenerateVk .vk-row-field,
            #modalGenerateVk .vk-row-total {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 991.98px) {
            .vk-hero,
            .vk-panel-head,
            .vk-filter-bar {
                align-items: stretch;
                flex-direction: column;
            }

            .vk-hero-controls,
            .vk-panel-actions,
            .vk-search {
                flex-basis: auto;
                width: 100%;
            }

            .vk-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .vk-detail-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .vk-detail-grid {
                grid-template-columns: 1fr;
            }

            .vk-detail-overview-grid,
            .vk-detail-recipient-layout {
                grid-template-columns: 1fr;
            }

            .vk-formula-flow-wide {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .vk-formula-flow-wide .vk-formula-step:not(:last-child)::after {
                display: none;
            }
        }

        @media (max-width: 767.98px) {
            .vk-detail-modal .modal-dialog {
                margin: .5rem auto;
                width: calc(100% - 1rem);
            }

            .vk-detail-hero,
            .vk-detail-body {
                padding: 14px;
            }

            .vk-detail-title {
                font-size: 17px;
            }

            .vk-detail-icon {
                border-radius: 10px;
                font-size: 23px;
                height: 44px;
                width: 44px;
            }

            .vk-detail-kpi strong {
                font-size: 16px;
            }

            .vk-formula-step {
                align-items: start;
                grid-template-columns: 32px minmax(0, 1fr);
            }

            .vk-formula-step-value {
                grid-column: 2;
                text-align: left;
                white-space: normal;
            }

            .vk-detail-total-line {
                align-items: flex-start;
                flex-direction: column;
            }

            .vk-detail-total-line strong {
                text-align: left;
            }

            .vk-recipient-item {
                align-items: start;
                grid-template-columns: 38px minmax(0, 1fr);
            }

            .vk-recipient-avatar {
                border-radius: 10px;
                height: 38px;
                width: 38px;
            }

            .vk-recipient-amount {
                grid-column: 2;
                text-align: left;
                white-space: normal;
            }

            .vk-bpjs-preview-row {
                align-items: flex-start;
                gap: 4px;
            }

            .vk-bpjs-preview-row strong {
                overflow-wrap: anywhere;
                text-align: right;
            }
        }

        @media (max-width: 575.98px) {
            .vk-detail-modal .modal-dialog {
                height: 100%;
                margin: 0;
                max-width: none;
                width: 100%;
            }

            .vk-detail-modal .modal-content,
            .vk-detail-shell {
                border-radius: 0 !important;
                max-height: 100vh;
                max-height: 100dvh;
                min-height: 100vh;
                min-height: 100dvh;
            }

            .vk-type-tabs {
                display: grid;
            }

            .vk-summary-grid {
                grid-template-columns: 1fr;
            }

            .vk-detail-hero-top,
            .vk-recipient-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .vk-detail-actions {
                align-items: stretch;
                display: grid;
                grid-template-columns: 1fr 1fr auto;
            }

            .vk-detail-kpis,
            .vk-detail-tabs,
            .vk-formula-flow-wide,
            .vk-detail-stat-grid {
                grid-template-columns: 1fr;
            }

            .vk-detail-status {
                justify-content: center;
                width: 100%;
            }

            .vk-bpjs-preview-row {
                flex-direction: column;
            }

            .vk-bpjs-preview-row strong {
                text-align: left;
            }
        }
    </style>
@endpush

@section("content")
    <div class="vk-page">
        <nav class="page-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route("backOffice.keuangan.hitungPremi") }}">Generate Premi</a>
                </li>
                <li class="breadcrumb-item active">Generate VK</li>
            </ol>
        </nav>

        <section class="vk-hero">
            <div class="vk-hero-main">
                <div class="vk-hero-icon">
                    <i class="mdi mdi-mother-nurse"></i>
                </div>
                <div>
                    <div class="vk-eyebrow">Manual Generator</div>
                    <h4 class="vk-title">Generate Premi VK</h4>
                    <p class="vk-description">
                        Input jumlah tindakan VK dari tindakan Khanza, pilih jenis BPJS atau UMUM,
                        tentukan ploting premi, lalu sistem menghitung total premi dari nominal hitung dan konfigurasi BPJS.
                    </p>
                </div>
            </div>
            <div class="vk-hero-controls">
                <div class="vk-control-box">
                    <label for="periodeVk">Periode Perhitungan</label>
                    <div class="vk-period-picker">
                        <button type="button" class="vk-period-step" id="btnPrevPeriodVk"
                            title="Bulan sebelumnya">
                            <i class="mdi mdi-chevron-left"></i>
                        </button>
                        <input type="month" id="periodeVk" class="form-control">
                        <button type="button" class="vk-period-step" id="btnNextPeriodVk"
                            title="Bulan berikutnya">
                            <i class="mdi mdi-chevron-right"></i>
                        </button>
                    </div>
                    <button type="button" class="vk-period-current" id="btnCurrentPeriodVk">
                        <i class="mdi mdi-calendar-today-outline"></i>
                        Bulan Ini
                    </button>
                </div>
            </div>
        </section>

        <div class="vk-type-tabs">
            <button type="button" class="vk-type-tab active" data-type="umum">
                <i class="mdi mdi-account-cash-outline"></i>
                <span>
                    <strong>UMUM</strong>
                    <small>Input tindakan VK non-BPJS</small>
                </span>
            </button>
            <button type="button" class="vk-type-tab" data-type="bpjs">
                <i class="mdi mdi-shield-account-outline"></i>
                <span>
                    <strong>BPJS</strong>
                    <small>Input tindakan VK BPJS</small>
                </span>
            </button>
        </div>

        <section class="vk-panel">
            <div class="vk-panel-head">
                <div>
                    <div class="vk-panel-title">Ringkasan Generate VK</div>
                    <div class="vk-panel-subtitle" id="summaryVkSubtitle">
                        Data dihitung dari input real tindakan VK.
                    </div>
                </div>
                <div class="vk-panel-actions">
                    <button type="button" class="vk-refresh-btn" id="btnRefreshVk">
                        <i class="mdi mdi-refresh"></i>
                        Refresh
                    </button>
                    <button type="button" class="vk-copy-btn" id="btnCopyNextMonthVk">
                        <i class="mdi mdi-content-copy"></i>
                        Copy Bulan Berikutnya
                    </button>
                    <button type="button" class="vk-lock-all-btn" id="btnLockAllVk">
                        <i class="mdi mdi-lock-check-outline"></i>
                        Kunci Semua
                    </button>
                    <button type="button" class="vk-config-btn" id="btnConfigVk">
                        <i class="mdi mdi-tune-variant"></i>
                        Konfigurasi
                    </button>
                    <button type="button" class="vk-generate-btn" id="btnOpenGenerateVk">
                        <i class="mdi mdi-plus-circle-outline"></i>
                        Generate VK
                    </button>
                </div>
            </div>
            <div class="vk-summary-grid">
                <div class="vk-summary-card">
                    <div class="vk-summary-label">Tindakan</div>
                    <div class="vk-summary-value" id="summaryTindakanVk">0</div>
                </div>
                <div class="vk-summary-card">
                    <div class="vk-summary-label">Jumlah</div>
                    <div class="vk-summary-value" id="summaryJumlahVk">0</div>
                </div>
                <div class="vk-summary-card">
                    <div class="vk-summary-label">Data Generate</div>
                    <div class="vk-summary-value" id="summaryDataVk">0</div>
                </div>
                <div class="vk-summary-card d-none" id="summaryHasilHitungVkCard">
                    <div class="vk-summary-label">Total Hasil Hitung</div>
                    <div class="vk-summary-value" id="summaryHasilHitungVk">Rp 0</div>
                </div>
                <div class="vk-summary-card total">
                    <div class="vk-summary-label">Total VK</div>
                    <div class="vk-summary-value" id="summaryTotalVk">Rp 0</div>
                </div>
            </div>
            <div class="vk-ploting-summary" id="summaryPlotingVk">
                <div class="vk-ploting-empty">Belum ada total per ploting.</div>
            </div>
        </section>

        <section class="vk-panel">
            <div class="vk-panel-head">
                <div>
                    <div class="vk-panel-title" id="resultVkTitle">Hasil Generate VK Umum</div>
                    <div class="vk-panel-subtitle">Hasil tersimpan per tindakan, jenis pelayanan, dan ploting premi.</div>
                </div>
            </div>
            <div class="vk-filter-bar">
                <div class="input-group vk-search">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="mdi mdi-magnify text-muted"></i>
                    </span>
                    <input type="search" id="searchGenerateVk" class="form-control border-start-0"
                        placeholder="Cari tindakan, kode, sumber, atau ploting...">
                </div>
                <small class="text-muted">
                    Data terbuka bisa direvisi. Data terkunci hanya Admin yang dapat membuka.
                </small>
            </div>
            <div class="vk-table-wrap">
                <div class="table-responsive">
                    <table id="tableGenerateVk" class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Periode</th>
                                <th>Jenis</th>
                                <th>Tindakan</th>
                                <th>Sumber</th>
                                <th>Ploting</th>
                                <th class="text-center">Jumlah</th>
                                <th class="text-end">Nominal</th>
                                <th class="text-end">Total</th>
                                <th>Status</th>
                                <th>Generate</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </section>
    </div>

    @include("simrs.backOffice.keuangan.hitungPremi.generateVk.modal")
@endsection

@push("scripts")
    @include("simrs.backOffice.keuangan.hitungPremi.generateVk.jsMain")
@endpush
