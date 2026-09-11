@php
    $currentUser = auth()->user();
    $pageTitle = $pageTitle ?? 'Dashboard';
    $faviconUrl = ! empty($appSettings?->favicon) && file_exists(public_path('assets/favicon/'.$appSettings->favicon))
        ? asset('assets/favicon/'.$appSettings->favicon)
        : null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }} - {{ $appSettings->nama_instansi ?? config('app.name') }}</title>
    @if ($faviconUrl)
        <link rel="icon" href="{{ $faviconUrl }}">
        <link rel="shortcut icon" href="{{ $faviconUrl }}">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @yield('head')
    <style>
        :root {
            --green-900: #0D5C3F;
            --green-800: #065F46;
            --green-700: #0F766E;
            --green-100: #D1FAE5;
            --green-50: #ECFDF5;
            --slate-900: #0F172A;
            --slate-800: #1E293B;
            --slate-600: #475569;
            --slate-500: #64748B;
            --slate-300: #CBD5E1;
            --slate-200: #E2E8F0;
            --slate-100: #F1F5F9;
            --slate-50: #F8FAFC;
            --blue-700: #1D4ED8;
            --blue-50: #EFF6FF;
            --amber-700: #B45309;
            --amber-50: #FFFBEB;
            --red-700: #B91C1C;
            --red-50: #FEF2F2;
            --purple-700: #7E22CE;
            --purple-50: #F3E8FF;
            --shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.06);
            --shadow-md: 0 12px 30px rgba(15, 23, 42, 0.14);
            --radius-lg: 16px;
            --radius-md: 12px;
            --radius-sm: 10px;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: #F3F4F6;
            color: #1E293B;
        }
        a { color: inherit; }
        .sidebar {
            width: 220px;
            background: #0D5C3F;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.25s ease;
            transform: translateX(-100%);
            box-shadow: 2px 0 12px rgba(0,0,0,0.08);
        }
        .sidebar.open { transform: translateX(0); }
        .sidebar::-webkit-scrollbar { width: 3px; }
        .sidebar::-webkit-scrollbar-track { background: #0A4D35; }
        .sidebar::-webkit-scrollbar-thumb { background: #2C7A5E; border-radius: 3px; }
        .sidebar-user {
            padding: 20px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #1A6B4D;
            margin-bottom: 12px;
        }
        .user-avatar-sm {
            width: 42px;
            height: 42px;
            background: #FFFFFF;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #0D5C3F;
            overflow: hidden;
            flex-shrink: 0;
        }
        .user-avatar-sm img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #FFFFFF;
            padding: 4px;
        }
        .user-avatar-sm i {
            font-size: 18px;
        }
        .user-name-sm { font-size: 13px; font-weight: 500; color: #FFFFFF; }
        .user-role-sm { font-size: 10px; color: #9CD4B8; }
        .sidebar-nav {
            list-style: none;
            padding: 0 8px;
            margin: 0;
        }
        .nav-item { margin-bottom: 2px; }
        .nav-link, .nav-button {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 10px 10px;
            color: #D1FAE5;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            border: none;
            background: transparent;
            cursor: pointer;
        }
        .nav-link i, .nav-button i { width: 20px; font-size: 14px; color: #9CD4B8; }
        .nav-link span, .nav-button span { flex: 1; text-align: left; }
        .nav-link:hover, .nav-button:hover { background: #1A6B4D; color: #FFFFFF; }
        .nav-link:hover i, .nav-button:hover i { color: #FFFFFF; }
        .nav-link.active { background: #FFFFFF; color: #0D5C3F; }
        .nav-link.active i { color: #0D5C3F; }
        .dropdown-icon { font-size: 10px; margin-left: auto; transition: transform 0.2s; }
        .dropdown-toggle[aria-expanded="true"] .dropdown-icon { transform: rotate(180deg); }
        .submenu {
            list-style: none;
            padding-left: 30px;
            margin: 4px 0 6px 0;
            display: none;
        }
        .submenu.show { display: block; }
        .submenu li a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            color: #D1FAE5;
            text-decoration: none;
            border-radius: 6px;
            font-size: 12px;
        }
        .submenu li a:hover { background: #1A6B4D; color: #FFFFFF; }
        .submenu li a.active { background: #FFFFFF; color: #0D5C3F; }
        .nav-divider {
            height: 1px;
            background: #1A6B4D;
            margin: 10px 8px;
        }
        .logout-link { color: #FCA5A5 !important; }
        .logout-link i { color: #FCA5A5 !important; }
        .logout-link:hover { background: #991B1B !important; color: #FFFFFF !important; }
        .logout-link.logout-inline-loading,
        .logout-link.logout-inline-loading:hover {
            background: #991B1B !important;
            color: #FFFFFF !important;
            cursor: wait;
            opacity: 1;
        }
        .logout-link.logout-inline-loading i,
        .logout-link.logout-inline-loading span {
            color: #FFFFFF !important;
        }
        .mobile-menu-btn {
            position: fixed;
            top: 16px;
            left: 16px;
            z-index: 1100;
            background: #0D5C3F;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            color: white;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transition: left 0.25s ease, background-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
        }
        .mobile-menu-btn.is-open {
            background: #FFFFFF;
            color: #0D5C3F;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.18);
        }
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        .sidebar-overlay.show { display: block; }
        .main-content {
            margin-left: 0;
            padding: 84px 20px 20px;
            min-height: 100vh;
            background: #F1F5F9;
        }
        .top-bar {
            background: #FFFFFF;
            padding: 0 20px;
            min-height: 64px;
            margin: 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 900;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            border-radius: 0;
            border-bottom: 1px solid #E2E8F0;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
        }
        .top-bar-left,
        .top-bar-right {
            display: flex;
            align-items: center;
            min-width: 0;
        }
        .top-bar-right { margin-left: auto; }
        .page-title {
            font-size: 18px;
            font-weight: 600;
            color: #1E293B;
            margin: 0;
            line-height: 1.2;
        }
        .user-info { display: flex; align-items: center; gap: 10px; }
        .header-datetime {
            display: grid;
            gap: 2px;
            text-align: right;
            line-height: 1.1;
            margin-right: 4px;
        }
        .header-time {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: 3px;
            font-size: 15px;
            font-weight: 700;
            color: #1E293B;
            font-variant-numeric: tabular-nums;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }
        .time-part,
        .time-separator {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }
        .time-zone {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 2px;
            font-size: 11px;
            font-weight: 700;
            color: #64748B;
            letter-spacing: 0.04em;
            line-height: 1;
        }
        .time-separator {
            margin: 0 -1px;
            transform: translateY(-0.5px);
        }
        .header-date {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
            font-size: 11px;
            color: #64748B;
            white-space: nowrap;
        }
        .header-time i,
        .header-date i {
            font-size: 11px;
            opacity: .85;
            flex-shrink: 0;
        }
        .user-avatar {
            width: 34px;
            height: 34px;
            background: #065F46;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            flex-shrink: 0;
        }
        .user-meta {
            display: grid;
            gap: 2px;
            line-height: 1.15;
        }
        .user-name { font-size: 13px; font-weight: 600; color: #1E293B; }
        .user-role { font-size: 10px; color: #64748B; }
        .card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .card-header {
            padding: 14px 20px;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .card-header h3 { font-size: 14px; font-weight: 600; margin: 0; }
        .card-body { padding: 20px; }
        .alert {
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 12px;
            margin-bottom: 14px;
        }
        .alert-error { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }
        .alert-success { background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; }
        .alert-warning { background: #FFFBEB; color: #92400E; border: 1px solid #FDE68A; }
        .alert-info { background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; }
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .page-subtitle {
            font-size: 14px;
            font-weight: 500;
            color: #334155;
            margin: 0;
        }
        .page-description {
            font-size: 12px;
            color: #64748B;
            margin: 4px 0 0;
        }
        .button-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn,
        .btn-inline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
        }
        .btn:hover,
        .btn-inline:hover { text-decoration: none; }
        .btn-primary { background: #065F46; color: white; }
        .btn-primary:hover { background: #0D7C5A; color: white; }
        .btn-info { background: #3B82F6; color: white; }
        .btn-info:hover { background: #2563EB; color: white; }
        .btn-success { background: #10B981; color: white; }
        .btn-success:hover { background: #059669; color: white; }
        .btn-warning { background: #D97706; color: white; }
        .btn-warning:hover { background: #B45309; color: white; }
        .btn-danger { background: #DC2626; color: white; }
        .btn-danger:hover { background: #B91C1C; color: white; }
        .btn-secondary { background: #E2E8F0; color: #334155; }
        .btn-secondary:hover { background: #CBD5E1; color: #334155; }
        .btn-outline {
            background: white;
            color: #475569;
            border: 1px solid #E2E8F0;
        }
        .btn-outline:hover { background: #F1F5F9; color: #475569; }
        .info-zone,
        .info-banner,
        .summary-banner {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 12px;
            margin-bottom: 16px;
        }
        .info-zone {
            background: linear-gradient(135deg, var(--green-800) 0%, #0D7C5A 100%);
            color: white;
            padding: 10px 16px;
        }
        .info-zone .separator { color: rgba(255,255,255,0.5); }
        .info-banner { background: var(--blue-50); color: var(--blue-700); }
        .summary-banner { background: var(--green-50); color: var(--green-800); }
        .warning-banner { background: var(--amber-50); color: #92400E; }
        .danger-banner { background: var(--red-50); color: var(--red-700); }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .stat-card-body {
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--green-800);
            line-height: 1.1;
            margin-bottom: 4px;
        }
        .stat-label {
            font-size: 12px;
            color: var(--slate-500);
        }
        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--green-50);
            color: var(--green-800);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }
        .content-grid-2,
        .content-grid-3 {
            display: grid;
            gap: 16px;
        }
        .content-grid-2 {
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        }
        .content-grid-3 {
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }
        .form-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }
        .form-row {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }
        .form-group {
            display: block;
            margin-bottom: 14px;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 12px;
            font-weight: 500;
            color: #374151;
        }
        .required { color: var(--red-700); }
        .form-control,
        .form-select,
        .filter-input,
        .form-control-file {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
            color: #334155;
            background: white;
        }
        .form-control:focus,
        .form-select:focus,
        .filter-input:focus {
            outline: none;
            border-color: #065F46;
            box-shadow: 0 0 0 2px rgba(6,95,70,0.1);
        }
        .form-control[readonly],
        .form-control:disabled,
        .form-select[readonly],
        .form-select:disabled,
        .filter-input[readonly],
        .filter-input:disabled,
        textarea.form-control[readonly],
        textarea.form-control:disabled {
            background: #F1F5F9;
            border-color: #CBD5E1;
            color: #64748B;
            cursor: not-allowed;
            box-shadow: none;
            opacity: 1;
        }
        .form-control[readonly]::placeholder,
        .form-control:disabled::placeholder,
        .form-select[readonly]::placeholder,
        .form-select:disabled::placeholder,
        .filter-input[readonly]::placeholder,
        .filter-input:disabled::placeholder,
        textarea.form-control[readonly]::placeholder,
        textarea.form-control:disabled::placeholder {
            color: #94A3B8;
        }
        .form-control.is-invalid,
        .form-select.is-invalid,
        .filter-input.is-invalid,
        .form-input.is-invalid,
        .form-textarea.is-invalid,
        .form-control-file.is-invalid {
            border-color: #DC2626;
            box-shadow: 0 0 0 2px rgba(220,38,38,0.08);
        }
        .invalid-feedback {
            display: block;
            margin-top: 6px;
            font-size: 11px;
            color: #B91C1C;
        }
        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }
        input[type="checkbox"] {
            width: 14px;
            height: 14px;
        }
        .form-text {
            display: block;
            margin-top: 4px;
            font-size: 10px;
            color: #64748B;
        }
        .form-actions,
        .modal-footer-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 16px;
        }
        .form-note {
            font-size: 12px;
            color: var(--slate-500);
        }
        .info-note {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 10px;
            margin-top: 12px;
            border-radius: 8px;
            background: #FEF3C7;
            color: #92400E;
            font-size: 11px;
        }
        .filter-bar {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            padding: 12px 20px;
            margin-bottom: 20px;
        }
        .filter-form {
            display: flex;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
        }
        .filter-group {
            display: grid;
            gap: 4px;
            min-width: 140px;
        }
        .filter-group label {
            font-size: 11px;
            font-weight: 500;
            color: #64748B;
        }
        .btn-reset,
        .btn-export {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
        }
        .btn-reset {
            padding: 8px 20px;
            background: #E2E8F0;
            color: #475569;
        }
        .btn-export.excel {
            background: #10B981;
            color: white;
        }
        .btn-export.pdf {
            background: #EF4444;
            color: white;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .table-bordered {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .table-bordered th,
        .table-bordered td {
            border: 1px solid #D1D5DB;
            padding: 8px 8px;
            vertical-align: top;
        }
        .table-bordered thead tr,
        .table-header {
            background: #F8FAFC;
        }
        .table-bordered th {
            font-weight: 600;
            color: #065F46;
            text-align: left;
        }
        .table-bordered td { color: #334155; }
        .table-bordered tbody tr:hover { background: #F8FAFC; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-muted { color: var(--slate-500); }
        .total-data,
        .data-count {
            font-size: 11px;
            color: #64748B;
            background: #F1F5F9;
            padding: 4px 10px;
            border-radius: 20px;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 600;
            white-space: nowrap;
        }
        .badge-success { background: var(--green-100); color: var(--green-800); }
        .badge-warning { background: #FEF3C7; color: var(--amber-700); }
        .badge-danger { background: #FEE2E2; color: var(--red-700); }
        .badge-info { background: var(--blue-50); color: var(--blue-700); }
        .badge-purple { background: var(--purple-50); color: var(--purple-700); }
        .action-buttons {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .action-buttons form {
            margin: 0;
        }
        .action-buttons .btn,
        .action-buttons .btn-inline,
        .table-bordered td .btn,
        .table-bordered td .btn-inline {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
            gap: 4px;
        }
        .action-buttons .btn-info,
        .table-bordered td .btn-info {
            background: #EFF6FF;
            color: #3B82F6;
        }
        .action-buttons .btn-info:hover,
        .table-bordered td .btn-info:hover {
            background: #3B82F6;
            color: white;
        }
        .action-buttons .btn-warning,
        .table-bordered td .btn-warning {
            background: #FEF3C7;
            color: #D97706;
        }
        .action-buttons .btn-warning:hover,
        .table-bordered td .btn-warning:hover {
            background: #D97706;
            color: white;
        }
        .action-buttons .btn-danger,
        .table-bordered td .btn-danger {
            background: #FEF2F2;
            color: #EF4444;
        }
        .action-buttons .btn-danger:hover,
        .table-bordered td .btn-danger:hover {
            background: #EF4444;
            color: white;
        }
        .action-buttons .btn-success,
        .table-bordered td .btn-success {
            background: #D1FAE5;
            color: #065F46;
        }
        .action-buttons .btn-success:hover,
        .table-bordered td .btn-success:hover {
            background: #065F46;
            color: white;
        }
        .action-buttons .btn-secondary,
        .table-bordered td .btn-secondary {
            background: #E2E8F0;
            color: #334155;
        }
        .action-buttons .btn-secondary:hover,
        .table-bordered td .btn-secondary:hover {
            background: #CBD5E1;
            color: #334155;
        }
        .section-stack {
            display: grid;
            gap: 16px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.show { display: flex; }
        .modal-dialog {
            width: 90%;
            max-width: 500px;
        }
        .modal-dialog-confirm {
            max-width: 430px;
            transform: translateY(14px) scale(0.98);
            opacity: 0;
            transition: transform 0.18s ease, opacity 0.18s ease;
        }
        .modal.show .modal-dialog-confirm {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
        .modal-content {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .modal-header,
        .modal-footer {
            padding: 16px 20px;
            background: #F8FAFC;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            border-bottom: 1px solid #E2E8F0;
        }
        .modal-footer {
            border-bottom: none;
            border-top: 1px solid #E2E8F0;
            justify-content: flex-end;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }
        .modal-body {
            padding: 20px;
        }
        .modal-close {
            border: none;
            background: none;
            color: #94A3B8;
            font-size: 22px;
            cursor: pointer;
            text-decoration: none;
        }
        .modal-close:hover { color: #DC2626; }
        body.confirm-modal-open {
            overflow: hidden;
        }
        #panelConfirmModal {
            z-index: 1700;
            padding: 16px;
        }
        .confirm-modal-content {
            border: 1px solid #E2E8F0;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.2);
        }
        .confirm-modal-header {
            padding: 18px 20px 0;
            background: #FFFFFF;
            border-bottom: none;
            align-items: flex-start;
        }
        .confirm-modal-header-main {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            flex: 1;
            min-width: 0;
        }
        .confirm-modal-head-copy {
            display: grid;
            gap: 8px;
            min-width: 0;
            flex: 1;
        }
        .confirm-modal-title-box {
            display: grid;
            gap: 4px;
        }
        .confirm-modal-title {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.2;
            color: #0F172A;
        }
        .confirm-modal-subtitle {
            margin: 0;
            font-size: 12px;
            line-height: 1.5;
            color: #64748B;
        }
        .confirm-modal-body {
            display: grid;
            gap: 12px;
            padding-top: 16px;
        }
        .confirm-modal-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 16px;
            border: 1px solid #E2E8F0;
            background: #F8FAFC;
            color: #475569;
        }
        .confirm-modal-panel {
            display: grid;
            gap: 6px;
            padding: 13px 14px;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            background: #FFFFFF;
        }
        .confirm-modal-panel-soft {
            background: #F8FAFC;
        }
        .confirm-modal-label {
            margin: 0;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #64748B;
        }
        .confirm-modal-message {
            margin: 0;
            font-size: 13px;
            line-height: 1.65;
            color: #1E293B;
        }
        .confirm-modal-note {
            margin: 0;
            font-size: 11px;
            line-height: 1.65;
            color: #64748B;
        }
        .confirm-modal-footer {
            padding: 12px 20px;
        }
        #panelConfirmModal[data-variant="danger"] .confirm-modal-icon {
            background: #FEF2F2;
            color: #DC2626;
            border-color: #FECACA;
        }
        #panelConfirmModal[data-variant="warning"] .confirm-modal-icon {
            background: #FFFBEB;
            color: #D97706;
            border-color: #FDE68A;
        }
        #panelConfirmModal[data-variant="success"] .confirm-modal-icon {
            background: #ECFDF5;
            color: #059669;
            border-color: #A7F3D0;
        }
        #panelConfirmModal[data-variant="info"] .confirm-modal-icon,
        #panelConfirmModal[data-variant="primary"] .confirm-modal-icon {
            background: #EFF6FF;
            color: #1D4ED8;
            border-color: #BFDBFE;
        }
        .toast-wrapper {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1600;
            display: grid;
            gap: 12px;
        }
        .toast {
            min-width: 260px;
            background: white;
            border-radius: 12px;
            border-left: 4px solid;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateX(120%);
            transition: transform 0.3s ease;
        }
        .toast.show { transform: translateX(0); }
        .toast.success { border-left-color: #10B981; }
        .toast.error { border-left-color: #EF4444; }
        .toast.warning { border-left-color: #F59E0B; }
        .toast.info { border-left-color: #3B82F6; }
        .toast-content {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 20px;
            font-size: 12px;
            color: #334155;
        }
        .toast.success i { color: #10B981; }
        .toast.error i { color: #EF4444; }
        .toast.warning i { color: #F59E0B; }
        .toast.info i { color: #3B82F6; }
        .toast-close {
            border: none;
            background: transparent;
            color: var(--slate-500);
            font-size: 16px;
            cursor: pointer;
            padding: 0;
            margin-left: auto;
        }
        .btn-loading,
        .action-loading,
        .btn[disabled],
        .btn-inline[disabled],
        button[disabled] {
            opacity: 0.7;
            cursor: wait;
        }
        .action-loading {
            pointer-events: none;
        }
        .btn-loading-content {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-spinner {
            width: 14px;
            height: 14px;
            border: 2px solid currentColor;
            border-right-color: transparent;
            border-radius: 999px;
            animation: button-spin 0.7s linear infinite;
            flex-shrink: 0;
        }
        @keyframes button-spin {
            to { transform: rotate(360deg); }
        }
        .empty-state {
            padding: 14px;
            text-align: center;
            color: var(--slate-500);
        }
        .pagination-wrap {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            padding: 14px 20px;
            border-top: 1px solid #E2E8F0;
            background: #FFFFFF;
        }
        .pagination-summary {
            font-size: 12px;
            color: #64748B;
        }
        .pagination {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .page-link,
        .page-link-disabled,
        .page-link-active {
            min-width: 36px;
            height: 36px;
            padding: 0 12px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid #E2E8F0;
        }
        .page-link {
            background: #FFFFFF;
            color: #475569;
        }
        .page-link:hover {
            background: #F8FAFC;
            color: #065F46;
            border-color: #CBD5E1;
        }
        .page-link-active {
            background: #065F46;
            color: #FFFFFF;
            border-color: #065F46;
        }
        .page-link-disabled {
            background: #F8FAFC;
            color: #94A3B8;
            cursor: not-allowed;
        }
        @media (max-width: 640px) {
            .pagination-wrap {
                padding: 12px 16px;
                justify-content: center;
            }
            .pagination-summary {
                width: 100%;
                text-align: center;
            }
        }
        .fragment-loading-host {
            position: relative;
        }
        .table-responsive.fragment-loading-host,
        .table-container.fragment-loading-host,
        .table-scroll.fragment-loading-host {
            min-height: 220px;
        }
        .fragment-loading-overlay {
            position: absolute;
            inset: 0;
            z-index: 40;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 18px 12px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(1px);
            border-radius: 14px;
        }
        .table-responsive > .fragment-loading-overlay,
        .table-container > .fragment-loading-overlay,
        .table-scroll > .fragment-loading-overlay {
            border-radius: 0;
        }
        .fragment-loading-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 0;
            border: none;
            box-shadow: none;
            background: transparent;
            color: #065F46;
            font-size: 12px;
            font-weight: 600;
        }
        .fragment-loading-overlay .btn-spinner {
            width: 18px;
            height: 18px;
            color: #065F46;
        }
        @media (min-width: 769px) {
            .sidebar { transform: translateX(0); }
            .mobile-menu-btn { display: none; }
            .main-content { margin-left: 220px; }
            .top-bar { left: 220px; }
        }
        @media (max-width: 768px) {
            body.sidebar-mobile-open { overflow: hidden; }
            .mobile-menu-btn {
                top: 8px;
                left: 12px;
            }
            body.sidebar-mobile-open .mobile-menu-btn {
                left: min(168px, calc(100vw - 52px));
            }
            .main-content { margin-left: 0; padding: 72px 16px 16px; }
            .top-bar {
                top: 0;
                min-height: 56px;
                padding: 0 16px 0 60px;
                left: 0;
                right: 0;
            }
            .page-title { font-size: 17px; }
            .header-datetime { display: none; }
            .user-avatar {
                width: 30px;
                height: 30px;
                border-radius: 7px;
                font-size: 12px;
            }
            .user-name { font-size: 12px; }
            .user-role { font-size: 9px; }
            .toast-wrapper {
                left: 16px;
                right: 16px;
                top: 16px;
            }
            .toast { min-width: 0; }
        }
        @yield('styles')
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
            .content-grid-2,
            .content-grid-3 {
                grid-template-columns: 1fr !important;
            }
        }
        @media (max-width: 768px) {
            body {
                overflow-x: hidden;
            }
            .main-content {
                padding: 72px 14px 14px !important;
            }
            .card-body,
            .panel-body {
                padding: 16px !important;
            }
            .page-header,
            .card-header,
            .panel-head,
            .portal-card-header,
            .device-card-header,
            .employee-card-header,
            .resign-card-header,
            .device-page-header {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 12px !important;
            }
            .page-header > *,
            .card-header > *,
            .panel-head > *,
            .portal-card-header > *,
            .device-card-header > *,
            .employee-card-header > *,
            .resign-card-header > *,
            .device-page-header > * {
                width: 100%;
                min-width: 0;
            }
            .page-header-actions,
            .card-header-actions,
            .panel-head-actions,
            .device-card-header-tools,
            .employee-card-header-tools,
            .resign-card-header-tools,
            .filter-form,
            .filter-row,
            .filter-toolbar,
            .device-toolbar,
            .shift-filter-row,
            .history-modal-filter {
                width: 100% !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
                justify-content: flex-start !important;
                gap: 12px !important;
                margin-left: 0 !important;
            }
            .button-group,
            .export-buttons {
                width: 100% !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 10px !important;
            }
            .button-group .btn,
            .button-group .btn-inline,
            .button-group a,
            .page-header-actions .btn,
            .page-header-actions .btn-inline,
            .page-header-actions a,
            .card-header-actions .btn,
            .card-header-actions .btn-inline,
            .card-header-actions a,
            .panel-head-actions .btn,
            .panel-head-actions .btn-inline,
            .panel-head-actions a,
            .export-buttons .btn,
            .export-buttons a {
                width: 100% !important;
                justify-content: center !important;
            }
            .filter-group,
            .employee-card-header-form .filter-group,
            .resign-card-header-form .filter-group,
            .device-card-header-form .filter-group {
                width: 100% !important;
                min-width: 0 !important;
            }
            .filter-input,
            .filter-select,
            .form-control,
            .form-select {
                width: 100% !important;
                min-width: 0 !important;
            }
            .stats-grid,
            .schedule-grid,
            .notice-grid,
            .bottom-grid,
            .quick-menu,
            .portal-grid {
                grid-template-columns: 1fr !important;
            }
            .form-grid,
            .form-row,
            .detail-form-grid,
            .detail-form-grid.full-3,
            .izin-form-grid,
            .leave-modal-grid {
                grid-template-columns: 1fr !important;
            }
            .table-responsive {
                width: 100%;
                overflow-x: auto !important;
                overflow-y: hidden !important;
                -webkit-overflow-scrolling: touch;
            }
            .table-responsive > table,
            .table-responsive > .table-bordered,
            .table-responsive > .gaji-table {
                min-width: max(720px, 100%) !important;
            }
            .table-bordered {
                font-size: 11px !important;
            }
            .table-bordered th,
            .table-bordered td {
                padding: 7px 6px !important;
            }
            .action-buttons {
                justify-content: flex-start !important;
            }
            .sticky-col-left,
            .sticky-col-right {
                position: static !important;
                left: auto !important;
                right: auto !important;
                z-index: auto !important;
                background: inherit !important;
                box-shadow: none !important;
            }
            .sticky-col-left::after,
            .sticky-col-right::before {
                display: none !important;
            }
            .modal {
                padding: 12px !important;
                align-items: flex-start !important;
                overflow-y: auto !important;
            }
            .modal-dialog,
            .modal-dialog-confirm,
            .modal-dialog-employee,
            .modal-dialog-resign,
            .modal-dialog-clearance,
            .modal-dialog-kasbon-history,
            .modal-dialog-izin,
            .payroll-adjustment-modal-dialog,
            .payroll-breakdown-modal-dialog,
            .tax-detail-modal-dialog,
            .detail-modal-dialog,
            .leave-modal-dialog {
                width: calc(100vw - 24px) !important;
                max-width: calc(100vw - 24px) !important;
            }
            .modal-content,
            .confirm-modal-content {
                max-height: calc(100vh - 24px) !important;
                display: flex;
                flex-direction: column;
            }
            .modal-body,
            .leave-modal-body,
            .payroll-adjustment-list {
                overflow: auto !important;
                -webkit-overflow-scrolling: touch;
            }
            .modal-footer,
            .confirm-modal-footer,
            .modal-footer-actions,
            .form-actions {
                flex-direction: column !important;
                align-items: stretch !important;
                justify-content: flex-start !important;
            }
            .modal-footer .btn,
            .modal-footer a,
            .confirm-modal-footer .btn,
            .confirm-modal-footer a,
            .modal-footer-actions .btn,
            .modal-footer-actions a,
            .form-actions .btn,
            .form-actions a {
                width: 100% !important;
                justify-content: center !important;
            }
            .total-data,
            .data-count {
                align-self: flex-start;
            }
        }
        @media (max-width: 640px) {
            .main-content {
                padding: 72px 12px 12px !important;
            }
            .top-bar {
                padding-right: 12px !important;
            }
            .page-title {
                font-size: 16px !important;
            }
            .card-body,
            .panel-body {
                padding: 14px !important;
            }
            .stat-card-body {
                padding: 14px !important;
                align-items: flex-start !important;
            }
            .stat-value {
                font-size: 22px !important;
            }
            .stat-icon {
                width: 40px !important;
                height: 40px !important;
                font-size: 18px !important;
            }
            .table-responsive > table,
            .table-responsive > .table-bordered,
            .table-responsive > .gaji-table {
                min-width: max(640px, 100%) !important;
            }
        }
    </style>
</head>
<body>
    <button class="mobile-menu-btn" id="mobileMenuBtn">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    @include(($panel ?? 'admin') === 'karyawan' ? 'partials.sidebars.karyawan' : 'partials.sidebars.admin')

    <div class="modal" id="panelConfirmModal" aria-hidden="true" data-variant="primary">
        <div class="modal-dialog modal-dialog-confirm">
            <div class="modal-content confirm-modal-content" role="dialog" aria-modal="true" aria-labelledby="panelConfirmTitle" aria-describedby="panelConfirmMessage">
                <div class="modal-header confirm-modal-header">
                    <div class="confirm-modal-header-main">
                        <div class="confirm-modal-icon" id="panelConfirmIcon">
                            <i class="fas fa-circle-question"></i>
                        </div>
                        <div class="confirm-modal-head-copy">
                            <div class="confirm-modal-title-box">
                                <h3 class="confirm-modal-title" id="panelConfirmTitle">Konfirmasi Aksi</h3>
                                <p class="confirm-modal-subtitle" id="panelConfirmSubtitle">Periksa tindakan ini sebelum dilanjutkan.</p>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="modal-close" id="panelConfirmClose" aria-label="Tutup">&times;</button>
                </div>
                <div class="modal-body confirm-modal-body">
                    <div class="confirm-modal-panel">
                        <p class="confirm-modal-label">Aksi Yang Akan Dijalankan</p>
                        <p class="confirm-modal-message" id="panelConfirmMessage">Lanjutkan proses ini?</p>
                    </div>
                    <div class="confirm-modal-panel confirm-modal-panel-soft" id="panelConfirmNoteWrap">
                        <p class="confirm-modal-label">Keterangan</p>
                        <p class="confirm-modal-note" id="panelConfirmNote">Pastikan tindakan ini memang ingin dijalankan.</p>
                    </div>
                </div>
                <div class="modal-footer confirm-modal-footer">
                    <button type="button" class="btn btn-secondary" id="panelConfirmCancel">Batal</button>
                    <button type="button" class="btn btn-primary" id="panelConfirmApprove">Ya, lanjutkan</button>
                </div>
            </div>
        </div>
    </div>

    <main class="main-content">
        @include('partials.panel-header', ['pageTitle' => $pageTitle, 'currentUser' => $currentUser])

        @include('partials.flash')

        @yield('content')
    </main>

    <script>
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const mobileBtnIcon = mobileBtn?.querySelector('i');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        function syncMobileSidebarState(isOpen) {
            const mobileViewport = window.innerWidth <= 768;
            const shouldOpen = Boolean(isOpen) && mobileViewport;

            if (sidebar) {
                sidebar.classList.toggle('open', shouldOpen);
            }

            if (overlay) {
                overlay.classList.toggle('show', shouldOpen);
            }

            document.body.classList.toggle('sidebar-mobile-open', shouldOpen);

            if (mobileBtn) {
                mobileBtn.classList.toggle('is-open', shouldOpen);
                mobileBtn.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
                mobileBtn.setAttribute('aria-label', shouldOpen ? 'Tutup menu' : 'Buka menu');
            }

            if (mobileBtnIcon) {
                mobileBtnIcon.className = shouldOpen ? 'fas fa-xmark' : 'fas fa-bars';
            }
        }

        if (mobileBtn) {
            mobileBtn.addEventListener('click', function () {
                syncMobileSidebarState(!sidebar?.classList.contains('open'));
            });
        }

        if (overlay) {
            overlay.addEventListener('click', function () {
                syncMobileSidebarState(false);
            });
        }

        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) {
                syncMobileSidebarState(false);
            }
        });

        syncMobileSidebarState(false);

        document.querySelectorAll('.dropdown-toggle').forEach(function (toggle) {
            toggle.addEventListener('click', function (event) {
                event.preventDefault();
                const target = document.getElementById(this.dataset.target);
                if (! target) {
                    return;
                }

                target.classList.toggle('show');
                this.setAttribute('aria-expanded', target.classList.contains('show') ? 'true' : 'false');
            });
        });

        window.panelToast = {
            show(type, message) {
                if (! message) {
                    return;
                }

                const icons = {
                    success: 'fa-check-circle',
                    error: 'fa-exclamation-circle',
                    warning: 'fa-triangle-exclamation',
                    info: 'fa-circle-info',
                };
                let wrapper = document.getElementById('toastWrapper');

                if (! wrapper) {
                    wrapper = document.createElement('div');
                    wrapper.id = 'toastWrapper';
                    wrapper.className = 'toast-wrapper';
                    document.body.appendChild(wrapper);
                }

                const toast = document.createElement('div');
                toast.className = `toast ${type || 'info'}`;
                toast.innerHTML = `
                    <div class="toast-content">
                        <i class="fas ${icons[type] || icons.info}"></i>
                        <div class="toast-message"></div>
                        <button type="button" class="toast-close">&times;</button>
                    </div>
                `;
                toast.querySelector('.toast-message').textContent = message;
                toast.querySelector('.toast-close').addEventListener('click', function () {
                    toast.classList.remove('show');
                    window.setTimeout(function () {
                        toast.remove();
                    }, 300);
                });

                wrapper.appendChild(toast);
                window.requestAnimationFrame(function () {
                    toast.classList.add('show');
                });
                window.setTimeout(function () {
                    toast.classList.remove('show');
                    window.setTimeout(function () {
                        toast.remove();
                    }, 300);
                }, 4200);
            },
        };

        window.panelConfirm = {
            modal: document.getElementById('panelConfirmModal'),
            title: document.getElementById('panelConfirmTitle'),
            subtitle: document.getElementById('panelConfirmSubtitle'),
            message: document.getElementById('panelConfirmMessage'),
            note: document.getElementById('panelConfirmNote'),
            noteWrap: document.getElementById('panelConfirmNoteWrap'),
            icon: document.querySelector('#panelConfirmIcon i'),
            approveButton: document.getElementById('panelConfirmApprove'),
            cancelButton: document.getElementById('panelConfirmCancel'),
            closeButton: document.getElementById('panelConfirmClose'),
            activeResolver: null,
            lastFocusedElement: null,
            initialized: false,
            variants: {
                primary: {
                    icon: 'fa-circle-question',
                    buttonClass: 'btn-primary',
                    subtitle: 'Periksa tindakan ini sebelum dilanjutkan.',
                },
                info: {
                    icon: 'fa-circle-info',
                    buttonClass: 'btn-info',
                    subtitle: 'Aksi ini membutuhkan konfirmasi Anda.',
                },
                success: {
                    icon: 'fa-circle-check',
                    buttonClass: 'btn-success',
                    subtitle: 'Tindakan ini akan menyetujui data terkait.',
                },
                warning: {
                    icon: 'fa-rotate-left',
                    buttonClass: 'btn-warning',
                    subtitle: 'Pastikan perubahan ini memang ingin dijalankan.',
                },
                danger: {
                    icon: 'fa-trash-can',
                    buttonClass: 'btn-danger',
                    subtitle: 'Tindakan ini dapat menghapus atau menolak data.',
                },
            },

            initialize() {
                if (this.initialized || ! this.modal) {
                    return;
                }

                this.cancelButton?.addEventListener('click', () => this.hide(false));
                this.closeButton?.addEventListener('click', () => this.hide(false));
                this.approveButton?.addEventListener('click', () => this.hide(true));
                this.modal.addEventListener('click', (event) => {
                    if (event.target === this.modal) {
                        this.hide(false);
                    }
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && this.modal.classList.contains('show')) {
                        this.hide(false);
                    }
                });

                this.initialized = true;
            },

            applyVariant(variant, iconClass = null, subtitleText = null) {
                const currentVariant = this.variants[variant] ? variant : 'primary';
                const variantConfig = this.variants[currentVariant];

                this.modal.dataset.variant = currentVariant;
                this.icon.className = `fas ${iconClass || variantConfig.icon}`;
                this.subtitle.textContent = subtitleText || variantConfig.subtitle;
                this.approveButton.classList.remove('btn-primary', 'btn-info', 'btn-success', 'btn-warning', 'btn-danger');
                this.approveButton.classList.add(variantConfig.buttonClass);
            },

            show(options = {}) {
                if (! this.modal) {
                    return Promise.resolve(false);
                }

                if (this.activeResolver) {
                    this.hide(false);
                }

                this.initialize();

                this.lastFocusedElement = document.activeElement instanceof HTMLElement
                    ? document.activeElement
                    : null;

                this.title.textContent = options.title || 'Konfirmasi Aksi';
                this.message.textContent = options.message || 'Lanjutkan proses ini?';
                this.note.textContent = options.note || 'Pastikan tindakan ini memang ingin dijalankan.';
                this.noteWrap.hidden = ! this.note.textContent;
                this.cancelButton.textContent = options.cancelText || 'Batal';
                this.approveButton.textContent = options.confirmText || 'Ya, lanjutkan';
                this.applyVariant(
                    options.variant || 'primary',
                    options.icon || null,
                    options.subtitle || null
                );

                this.modal.classList.add('show');
                this.modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('confirm-modal-open');

                window.setTimeout(() => {
                    this.approveButton?.focus();
                }, 10);

                return new Promise((resolve) => {
                    this.activeResolver = resolve;
                });
            },

            hide(confirmed) {
                if (! this.modal) {
                    return;
                }

                this.modal.classList.remove('show');
                this.modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('confirm-modal-open');

                if (this.lastFocusedElement && typeof this.lastFocusedElement.focus === 'function') {
                    this.lastFocusedElement.focus();
                }

                this.lastFocusedElement = null;

                if (! this.activeResolver) {
                    return;
                }

                const resolver = this.activeResolver;
                this.activeResolver = null;
                resolver(Boolean(confirmed));
            },
        };

        window.panelAjax = {
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            autoSubmitTimers: new WeakMap(),
            fragmentUrls: new Map(),

            normalizeFieldNames(name) {
                if (! name.includes('.')) {
                    return [name];
                }

                const parts = name.split('.');
                const bracketName = parts.shift() + parts.map(function (part) {
                    return `[${part}]`;
                }).join('');

                return [name, bracketName];
            },

            findField(form, name) {
                const names = this.normalizeFieldNames(name);

                for (const candidate of names) {
                    const field = form.querySelector(`[name="${candidate.replace(/"/g, '\\"')}"]`);
                    if (field) {
                        return field;
                    }
                }

                return null;
            },

            clearErrors(form) {
                form.querySelectorAll('.is-invalid').forEach(function (field) {
                    field.classList.remove('is-invalid');
                });

                form.querySelectorAll('.invalid-feedback[data-generated="true"]').forEach(function (node) {
                    node.remove();
                });
            },

            renderErrors(form, errors) {
                Object.entries(errors || {}).forEach(([name, messages]) => {
                    const field = this.findField(form, name);

                    if (! field) {
                        return;
                    }

                    field.classList.add('is-invalid');

                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.dataset.generated = 'true';
                    feedback.textContent = Array.isArray(messages) ? messages[0] : messages;
                    field.insertAdjacentElement('afterend', feedback);
                });
            },

            toggleLoading(form, loading) {
                form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (button) {
                    button.disabled = loading;
                    button.classList.toggle('btn-loading', loading);
                });
            },

            setActionLoading(element, loading) {
                if (! element) {
                    return;
                }

                const loadingText = element.dataset.loadingText || 'Memproses...';
                const loadingMode = element.dataset.loadingMode || 'text';

                if (loading) {
                    if (element.dataset.loadingActive === 'true') {
                        return;
                    }

                    element.dataset.loadingActive = 'true';
                    element.dataset.originalMinWidth = element.style.minWidth || '';
                    element.style.minWidth = `${Math.ceil(element.getBoundingClientRect().width)}px`;
                    element.classList.add('action-loading');
                    element.setAttribute('aria-busy', 'true');

                    if (element.tagName === 'INPUT') {
                        element.dataset.originalValue = element.value;
                        element.value = loadingText;
                        element.disabled = true;
                        return;
                    }

                    element.dataset.originalHtml = element.innerHTML;
                    element.innerHTML = loadingMode === 'icon'
                        ? '<span class="btn-loading-content"><span class="btn-spinner"></span></span>'
                        : `<span class="btn-loading-content"><span class="btn-spinner"></span><span>${loadingText}</span></span>`;

                    if ('disabled' in element) {
                        element.disabled = true;
                    }

                    return;
                }

                if (element.dataset.loadingActive !== 'true') {
                    return;
                }

                element.classList.remove('action-loading');
                element.removeAttribute('aria-busy');
                element.style.minWidth = element.dataset.originalMinWidth || '';

                if (element.tagName === 'INPUT') {
                    element.value = element.dataset.originalValue || element.value;
                    element.disabled = false;
                    delete element.dataset.originalValue;
                } else {
                    if (element.dataset.originalHtml !== undefined) {
                        element.innerHTML = element.dataset.originalHtml;
                    }

                    if ('disabled' in element) {
                        element.disabled = false;
                    }

                    delete element.dataset.originalHtml;
                }

                delete element.dataset.originalMinWidth;
                delete element.dataset.loadingActive;
            },

            setLogoutLoading(element, loading) {
                if (! element || ! element.classList.contains('logout-link')) {
                    return false;
                }

                const icon = element.querySelector('i');

                if (loading) {
                    if (element.dataset.logoutLoadingActive === 'true') {
                        return true;
                    }

                    element.dataset.logoutLoadingActive = 'true';
                    element.classList.add('logout-inline-loading');

                    if (icon) {
                        icon.dataset.originalClass = icon.className;
                        icon.className = 'fas fa-spinner fa-spin';
                    }

                    if ('disabled' in element) {
                        element.disabled = true;
                    }

                    return true;
                }

                if (element.dataset.logoutLoadingActive !== 'true') {
                    return true;
                }

                element.classList.remove('logout-inline-loading');

                if (icon && icon.dataset.originalClass) {
                    icon.className = icon.dataset.originalClass;
                    delete icon.dataset.originalClass;
                }

                if ('disabled' in element) {
                    element.disabled = false;
                }

                delete element.dataset.logoutLoadingActive;

                return true;
            },

            closeRelatedModal(form) {
                const selector = form.dataset.closeModal;
                const modal = selector
                    ? document.querySelector(selector)
                    : form.closest('.modal, .modal-custom');

                if (modal) {
                    modal.classList.remove('show');
                }
            },

            resolveTargetSelector(source) {
                return source?.dataset?.refreshTarget || source?.dataset?.ajaxTarget || '';
            },

            shouldReplaceUrl(source) {
                return source?.dataset?.replaceUrl === 'true';
            },

            resolveConfirmOptions(source) {
                const message = source?.dataset?.confirm || '';
                if (! message) {
                    return null;
                }

                const normalized = message.toLowerCase();
                const options = {
                    title: 'Konfirmasi Aksi',
                    message,
                    note: 'Pastikan tindakan ini memang ingin dijalankan.',
                    subtitle: null,
                    confirmText: 'Ya, lanjutkan',
                    cancelText: 'Batal',
                    variant: 'primary',
                    icon: null,
                };

                if (normalized.includes('logout') || normalized.includes('keluar')) {
                    options.title = 'Logout Dari Panel';
                    options.message = 'Sesi admin saat ini akan diakhiri. Lanjutkan logout sekarang?';
                    options.note = 'Pastikan perubahan penting sudah tersimpan sebelum keluar.';
                    options.subtitle = 'Anda akan keluar dari sesi admin aktif.';
                    options.confirmText = 'Ya, logout';
                    options.variant = 'warning';
                    options.icon = 'fa-right-from-bracket';
                } else if (normalized.includes('reset password')) {
                    options.title = 'Reset Password Karyawan';
                    options.message = 'Password akun ini akan diubah ke default `123456`.';
                    options.note = 'Setelah login, pengguna sebaiknya segera mengganti password-nya.';
                    options.subtitle = 'Akun akan kembali memakai password bawaan.';
                    options.confirmText = 'Ya, reset';
                    options.variant = 'warning';
                    options.icon = 'fa-key';
                } else if (normalized.includes('reset')) {
                    options.title = 'Reset Data Sistem';
                    options.note = 'Data yang direset tidak dapat dipulihkan setelah proses selesai.';
                    options.subtitle = 'Pastikan Anda memahami dampak proses reset ini.';
                    options.confirmText = 'Ya, reset';
                    options.variant = 'warning';
                    options.icon = 'fa-rotate-left';
                } else if (normalized.includes('hapus')) {
                    options.title = 'Hapus Data';
                    options.note = 'Data yang dihapus akan hilang permanen dan tidak bisa dikembalikan.';
                    options.subtitle = 'Tindakan ini bersifat permanen.';
                    options.confirmText = 'Ya, hapus';
                    options.variant = 'danger';
                    options.icon = 'fa-trash-can';
                } else if (normalized.includes('tolak')) {
                    options.title = 'Tolak Data';
                    options.note = 'Pastikan pengajuan ini sudah diperiksa sebelum ditolak.';
                    options.subtitle = 'Status data akan diubah menjadi ditolak.';
                    options.confirmText = 'Ya, tolak';
                    options.variant = 'danger';
                    options.icon = 'fa-circle-xmark';
                } else if (normalized.includes('setujui')) {
                    options.title = 'Setujui Data';
                    options.note = 'Pastikan data yang disetujui sudah benar dan lengkap.';
                    options.subtitle = 'Status data akan diubah menjadi disetujui.';
                    options.confirmText = 'Ya, setujui';
                    options.variant = 'success';
                    options.icon = 'fa-circle-check';
                } else if (normalized.includes('broadcast') || normalized.includes('kirim')) {
                    options.title = 'Kirim Broadcast';
                    options.note = 'Pesan akan dikirim ke seluruh penerima aktif yang terdaftar.';
                    options.subtitle = 'Periksa kembali isi pesan sebelum dikirim.';
                    options.confirmText = 'Ya, kirim';
                    options.variant = 'warning';
                    options.icon = 'fa-paper-plane';
                }

                if (source?.dataset?.confirmTitle) {
                    options.title = source.dataset.confirmTitle;
                }

                if (source?.dataset?.confirmNote) {
                    options.note = source.dataset.confirmNote;
                }

                if (source?.dataset?.confirmSubtitle) {
                    options.subtitle = source.dataset.confirmSubtitle;
                }

                if (source?.dataset?.confirmButton) {
                    options.confirmText = source.dataset.confirmButton;
                }

                if (source?.dataset?.cancelButton) {
                    options.cancelText = source.dataset.cancelButton;
                }

                if (source?.dataset?.confirmVariant) {
                    options.variant = source.dataset.confirmVariant;
                }

                if (source?.dataset?.confirmIcon) {
                    options.icon = source.dataset.confirmIcon;
                }

                return options;
            },

            async confirmAction(source) {
                const options = this.resolveConfirmOptions(source);

                if (! options) {
                    return true;
                }

                return await window.panelConfirm.show(options);
            },

            buildGetUrl(form) {
                const action = form.getAttribute('action') || window.location.href;
                const url = new URL(action, window.location.origin);
                const formData = new FormData(form);
                const params = new URLSearchParams();

                formData.forEach((value, key) => {
                    if (value instanceof File) {
                        return;
                    }

                    if (value === null || value === undefined || value === '') {
                        return;
                    }

                    params.append(key, value);
                });

                url.search = params.toString();

                return url.toString();
            },

            showFragmentLoading(target, source = null) {
                if (! target) {
                    return;
                }

                if (source?.dataset?.loadingSilent === 'true') {
                    return;
                }

                if (target.querySelector(':scope > .fragment-loading-overlay')) {
                    return;
                }

                target.classList.add('fragment-loading-host');
                target.setAttribute('aria-busy', 'true');

                const overlay = document.createElement('div');
                overlay.className = 'fragment-loading-overlay';
                overlay.innerHTML = `
                    <div class="fragment-loading-box">
                        <span class="btn-spinner"></span>
                        <span>Memproses data...</span>
                    </div>
                `;

                target.appendChild(overlay);
            },

            hideFragmentLoading(target) {
                if (! target) {
                    return;
                }

                const overlay = target.querySelector(':scope > .fragment-loading-overlay');
                if (overlay) {
                    overlay.remove();
                }

                target.removeAttribute('aria-busy');
                target.classList.remove('fragment-loading-host');
            },

            resolveLoadingElement(source, refreshSelector = '', method = 'GET') {
                if (source?.dataset?.loadingTarget) {
                    return document.querySelector(source.dataset.loadingTarget);
                }

                if (method === 'GET' && refreshSelector) {
                    const refreshTarget = document.querySelector(refreshSelector);

                    if (! refreshTarget) {
                        return null;
                    }

                    if (refreshTarget.matches('[data-fragment-loading-scope], .table-responsive, .table-container, .table-scroll')) {
                        return refreshTarget;
                    }

                    const explicitScope = refreshTarget.querySelector('[data-fragment-loading-scope]');
                    if (explicitScope) {
                        return explicitScope;
                    }

                    const firstTableWrapper = refreshTarget.querySelector('.table-responsive, .table-container, .table-scroll');
                    if (firstTableWrapper) {
                        return firstTableWrapper;
                    }

                    const firstTable = refreshTarget.querySelector('table');
                    if (firstTable) {
                        return firstTable.parentElement;
                    }

                    return null;
                }

                return source?.closest?.('[data-loading-scope], [data-fragment-loading-scope], .table-responsive, .table-container, .table-scroll') || null;
            },

            async loadFragment(url, selector, replaceUrl = false, source = null) {
                if (! selector) {
                    return;
                }

                const target = document.querySelector(selector);
                if (! target) {
                    window.location.href = url;
                    return;
                }

                const requestUrl = new URL(url, window.location.origin).toString();
                const loadingTarget = this.resolveLoadingElement(source, selector, 'GET');
                this.showFragmentLoading(loadingTarget, source);

                try {
                    const response = await fetch(requestUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-Panel-Fragment': '1',
                        },
                        credentials: 'same-origin',
                    });

                    if (! response.ok) {
                        throw new Error('Gagal memuat data halaman.');
                    }

                    const html = await response.text();
                    const parser = new DOMParser();
                    const documentCopy = parser.parseFromString(html, 'text/html');
                    const nextTarget = documentCopy.querySelector(selector);

                    if (! nextTarget) {
                        window.location.href = requestUrl;
                        return;
                    }

                    target.innerHTML = nextTarget.innerHTML;
                    this.fragmentUrls.set(selector, requestUrl);

                    if (replaceUrl) {
                        window.history.replaceState({}, '', requestUrl);
                    }

                    window.dispatchEvent(new CustomEvent('panel:fragment-refreshed', {
                        detail: { selector, url: requestUrl },
                    }));
                } finally {
                    this.hideFragmentLoading(loadingTarget);
                }
            },

            async refreshFragment(selector, source = null) {
                const requestUrl = this.fragmentUrls.get(selector) || window.location.href;
                await this.loadFragment(requestUrl, selector, false, source);
            },

            async submitForm(form, trigger = null) {
                if (! await this.confirmAction(form)) {
                    return;
                }

                this.clearErrors(form);
                this.toggleLoading(form, true);
                this.setActionLoading(trigger, true);

                try {
                    const method = (form.getAttribute('method') || 'POST').toUpperCase();

                    if (method === 'GET') {
                        await this.loadFragment(
                            this.buildGetUrl(form),
                            this.resolveTargetSelector(form),
                            this.shouldReplaceUrl(form),
                            form
                        );
                        return;
                    }

                    const response = await fetch(form.action, {
                        method,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        body: new FormData(form),
                        credentials: 'same-origin',
                    });

                    const contentType = response.headers.get('content-type') || '';

                    if (! contentType.includes('application/json')) {
                        if (response.redirected) {
                            window.location.href = response.url;
                            return;
                        }

                        throw new Error('Respons server tidak valid.');
                    }

                    const payload = await response.json();

                    if (! response.ok) {
                        if (response.status === 422 && payload.errors) {
                            this.renderErrors(form, payload.errors);
                        }

                        window.panelToast.show(payload.level || 'error', payload.message || 'Proses gagal.');
                        return;
                    }

                    window.panelToast.show(payload.level || 'success', payload.message || 'Proses berhasil.');

                    if (form.dataset.resetOnSuccess === 'true') {
                        form.reset();
                    }

                    this.closeRelatedModal(form);

                    if (payload.redirect) {
                        window.setTimeout(function () {
                            window.location.href = payload.redirect;
                        }, 350);
                        return;
                    }

                    await this.refreshFragment(form.dataset.refreshTarget || '', trigger || form);
                } catch (error) {
                    window.panelToast.show('error', error.message || 'Terjadi kesalahan saat memproses permintaan.');
                } finally {
                    this.toggleLoading(form, false);
                    this.setActionLoading(trigger, false);
                }
            },

            async handleSubmit(event) {
                const form = event.target;
                if (! form.matches('form[data-ajax="true"]')) {
                    return;
                }

                event.preventDefault();

                await this.submitForm(form, event.submitter || null);
            },

            async handleNativeConfirmSubmit(event) {
                const form = event.target;
                if (! form.matches('form[data-confirm]') || form.matches('form[data-ajax="true"]')) {
                    return;
                }

                if (form.dataset.confirmApproved === 'true') {
                    delete form.dataset.confirmApproved;
                    return;
                }

                event.preventDefault();

                if (! await this.confirmAction(form)) {
                    return;
                }

                const submitter = event.submitter || null;
                this.setActionLoading(submitter, true);
                form.dataset.confirmApproved = 'true';

                window.setTimeout(() => {
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit(submitter || undefined);
                        return;
                    }

                    form.submit();
                }, 0);
            },

            handleNativeSubmitLoading(event) {
                const form = event.target;
                if (! form.matches('form[data-submit-loading="true"]') || form.matches('form[data-ajax="true"]')) {
                    return;
                }

                if (form.dataset.submitLoadingApplied === 'true') {
                    return;
                }

                form.dataset.submitLoadingApplied = 'true';
                const submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');

                if (this.setLogoutLoading(submitter, true)) {
                    return;
                }

                this.setActionLoading(submitter, true);
            },

            handleAutoSubmit(event) {
                const field = event.target;
                const form = field.closest('form[data-auto-submit="true"]');

                if (! form) {
                    return;
                }

                if ((form.getAttribute('method') || 'GET').toUpperCase() !== 'GET') {
                    return;
                }

                if (field.matches('[data-no-auto-submit], button, [type="submit"], [type="button"], [type="file"]')) {
                    return;
                }

                this.submitForm(form);
            },

            handleAutoInput(event) {
                const field = event.target;
                const form = field.closest('form[data-auto-submit="true"]');

                if (! form) {
                    return;
                }

                if ((form.getAttribute('method') || 'GET').toUpperCase() !== 'GET') {
                    return;
                }

                if (! field.matches('input[type="search"], input[type="text"]')) {
                    return;
                }

                const activeTimer = this.autoSubmitTimers.get(form);
                if (activeTimer) {
                    window.clearTimeout(activeTimer);
                }

                const nextTimer = window.setTimeout(() => {
                    this.submitForm(form);
                    this.autoSubmitTimers.delete(form);
                }, 350);

                this.autoSubmitTimers.set(form, nextTimer);
            },

            async handleLink(event) {
                const link = event.target.closest('a[data-ajax-link="true"]');
                if (! link) {
                    return;
                }

                event.preventDefault();
                if (! await this.confirmAction(link)) {
                    return;
                }

                this.setActionLoading(link, true);

                try {
                    await this.loadFragment(
                        link.href,
                        this.resolveTargetSelector(link),
                        this.shouldReplaceUrl(link),
                        link
                    );
                } catch (error) {
                    window.panelToast.show('error', error.message || 'Gagal memuat halaman.');
                } finally {
                    this.setActionLoading(link, false);
                }
            },

            async handleNativeLink(event) {
                const link = event.target.closest('a[data-confirm]');
                if (! link || link.matches('[data-ajax-link="true"]')) {
                    return;
                }

                event.preventDefault();

                if (! await this.confirmAction(link)) {
                    return;
                }

                this.setActionLoading(link, true);
                window.location.href = link.href;
            },
        };

        document.addEventListener('submit', function (event) {
            window.panelAjax.handleNativeConfirmSubmit(event);
            window.panelAjax.handleNativeSubmitLoading(event);
            window.panelAjax.handleSubmit(event);
        });

        document.addEventListener('change', function (event) {
            window.panelAjax.handleAutoSubmit(event);
        });

        document.addEventListener('input', function (event) {
            window.panelAjax.handleAutoInput(event);
        });

        document.addEventListener('click', function (event) {
            window.panelAjax.handleNativeLink(event);
            window.panelAjax.handleLink(event);
        });

        (function initializeHeaderDateTime() {
            const dateTimeContainer = document.getElementById('headerDateTime');
            const hourElement = document.getElementById('headerRunningHour');
            const minuteElement = document.getElementById('headerRunningMinute');
            const secondElement = document.getElementById('headerRunningSecond');
            const dateElement = document.getElementById('headerRunningDate');
            const timezoneElement = document.getElementById('headerRunningTimezone');

            if (! dateTimeContainer || ! hourElement || ! minuteElement || ! secondElement || ! dateElement) {
                return;
            }

            const timezone = dateTimeContainer.dataset.timezone || 'Asia/Jakarta';
            const timezoneLabel = dateTimeContainer.dataset.timezoneLabel || 'WIB';

            if (timezoneElement) {
                timezoneElement.textContent = timezoneLabel;
            }

            const timeFormatter = new Intl.DateTimeFormat('id-ID', {
                timeZone: timezone,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
            });

            const dateFormatter = new Intl.DateTimeFormat('id-ID', {
                timeZone: timezone,
                weekday: 'long',
                day: '2-digit',
                month: 'long',
                year: 'numeric',
            });

            const updateDateTime = () => {
                const now = new Date();
                const timeParts = timeFormatter.formatToParts(now);
                const hour = timeParts.find((part) => part.type === 'hour')?.value ?? '00';
                const minute = timeParts.find((part) => part.type === 'minute')?.value ?? '00';
                const second = timeParts.find((part) => part.type === 'second')?.value ?? '00';

                hourElement.textContent = hour;
                minuteElement.textContent = minute;
                secondElement.textContent = second;
                dateElement.textContent = dateFormatter.format(now);
            };

            updateDateTime();
            window.setInterval(updateDateTime, 1000);
        })();
    </script>
    @stack('scripts')
    @yield('scripts')
</body>
</html>
