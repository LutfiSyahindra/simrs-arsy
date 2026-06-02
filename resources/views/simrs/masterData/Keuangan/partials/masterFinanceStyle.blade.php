<style>
    .modal-dialog {
        overflow-y: initial !important;
    }

    .modal-body {
        max-height: calc(100vh - 180px) !important;
        overflow-y: auto;
    }

    .select2-container,
    .select2-dropdown {
        z-index: 999999 !important;
    }

    .notif-unread {
        background-color: #fff7e6 !important;
        font-weight: 600;
    }

    .finance-master-page {
        --fm-primary: #2563eb;
        --fm-primary-dark: #1d4ed8;
        --fm-success: #16a34a;
        --fm-warning: #f59e0b;
        --fm-info: #0891b2;
        --fm-ink: #172033;
        --fm-muted: #64748b;
        --fm-line: #e2e8f0;
        --fm-soft: #f8fafc;
        --fm-panel: #f4f9ff;
        color: var(--fm-ink);
    }

    .finance-master-page .breadcrumb {
        background: transparent;
        padding: 0;
        margin-bottom: 0;
        font-size: 13px;
    }

    .finance-master-page .breadcrumb-item a {
        color: var(--fm-primary);
        font-weight: 600;
    }

    .finance-master-page .breadcrumb-item.active {
        color: var(--fm-muted);
    }

    .finance-master-page .master-card {
        border: 1px solid #e5edf7;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .finance-master-page .master-panel {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 18px;
        background: linear-gradient(135deg, #f8fbff 0%, #f3fbf7 100%);
        border-bottom: 1px solid #e6edf5;
    }

    .finance-master-page .master-title-group {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    .finance-master-page .master-icon {
        width: 46px;
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 46px;
        border-radius: 8px;
        color: #fff;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18);
    }

    .finance-master-page .master-icon-primary {
        background: linear-gradient(135deg, var(--fm-primary), var(--fm-info));
    }

    .finance-master-page .master-icon-success {
        background: linear-gradient(135deg, var(--fm-success), var(--fm-info));
    }

    .finance-master-page .master-icon-warning {
        background: linear-gradient(135deg, var(--fm-warning), #ef4444);
    }

    .finance-master-page .master-icon i {
        font-size: 24px;
        line-height: 1;
    }

    .finance-master-page .master-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 4px;
        color: var(--fm-primary);
        font-size: 12px;
        font-weight: 700;
    }

    .finance-master-page .master-title {
        color: var(--fm-ink);
        font-size: 18px;
        font-weight: 700;
        line-height: 1.25;
        margin-bottom: 2px;
    }

    .finance-master-page .master-subtitle {
        color: var(--fm-muted);
        font-size: 13px;
    }

    .finance-master-page .master-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 8px;
    }

    .finance-master-page .master-search {
        width: 260px;
        max-width: 100%;
        border-radius: 8px;
        box-shadow: 0 1px 0 rgba(15, 23, 42, 0.03);
    }

    .finance-master-page .master-search .input-group-text,
    .finance-master-page .master-search .form-control {
        height: 36px;
        border-color: var(--fm-line);
        color: #334155;
    }

    .finance-master-page .master-search .input-group-text {
        border-radius: 8px 0 0 8px;
    }

    .finance-master-page .master-search .form-control {
        border-radius: 0 8px 8px 0;
    }

    .finance-master-page .master-search .form-control:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .finance-master-page .master-action-btn {
        min-width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border-radius: 6px;
        font-weight: 700;
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.08);
    }

    .finance-master-page .master-action-btn i {
        font-size: 18px;
        line-height: 1;
    }

    .finance-master-page .master-action-primary {
        background: var(--fm-primary);
        border-color: var(--fm-primary);
        color: #fff;
        padding: 0 13px;
    }

    .finance-master-page .master-action-primary:hover {
        background: var(--fm-primary-dark);
        border-color: var(--fm-primary-dark);
        color: #fff;
    }

    .finance-master-page .master-action-success {
        background: #ecfdf3;
        border-color: #bbf7d0;
        color: var(--fm-success);
    }

    .finance-master-page .master-action-success:hover {
        background: var(--fm-success);
        border-color: var(--fm-success);
        color: #fff;
    }

    .finance-master-page .master-action-warning {
        background: #fffbeb;
        border-color: #fde68a;
        color: #b45309;
    }

    .finance-master-page .master-action-warning:hover {
        background: var(--fm-warning);
        border-color: var(--fm-warning);
        color: #fff;
    }

    .finance-master-page .master-table-wrap {
        padding: 18px;
    }

    .finance-master-page .table-responsive {
        border: 1px solid var(--fm-line);
        border-radius: 8px;
        background: #fff;
    }

    .finance-master-page .master-table {
        margin-bottom: 0;
    }

    .finance-master-page .master-table thead th {
        background: var(--fm-soft);
        border-top: 0;
        border-bottom: 1px solid var(--fm-line);
        color: #475569;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        white-space: nowrap;
        padding: 13px 14px;
    }

    .finance-master-page .master-table tbody td {
        border-color: #edf2f7;
        color: #334155;
        padding: 13px 14px;
        vertical-align: middle;
    }

    .finance-master-page .master-table tbody tr:hover td {
        background: #f8fbff;
    }

    .finance-master-page .master-table .badge {
        border-radius: 6px;
        font-weight: 700;
        padding: 6px 9px;
    }

    .finance-master-page .dataTables_wrapper .dataTables_length,
    .finance-master-page .dataTables_wrapper .dataTables_info {
        color: var(--fm-muted);
        font-size: 13px;
    }

    .finance-master-page .dataTables_wrapper .form-select,
    .finance-master-page .dataTables_wrapper .form-control {
        border-color: var(--fm-line);
        border-radius: 6px;
    }

    .finance-master-page .pagination .page-link {
        border-color: var(--fm-line);
        border-radius: 6px;
        color: var(--fm-primary);
        margin: 0 2px;
    }

    .finance-master-page .page-item.active .page-link {
        background: var(--fm-primary);
        border-color: var(--fm-primary);
        color: #fff;
    }

    .finance-master-page td.dt-control::before {
        display: none !important;
    }

    .finance-master-page .btn-expand {
        width: 30px;
        height: 30px;
        border-radius: 6px;
        border: 1px solid var(--fm-line);
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        transition: all 0.2s ease;
    }

    .finance-master-page .btn-expand i {
        font-size: 16px;
        color: #475569;
    }

    .finance-master-page .btn-expand:hover {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: var(--fm-primary);
    }

    .finance-master-page .table .card {
        border: 1px solid #e8eef6 !important;
        border-radius: 8px;
        box-shadow: none !important;
    }

    .hover-row:hover {
        background: #f1f5f9;
        transition: 0.2s;
    }

    .copy-row:hover {
        background: #dcfce7;
        cursor: pointer;
    }

    @media (max-width: 767.98px) {
        .finance-master-page .master-panel {
            align-items: stretch;
        }

        .finance-master-page .master-title-group,
        .finance-master-page .master-actions,
        .finance-master-page .master-search {
            width: 100%;
        }

        .finance-master-page .master-actions {
            justify-content: flex-start;
        }

        .finance-master-page .master-action-primary {
            flex: 1 1 auto;
        }
    }
</style>
