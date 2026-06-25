@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.select2")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")

    <style>
        .apotek-page {
            --apt-main: #db2777;
            --apt-rose: #be123c;
            --apt-cyan: #0891b2;
            --apt-deep: #0f172a;
            --apt-line: #e2e8f0;
            --apt-muted: #64748b;
            --apt-soft: #fdf2f8;
            color: #172033;
        }

        .apotek-page .breadcrumb {
            background: transparent;
            font-size: 13px;
            margin-bottom: 0;
            padding: 0;
        }

        .apotek-hero {
            align-items: stretch;
            background: linear-gradient(135deg, #0f172a 0%, #be123c 52%, #0891b2 100%);
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

        .apotek-hero::after {
            background: radial-gradient(circle, rgba(255, 255, 255, .22), transparent 58%);
            content: "";
            height: 220px;
            position: absolute;
            right: -70px;
            top: -85px;
            width: 220px;
        }

        .apotek-hero-main {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            min-width: 0;
            position: relative;
            z-index: 1;
        }

        .apotek-hero-icon {
            align-items: center;
            background: #fff;
            border-radius: 13px;
            color: var(--apt-main);
            display: flex;
            flex: 0 0 auto;
            font-size: 30px;
            height: 58px;
            justify-content: center;
            width: 58px;
        }

        .apotek-title {
            font-size: 24px;
            font-weight: 800;
            margin: 3px 0 5px;
        }

        .apotek-eyebrow,
        .apotek-control-box label {
            color: #fce7f3;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .apotek-description {
            color: rgba(255, 255, 255, .82);
            font-size: 13px;
            margin: 0;
            max-width: 760px;
        }

        .apotek-hero-controls {
            display: grid;
            flex: 0 0 320px;
            gap: 10px;
            position: relative;
            z-index: 1;
        }

        .apotek-control-box {
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .26);
            border-radius: 14px;
            padding: 12px;
        }

        .apotek-control-box label {
            display: block;
            margin-bottom: 6px;
        }

        .apotek-period-picker {
            align-items: center;
            display: grid;
            gap: 7px;
            grid-template-columns: 38px minmax(0, 1fr) 38px;
        }

        .apotek-period-picker .form-control {
            border: 0;
            height: 39px;
            text-align: center;
        }

        .apotek-period-step,
        .apotek-period-current {
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

        .apotek-period-current {
            font-size: 11px;
            font-weight: 800;
            gap: 5px;
            margin-top: 8px;
            width: 100%;
        }

        .apotek-type-tabs {
            background: #fff;
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .045);
            display: flex;
            gap: 8px;
            margin: 16px 0;
            padding: 8px;
        }

        .apotek-type-tab {
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

        .apotek-type-tab i {
            font-size: 23px;
        }

        .apotek-type-tab strong,
        .apotek-type-tab small {
            display: block;
        }

        .apotek-type-tab small {
            font-size: 11px;
        }

        .apotek-type-tab.active {
            background: linear-gradient(135deg, #fdf2f8, #ecfeff);
            box-shadow: inset 0 0 0 1px #f9a8d4, 0 8px 18px rgba(219, 39, 119, .08);
            color: var(--apt-main);
        }

        .apotek-panel {
            background: #fff;
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 16px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, .06);
            margin-bottom: 16px;
            overflow: hidden;
        }

        .apotek-panel-head,
        .apotek-filter-bar {
            align-items: center;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            padding: 15px 17px;
        }

        .apotek-panel-head {
            border-bottom: 1px solid #eef2f7;
        }

        .apotek-panel-title {
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
        }

        .apotek-panel-subtitle {
            color: var(--apt-muted);
            font-size: 11px;
        }

        .apotek-panel-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .apotek-btn {
            align-items: center;
            border-radius: 9px;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 6px;
            padding: 10px 12px;
        }

        .apotek-btn-light {
            background: #fff;
            border: 1px solid var(--apt-line);
            color: #475569;
        }

        .apotek-btn-primary {
            background: linear-gradient(135deg, #db2777, #0891b2);
            border: 0;
            box-shadow: 0 10px 22px rgba(219, 39, 119, .20);
            color: #fff;
        }

        .apotek-preview-grid,
        .apotek-detail-grid {
            display: grid;
            gap: 11px;
            padding: 15px 17px;
        }

        .apotek-preview-grid,
        .apotek-detail-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            padding: 0;
        }

        .apotek-summary-premium {
            background:
                linear-gradient(135deg, rgba(253, 242, 248, .96), rgba(236, 254, 255, .94)),
                #fff;
            display: grid;
            gap: 14px;
            grid-template-columns: minmax(280px, 1.1fr) minmax(280px, .9fr);
            padding: 17px;
        }

        .apotek-summary-highlight {
            background: linear-gradient(135deg, #0f172a 0%, #9f1239 58%, #0e7490 100%);
            border-radius: 14px;
            box-shadow: 0 18px 34px rgba(15, 23, 42, .18);
            color: #fff;
            min-width: 0;
            overflow: hidden;
            padding: 18px;
            position: relative;
        }

        .apotek-summary-highlight::after {
            background: rgba(255, 255, 255, .10);
            content: "";
            height: 100%;
            position: absolute;
            right: 0;
            top: 0;
            width: 34%;
        }

        .apotek-summary-highlight > * {
            position: relative;
            z-index: 1;
        }

        .apotek-summary-topline {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .apotek-summary-badge {
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

        .apotek-summary-highlight-label {
            color: #fbcfe8;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .apotek-summary-highlight-value {
            color: #fff;
            font-size: 32px;
            font-weight: 950;
            line-height: 1.08;
            margin-top: 6px;
            overflow-wrap: anywhere;
        }

        .apotek-summary-highlight-note {
            color: rgba(255, 255, 255, .78);
            font-size: 12px;
            margin-top: 7px;
        }

        .apotek-summary-highlight-grid {
            display: grid;
            gap: 9px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 18px;
        }

        .apotek-summary-micro {
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 11px;
            padding: 10px;
        }

        .apotek-summary-micro-label {
            color: #fce7f3;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .apotek-summary-micro-value {
            color: #fff;
            font-size: 19px;
            font-weight: 950;
            margin-top: 3px;
        }

        .apotek-summary-stack {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .apotek-summary-stack-card {
            background: rgba(255, 255, 255, .92);
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 13px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .07);
            min-width: 0;
            padding: 14px;
            position: relative;
        }

        .apotek-summary-stack-card.wide {
            grid-column: 1 / -1;
        }

        .apotek-summary-stack-top {
            align-items: center;
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }

        .apotek-summary-stack-icon {
            align-items: center;
            background: #fdf2f8;
            border: 1px solid #fbcfe8;
            border-radius: 10px;
            color: var(--apt-main);
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 19px;
            height: 36px;
            justify-content: center;
            width: 36px;
        }

        .apotek-summary-stack-card.cyan .apotek-summary-stack-icon {
            background: #ecfeff;
            border-color: #a5f3fc;
            color: #0e7490;
        }

        .apotek-summary-stack-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .apotek-summary-stack-value {
            color: #0f172a;
            font-size: 20px;
            font-weight: 950;
            margin-top: 7px;
            overflow-wrap: anywhere;
        }

        .apotek-summary-stack-note {
            color: #64748b;
            font-size: 11px;
            margin-top: 4px;
        }

        .apotek-summary-data-band {
            border-top: 1px solid rgba(226, 232, 240, .85);
            display: grid;
            gap: 10px;
            grid-column: 1 / -1;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            padding-top: 13px;
        }

        .apotek-summary-data-item {
            align-items: center;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 12px;
            display: grid;
            gap: 10px;
            grid-template-columns: 34px minmax(0, 1fr);
            padding: 11px;
        }

        .apotek-summary-data-icon {
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

        .apotek-summary-data-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .apotek-summary-data-value {
            color: #0f172a;
            font-size: 16px;
            font-weight: 950;
            margin-top: 2px;
            overflow-wrap: anywhere;
        }

        .apotek-mini-card {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 12px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .045);
            padding: 14px;
        }

        .apotek-mini-card.total {
            background: linear-gradient(135deg, #db2777, #0891b2);
            border: 0;
            color: #fff;
        }

        .apotek-mini-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .apotek-mini-card.total .apotek-mini-label,
        .apotek-mini-card.total .apotek-mini-note {
            color: #fce7f3;
        }

        .apotek-mini-value {
            color: #0f172a;
            font-size: 18px;
            font-weight: 900;
            margin-top: 4px;
            overflow-wrap: anywhere;
        }

        .apotek-mini-card.total .apotek-mini-value {
            color: #fff;
        }

        .apotek-formula-strip {
            background: #fff;
            border-top: 1px solid #eef2f7;
            display: block;
            padding: 16px 17px 18px;
        }

        .apotek-formula-head {
            align-items: flex-start;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .apotek-formula-title {
            color: #0f172a;
            font-size: 14px;
            font-weight: 950;
        }

        .apotek-formula-subtitle {
            color: #64748b;
            font-size: 11px;
            line-height: 1.45;
            margin-top: 2px;
        }

        .apotek-formula-head-badge {
            align-items: center;
            background: linear-gradient(135deg, #fdf2f8, #ecfeff);
            border: 1px solid #f9a8d4;
            border-radius: 999px;
            color: #be123c;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 10px;
            font-weight: 950;
            gap: 5px;
            padding: 6px 10px;
            white-space: nowrap;
        }

        .apotek-formula-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        }

        .apotek-formula-card {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(226, 232, 240, .95);
            border-radius: 13px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .055);
            min-height: 128px;
            min-width: 0;
            overflow: hidden;
            padding: 13px;
            position: relative;
        }

        .apotek-formula-card::before {
            background: linear-gradient(180deg, #db2777, #0891b2);
            bottom: 0;
            content: "";
            left: 0;
            position: absolute;
            top: 0;
            width: 4px;
        }

        .apotek-formula-card.cyan::before {
            background: #0891b2;
        }

        .apotek-formula-card.dark::before {
            background: #0f172a;
        }

        .apotek-formula-card-top {
            align-items: center;
            display: flex;
            gap: 9px;
            justify-content: space-between;
        }

        .apotek-formula-card-main {
            align-items: center;
            display: flex;
            gap: 9px;
            min-width: 0;
        }

        .apotek-formula-icon {
            align-items: center;
            background: #fdf2f8;
            border: 1px solid #fbcfe8;
            border-radius: 10px;
            color: #be123c;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 18px;
            height: 36px;
            justify-content: center;
            width: 36px;
        }

        .apotek-formula-card.cyan .apotek-formula-icon {
            background: #ecfeff;
            border-color: #a5f3fc;
            color: #0e7490;
        }

        .apotek-formula-card.dark .apotek-formula-icon {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #0f172a;
        }

        .apotek-formula-card-title {
            color: #0f172a;
            font-size: 12px;
            font-weight: 950;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .apotek-formula-chip {
            background: #fff;
            border: 1px solid #fbcfe8;
            border-radius: 999px;
            color: #db2777;
            display: inline-flex;
            font-size: 11px;
            font-weight: 950;
            line-height: 1.25;
            max-width: 100%;
            padding: 5px 9px;
            text-align: left;
            white-space: normal;
        }

        .apotek-formula-card.cyan .apotek-formula-chip {
            border-color: #a5f3fc;
            color: #0e7490;
        }

        .apotek-formula-note {
            color: #64748b;
            font-size: 11px;
            line-height: 1.5;
            margin-top: 11px;
        }

        .apotek-formula-meta {
            color: #94a3b8;
            font-size: 10px;
            font-weight: 800;
            margin-top: 9px;
            text-transform: uppercase;
        }

        .apotek-info-list {
            display: grid;
            gap: 8px;
        }

        .apotek-info-row {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #eef2f7;
            border-radius: 10px;
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(0, 1fr) auto;
            padding: 10px 11px;
        }

        .apotek-info-label {
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
        }

        .apotek-info-note {
            color: #64748b;
            font-size: 11px;
            margin-top: 2px;
        }

        .apotek-info-value {
            color: var(--apt-main);
            font-size: 12px;
            font-weight: 900;
            overflow-wrap: anywhere;
            text-align: right;
        }

        .apotek-simple-head,
        .apotek-simple-section,
        .apotek-config-section {
            background: #fff;
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 14px;
            padding: 15px;
        }

        .apotek-simple-head {
            align-items: flex-start;
            background: linear-gradient(135deg, #0f172a, #be123c 62%, #0891b2);
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

        .apotek-simple-main {
            align-items: center;
            display: flex;
            gap: 12px;
            min-width: 0;
            position: relative;
            z-index: 1;
        }

        .apotek-simple-icon {
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

        .apotek-simple-title {
            color: inherit;
            font-size: 15px;
            font-weight: 900;
        }

        .apotek-simple-text,
        .apotek-simple-section-subtitle {
            color: #64748b;
            font-size: 12px;
            line-height: 1.45;
            margin-top: 3px;
        }

        .apotek-simple-head .apotek-simple-text {
            color: rgba(255, 255, 255, .78);
        }

        .apotek-simple-badge {
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .28);
            border-radius: 999px;
            color: #fff;
            flex: 0 0 auto;
            font-size: 11px;
            font-weight: 900;
            padding: 6px 10px;
            white-space: nowrap;
        }

        .apotek-simple-section-title {
            align-items: center;
            color: #0f172a;
            display: flex;
            font-size: 13px;
            font-weight: 900;
            gap: 7px;
            margin-bottom: 10px;
        }

        .apotek-simple-section-title::before {
            background: linear-gradient(135deg, #db2777, #0891b2);
            border-radius: 999px;
            content: "";
            height: 8px;
            width: 8px;
        }

        .apotek-config-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .apotek-config-section h6 {
            align-items: center;
            color: #0f172a;
            display: flex;
            font-size: 13px;
            font-weight: 900;
            gap: 7px;
            margin-bottom: 12px;
        }

        .apotek-config-section .form-label {
            color: #334155;
            font-size: 11px;
            font-weight: 900;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .apotek-field-help {
            color: #64748b;
            display: block;
            font-size: 10px;
            line-height: 1.4;
            margin-top: 5px;
        }

        .apotek-source-mode-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .apotek-source-mode-option {
            align-items: flex-start;
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            border: 1px solid rgba(203, 213, 225, .9);
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            gap: 10px;
            min-width: 0;
            padding: 12px;
            transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
        }

        .apotek-source-mode-option:hover {
            border-color: rgba(8, 145, 178, .55);
            box-shadow: 0 12px 26px rgba(15, 23, 42, .08);
            transform: translateY(-1px);
        }

        .apotek-source-mode-option input {
            flex: 0 0 auto;
            margin-top: 2px;
        }

        .apotek-source-mode-option strong {
            color: #0f172a;
            display: block;
            font-size: 12px;
            font-weight: 900;
            line-height: 1.25;
        }

        .apotek-source-mode-option small {
            color: #64748b;
            display: block;
            font-size: 11px;
            line-height: 1.4;
            margin-top: 3px;
        }

        .apotek-recipient-list {
            display: grid;
            gap: 8px;
        }

        .apotek-recipient-item {
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

        .apotek-recipient-name {
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
        }

        .apotek-recipient-meta {
            color: #64748b;
            font-size: 11px;
            margin-top: 2px;
        }

        .apotek-recipient-total {
            color: var(--apt-main);
            font-size: 13px;
            font-weight: 900;
            text-align: right;
        }

        .apotek-recipient-status {
            background: #fdf2f8;
            border: 1px solid #f9a8d4;
            border-radius: 999px;
            color: #be123c;
            display: inline-flex;
            font-size: 10px;
            font-weight: 900;
            padding: 4px 8px;
        }

        .apotek-quality {
            border-radius: 10px;
            font-size: 12px;
            line-height: 1.5;
            margin-top: 12px;
            padding: 11px 12px;
        }

        .apotek-quality.ok {
            background: linear-gradient(135deg, #fdf2f8, #ecfeff);
            border: 1px solid #f9a8d4;
            color: #831843;
        }

        .apotek-quality.warn {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
        }

        .apotek-empty-state {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            color: #64748b;
            font-size: 12px;
            padding: 12px;
        }

        .apotek-filter-bar {
            border-bottom: 1px solid #eef2f7;
            padding: 13px 17px;
        }

        .apotek-search {
            max-width: 340px;
            width: 100%;
        }

        .apotek-table-wrap {
            padding: 8px 17px 17px;
        }

        .dataTables_wrapper .dataTables_filter {
            display: none;
        }

        .apotek-badge,
        .apotek-lock {
            border-radius: 999px;
            display: inline-flex;
            font-size: 10px;
            font-weight: 800;
            gap: 4px;
            padding: 4px 8px;
        }

        .apotek-badge.umum {
            background: #fdf2f8;
            color: #be123c;
        }

        .apotek-badge.bpjs {
            background: #cffafe;
            color: #0e7490;
        }

        .apotek-lock.locked {
            background: #fef3c7;
            color: #92400e;
        }

        .apotek-lock.open {
            background: #f1f5f9;
            color: #64748b;
        }

        .apotek-actions {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .apotek-actions .btn {
            align-items: center;
            display: inline-flex;
            height: 31px;
            justify-content: center;
            padding: 0;
            width: 31px;
        }

        #modalGenerateApotek .modal-content,
        #modalConfigApotek .modal-content,
        #modalDetailApotek .modal-content {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 38%, #f1f5f9 100%);
            border: 1px solid rgba(226, 232, 240, .85);
            border-radius: 16px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .10);
            overflow: hidden;
            position: relative;
        }

        #modalGenerateApotek .modal-content::before,
        #modalConfigApotek .modal-content::before,
        #modalDetailApotek .modal-content::before {
            background: linear-gradient(90deg, #db2777, #be123c, #0891b2);
            content: "";
            height: 4px;
            left: 0;
            pointer-events: none;
            position: absolute;
            right: 0;
            top: 0;
            z-index: 2;
        }

        #modalGenerateApotek .modal-dialog,
        #modalConfigApotek .modal-dialog,
        #modalDetailApotek .modal-dialog {
            max-width: 1080px;
        }

        .apotek-source-table {
            max-height: 330px;
            overflow: auto;
        }

        .apotek-source-table table {
            margin-bottom: 0;
        }

        .apotek-detail-filter-panel {
            background:
                radial-gradient(circle at top left, rgba(8, 145, 178, .10), transparent 34%),
                linear-gradient(135deg, #ffffff, #f8fafc);
            border: 1px solid rgba(203, 213, 225, .92);
            border-radius: 14px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, .08);
            margin-bottom: 14px;
            padding: 14px;
        }

        .apotek-detail-filter-head {
            align-items: flex-start;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .apotek-detail-filter-title {
            align-items: center;
            color: #0f172a;
            display: flex;
            font-size: 13px;
            font-weight: 900;
            gap: 7px;
        }

        .apotek-detail-filter-title i {
            color: #db2777;
            font-size: 18px;
        }

        .apotek-detail-filter-input {
            position: relative;
        }

        .apotek-detail-filter-input i {
            color: #64748b;
            font-size: 19px;
            left: 13px;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
        }

        .apotek-detail-filter-input .form-control {
            background: #fff;
            border: 1px solid rgba(148, 163, 184, .42);
            border-radius: 12px;
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
            min-height: 42px;
            padding-left: 41px;
        }

        .apotek-detail-filter-input .form-control:focus {
            border-color: rgba(8, 145, 178, .72);
            box-shadow: 0 0 0 .18rem rgba(8, 145, 178, .12);
        }

        .apotek-detail-filter-stats {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-top: 12px;
        }

        .apotek-detail-filter-card {
            background: #fff;
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 12px;
            min-width: 0;
            padding: 10px 11px;
        }

        .apotek-detail-filter-card span {
            color: #64748b;
            display: block;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .apotek-detail-filter-card strong {
            color: #0f172a;
            display: block;
            font-size: 16px;
            font-weight: 950;
            line-height: 1.25;
            margin-top: 4px;
            overflow-wrap: anywhere;
        }

        .apotek-detail-filter-card.accent {
            background: linear-gradient(135deg, #0f172a, #be123c);
            border-color: transparent;
            box-shadow: 0 12px 26px rgba(190, 18, 60, .22);
        }

        .apotek-detail-filter-card.accent span,
        .apotek-detail-filter-card.accent strong {
            color: #fff;
        }

        .apotek-detail-breakdown {
            display: grid;
            gap: 8px;
            margin-top: 10px;
            max-height: 210px;
            overflow: auto;
            padding-right: 2px;
        }

        .apotek-detail-breakdown-row {
            align-items: center;
            background: rgba(255, 255, 255, .86);
            border: 1px solid rgba(226, 232, 240, .92);
            border-radius: 12px;
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(0, 1.4fr) minmax(78px, .45fr) minmax(118px, .55fr);
            padding: 10px 12px;
        }

        .apotek-detail-breakdown-name {
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .apotek-detail-breakdown-meta,
        .apotek-detail-breakdown-metric span {
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            margin-top: 2px;
        }

        .apotek-detail-breakdown-metric {
            min-width: 0;
            text-align: right;
        }

        .apotek-detail-breakdown-metric strong {
            color: #0f172a;
            display: block;
            font-size: 12px;
            font-weight: 950;
            overflow-wrap: anywhere;
        }

        .apotek-detail-breakdown-more {
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            padding: 2px 4px;
        }

        @media (max-width: 991.98px) {
            .apotek-hero,
            .apotek-panel-head,
            .apotek-filter-bar,
            .apotek-simple-head,
            .apotek-formula-head {
                align-items: stretch;
                flex-direction: column;
            }

            .apotek-hero-controls,
            .apotek-panel-actions,
            .apotek-search {
                flex-basis: auto;
                max-width: none;
                width: 100%;
            }

            .apotek-summary-premium,
            .apotek-summary-stack,
            .apotek-summary-highlight-grid,
            .apotek-summary-data-band,
            .apotek-formula-grid,
            .apotek-preview-grid,
            .apotek-detail-grid,
            .apotek-config-grid {
                grid-template-columns: 1fr;
            }

            .apotek-type-tabs {
                flex-direction: column;
            }

            .apotek-info-row,
            .apotek-recipient-item {
                grid-template-columns: 1fr;
            }

            .apotek-info-value,
            .apotek-recipient-total {
                text-align: left;
            }

            .apotek-detail-filter-head {
                align-items: stretch;
                flex-direction: column;
            }

            .apotek-detail-filter-stats,
            .apotek-source-mode-grid,
            .apotek-detail-breakdown-row {
                grid-template-columns: 1fr;
            }

            .apotek-detail-breakdown-metric {
                text-align: left;
            }
        }
    </style>
@endpush

@section("content")
    <div class="apotek-page">
        <nav class="page-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route("backOffice.keuangan.hitungPremi") }}">Generate Premi</a>
                </li>
                <li class="breadcrumb-item active">Generate Apotek</li>
            </ol>
        </nav>

        <section class="apotek-hero">
            <div class="apotek-hero-main">
                <div class="apotek-hero-icon">
                    <i class="mdi mdi-pill"></i>
                </div>
                <div>
                    <div class="apotek-eyebrow">Otomatis dari Khanza</div>
                    <h4 class="apotek-title">Generate Premi Apotek</h4>
                    <p class="apotek-description">
                        Sistem membaca detail_pemberian_obat sesuai periode sumber, memisahkan UMUM dan BPJS,
                        mencocokkan kode barang ke mapping Farmasi, lalu menghitung formula 500 per item serta
                        pembagian 50%, 31%, 7%, 12%, dan premi bersama 30%.
                    </p>
                </div>
            </div>
            <div class="apotek-hero-controls">
                <div class="apotek-control-box">
                    <label for="periodeApotek">Periode Perhitungan</label>
                    <div class="apotek-period-picker">
                        <button type="button" class="apotek-period-step" id="btnPrevPeriodApotek">
                            <i class="mdi mdi-chevron-left"></i>
                        </button>
                        <input type="month" id="periodeApotek" class="form-control">
                        <button type="button" class="apotek-period-step" id="btnNextPeriodApotek">
                            <i class="mdi mdi-chevron-right"></i>
                        </button>
                    </div>
                    <button type="button" class="apotek-period-current" id="btnCurrentPeriodApotek">
                        <i class="mdi mdi-calendar-today-outline"></i>
                        Bulan Ini
                    </button>
                </div>
            </div>
        </section>

        <div class="apotek-type-tabs">
            <button type="button" class="apotek-type-tab active" data-type="umum">
                <i class="mdi mdi-account-cash-outline"></i>
                <span>
                    <strong>UMUM</strong>
                    <small>kd_pj selain BPJ dan -</small>
                </span>
            </button>
            <button type="button" class="apotek-type-tab" data-type="bpjs">
                <i class="mdi mdi-shield-account-outline"></i>
                <span>
                    <strong>BPJS Kesehatan</strong>
                    <small>kd_pj BPJ, sumber dapat dipilih</small>
                </span>
            </button>
        </div>

        <section class="apotek-panel">
            <div class="apotek-panel-head">
                <div>
                    <div class="apotek-panel-title">Ringkasan Generate Apotek</div>
                    <div class="apotek-panel-subtitle" id="summaryApotekSubtitle">
                        Data dihitung dari hasil generate periode aktif.
                    </div>
                </div>
                <div class="apotek-panel-actions">
                    <button type="button" class="apotek-btn apotek-btn-light" id="btnRefreshApotek">
                        <i class="mdi mdi-refresh"></i>
                        Refresh
                    </button>
                    <button type="button" class="apotek-btn apotek-btn-light" id="btnOpenConfigApotek">
                        <i class="mdi mdi-cog-outline"></i>
                        Konfigurasi
                    </button>
                    <button type="button" class="apotek-btn apotek-btn-primary" id="btnOpenGenerateApotek">
                        <i class="mdi mdi-calculator-variant-outline"></i>
                        Preview dan Generate
                    </button>
                </div>
            </div>
            <div class="apotek-summary-premium">
                <div class="apotek-summary-highlight">
                    <div class="apotek-summary-topline">
                        <span class="apotek-summary-badge" id="summaryTypeBadgeApotek">
                            <i class="mdi mdi-account-cash-outline"></i>
                            Umum
                        </span>
                        <span class="apotek-summary-badge" id="summaryGenerateCountApotek">
                            0 data generate
                        </span>
                    </div>
                    <div class="apotek-summary-highlight-label">Grand Total Periode</div>
                    <div class="apotek-summary-highlight-value" id="summaryGrandApotek">Rp 0</div>
                    <div class="apotek-summary-highlight-note" id="summaryGrandNoteApotek">
                        0 item mapping x nominal aktif.
                    </div>
                    <div class="apotek-summary-highlight-grid">
                        <div class="apotek-summary-micro">
                            <div class="apotek-summary-micro-label">Item Mapping</div>
                            <div class="apotek-summary-micro-value" id="summaryItemApotek">0</div>
                        </div>
                        <div class="apotek-summary-micro">
                            <div class="apotek-summary-micro-label">Pasien Terhitung</div>
                            <div class="apotek-summary-micro-value" id="summaryPasienApotek">0</div>
                        </div>
                    </div>
                </div>

                <div class="apotek-summary-stack">
                    <div class="apotek-summary-stack-card wide">
                        <div class="apotek-summary-stack-top">
                            <div>
                                <div class="apotek-summary-stack-label">Total Dibagikan</div>
                                <div class="apotek-summary-stack-value" id="summaryDibagikanApotek">Rp 0</div>
                                <div class="apotek-summary-stack-note">
                                    Formula 31%, 7%, dan 12% untuk pegawai terpilih.
                                </div>
                            </div>
                            <div class="apotek-summary-stack-icon">
                                <i class="mdi mdi-bank-transfer-out"></i>
                            </div>
                        </div>
                    </div>
                    <div class="apotek-summary-stack-card cyan">
                        <div class="apotek-summary-stack-top">
                            <div>
                                <div class="apotek-summary-stack-label">Premi Bersama</div>
                                <div class="apotek-summary-stack-value" id="summaryBersamaApotek">Rp 0</div>
                                <div class="apotek-summary-stack-note">30% default dari grand total.</div>
                            </div>
                            <div class="apotek-summary-stack-icon">
                                <i class="mdi mdi-account-group-outline"></i>
                            </div>
                        </div>
                    </div>
                    <div class="apotek-summary-stack-card">
                        <div class="apotek-summary-stack-top">
                            <div>
                                <div class="apotek-summary-stack-label">Terkunci</div>
                                <div class="apotek-summary-stack-value" id="summaryLockedApotek">0</div>
                                <div class="apotek-summary-stack-note">Data final periode aktif.</div>
                            </div>
                            <div class="apotek-summary-stack-icon">
                                <i class="mdi mdi-lock-check-outline"></i>
                            </div>
                        </div>
                    </div>

                    <div class="apotek-summary-data-band">
                        <div class="apotek-summary-data-item">
                            <div class="apotek-summary-data-icon"><i class="mdi mdi-database-search-outline"></i></div>
                            <div>
                                <div class="apotek-summary-data-label">Data Sumber</div>
                                <div class="apotek-summary-data-value" id="summarySourceApotek">0</div>
                            </div>
                        </div>
                        <div class="apotek-summary-data-item">
                            <div class="apotek-summary-data-icon"><i class="mdi mdi-pill-multiple"></i></div>
                            <div>
                                <div class="apotek-summary-data-label">Kode Obat Sumber</div>
                                <div class="apotek-summary-data-value" id="summaryObatApotek">0</div>
                            </div>
                        </div>
                        <div class="apotek-summary-data-item">
                            <div class="apotek-summary-data-icon"><i class="mdi mdi-chart-donut"></i></div>
                            <div>
                                <div class="apotek-summary-data-label">Rasio Mapping</div>
                                <div class="apotek-summary-data-value" id="summaryMatchRateApotek">0%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="apotek-formula-strip" id="formulaApotekStrip">
                <div class="apotek-formula-head">
                    <div>
                        <div class="apotek-formula-title">Formula Aktif</div>
                        <div class="apotek-formula-subtitle">Konfigurasi belum dimuat.</div>
                    </div>
                    <span class="apotek-formula-head-badge">
                        <i class="mdi mdi-progress-clock"></i>
                        Memuat
                    </span>
                </div>
            </div>
        </section>

        <section class="apotek-panel">
            <div class="apotek-filter-bar">
                <div>
                    <div class="apotek-panel-title" id="resultApotekTitle">Hasil Generate Apotek Umum</div>
                    <div class="apotek-panel-subtitle">Satu periode dan jenis hanya menyimpan satu hasil aktif.</div>
                </div>
                <div class="apotek-search input-group">
                    <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                    <input type="text" class="form-control" id="searchGenerateApotek"
                        placeholder="Cari periode / jenis / mapping...">
                </div>
            </div>
            <div class="apotek-table-wrap">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100" id="tableGenerateApotek">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Periode</th>
                                <th>Periode Data</th>
                                <th>Jenis</th>
                                <th>Mapping</th>
                                <th class="text-center">Item</th>
                                <th class="text-end">Grand Total</th>
                                <th class="text-end">Premi Bersama</th>
                                <th class="text-end">Dibagikan</th>
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

    @include("simrs.backOffice.keuangan.hitungPremi.generateApotek.modal")
@endsection

@push("scripts")
    @include("simrs.backOffice.keuangan.hitungPremi.generateApotek.jsMain")
@endpush
