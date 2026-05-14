        :root {
            --bg: #f1f5f9;
            --surface: #ffffff;
            --line: #e2e8f0;
            --text: #0f172a;
            --muted: #64748b;
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --soft: #eef2ff;
            --success: #059669;
            --success-hover: #047857;
            --radius: 14px;
            --radius-sm: 10px;
            --shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 4px 16px rgba(15, 23, 42, 0.06);
            --shadow-lg: 0 12px 40px rgba(15, 23, 42, 0.1);
            --sidebar-w: 288px;
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            background-image: radial-gradient(ellipse 120% 80% at 100% -20%, rgba(79, 70, 229, 0.07), transparent 50%),
                radial-gradient(ellipse 80% 60% at 0% 100%, rgba(5, 150, 105, 0.05), transparent 45%);
            font-family: "Plus Jakarta Sans", system-ui, -apple-system, Segoe UI, sans-serif;
            font-size: 15px;
            line-height: 1.5;
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }
        .shell {
            display: flex;
            gap: 0;
            align-items: stretch;
            min-height: 100vh;
            padding: 0;
        }
        /* Yan menu (koyu) */
        .sidebar {
            width: var(--sidebar-w);
            flex-shrink: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            background: linear-gradient(165deg, #0f172a 0%, #1e293b 55%, #0f172a 100%);
            color: #cbd5e1;
            border-right: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 4px 0 24px rgba(15, 23, 42, 0.12);
        }
        .sidebar__brand {
            padding: 1.35rem 1.25rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .sidebar__badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #a5b4fc;
            background: rgba(99, 102, 241, 0.25);
            padding: 4px 10px;
            border-radius: 999px;
            margin-bottom: 8px;
        }
        .sidebar h3 {
            margin: 0;
            font-size: 1.125rem;
            font-weight: 700;
            color: #f8fafc;
            letter-spacing: -0.02em;
            line-height: 1.3;
        }
        .sidebar__nav {
            flex: 1;
            overflow-y: auto;
            padding: 12px 10px 16px;
        }
        .menu-root, .submenu { list-style: none; margin: 0; padding: 0; }
        .menu-root > li { margin: 2px 0; }
        .menu-divider {
            list-style: none;
            height: 1px;
            margin: 14px 8px;
            padding: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.12), transparent);
            border: none;
        }
        .sidebar a.nav-link,
        .sidebar .single-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .sidebar a.nav-link:hover,
        .sidebar .single-link:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #f8fafc;
        }
        .sidebar details { margin: 2px 0; }
        .sidebar details summary {
            cursor: pointer;
            list-style: none;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            color: #e2e8f0;
            transition: background 0.15s ease;
        }
        .sidebar details summary::-webkit-details-marker { display: none; }
        .sidebar details summary:hover { background: rgba(255, 255, 255, 0.06); }
        .sidebar details[open] summary { background: rgba(255, 255, 255, 0.06); color: #fff; }
        .sidebar .arrow {
            font-size: 9px;
            color: #94a3b8;
            width: 16px;
            display: inline-flex;
            justify-content: center;
            transition: transform 0.2s ease;
        }
        .sidebar details[open] .arrow { transform: rotate(90deg); color: #a5b4fc; }
        .sidebar .submenu {
            margin-top: 4px;
            padding: 4px 0 8px 8px;
            border-left: 2px solid rgba(99, 102, 241, 0.45);
            margin-left: 14px;
        }
        .sidebar .submenu a {
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 500;
            color: #94a3b8;
        }
        .sidebar .submenu a:hover { color: #e2e8f0; }
        .sidebar__footer {
            padding: 14px 12px 18px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            margin-top: auto;
        }
        .sidebar__footer .btn {
            width: 100%;
            justify-content: center;
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.12);
            color: #e2e8f0;
        }
        .sidebar__footer .btn:hover {
            background: rgba(248, 113, 113, 0.2);
            border-color: rgba(248, 113, 113, 0.35);
            color: #fecaca;
        }
        /* â€”â€” Ana iÃ§erik â€”â€” */
        .content {
            flex: 1;
            min-width: 0;
            min-height: 100vh;
            overflow-x: auto;
            padding: 1.5rem clamp(1rem, 3vw, 2rem);
            background: transparent;
        }
        .content-loading { opacity: 0.55; pointer-events: none; transition: opacity 0.15s ease; }
        .content-inner-card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: clamp(1.25rem, 2vw, 1.75rem);
            max-width: 100%;
        }
        .content > .alert-ok:first-child,
        .content > .alert-err:first-child,
        .content > div.alert-err:first-child { margin-top: 0; }
        h1 { font-size: clamp(1.35rem, 2.5vw, 1.65rem); font-weight: 700; letter-spacing: -0.03em; margin: 0 0 0.5rem; color: var(--text); }
        h2 { font-size: 1.15rem; font-weight: 600; margin: 1.25rem 0 0.75rem; }
        h3 { font-size: 1.05rem; font-weight: 600; }
        .content a:not(.btn):not(.chip) { color: var(--primary); text-decoration: none; font-weight: 500; }
        .content a:not(.btn):not(.chip):hover { text-decoration: underline; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: 1px solid var(--line);
            background: var(--surface);
            border-radius: var(--radius-sm);
            padding: 9px 16px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            color: var(--text);
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            transition: background 0.15s, border-color 0.15s, box-shadow 0.15s;
        }
        .btn:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }
        .btn-primary {
            background: linear-gradient(180deg, var(--primary), var(--primary-hover));
            border-color: transparent;
            color: #fff;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.35);
        }
        .btn-primary:hover {
            filter: brightness(1.06);
            background: linear-gradient(180deg, #6366f1, var(--primary));
        }
        .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; filter: none; }
        .btn-success {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: 1px solid transparent;
            border-radius: var(--radius-sm);
            padding: 9px 16px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            background: linear-gradient(180deg, var(--success), var(--success-hover));
            color: #fff;
            box-shadow: 0 2px 8px rgba(5, 150, 105, 0.35);
            transition: filter 0.15s, background 0.15s, box-shadow 0.15s;
        }
        .btn-success:hover {
            filter: brightness(1.05);
            background: linear-gradient(180deg, #10b981, var(--success));
        }
        .btn-success:disabled { opacity: 0.5; cursor: not-allowed; filter: none; }
        .chips { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .chip {
            padding: 8px 14px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: var(--surface);
            font-size: 13px;
            font-weight: 500;
            color: var(--muted);
        }
        .chip.active {
            background: var(--soft);
            border-color: #c7d2fe;
            color: var(--primary);
            font-weight: 600;
        }
        .chip.nav-link { color: var(--text); }
        .chip.nav-link:hover { background: #f8fafc; }
        .toolbar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        input, select, textarea {
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            padding: 9px 12px;
            background: #fff;
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #a5b4fc;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
        input.search { min-width: 240px; }
        .kpis { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
        .kpi {
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            padding: 14px 16px;
            background: linear-gradient(180deg, #fff, #f8fafc);
            font-size: 13px;
            color: var(--muted);
        }
        .kpi b { display: block; font-size: 1.35rem; font-weight: 700; color: var(--text); margin-top: 6px; letter-spacing: -0.02em; }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            background: #fff;
            overflow: hidden;
        }
        table:not(.tbl-orders) { overflow: hidden; }
        table.tbl-orders { overflow: visible; border-radius: var(--radius-sm); }
        .tbl-orders tbody tr:has(details.row-actions[open]) { position: relative; z-index: 60; }
        .tbl-orders tbody tr:has(details.row-actions[open]) td { background: #fff; }
        th, td { border-bottom: 1px solid var(--line); padding: 12px 14px; text-align: left; font-size: 14px; }
        tbody tr:last-child td { border-bottom: none; }
        th { background: #f8fafc; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.04em; color: var(--muted); }
        tbody tr:hover td { background: #fafbfc; }
        .alert-ok {
            color: #166534;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: var(--radius-sm);
            padding: 12px 16px;
            margin: 0 0 14px;
            font-size: 14px;
        }
        .alert-err {
            color: #991b1b;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: var(--radius-sm);
            padding: 12px 16px;
            margin: 0 0 14px;
            font-size: 14px;
        }
        dialog.kurye-dialog {
            border: none;
            border-radius: var(--radius);
            padding: 0;
            max-width: 440px;
            width: 92vw;
            box-shadow: var(--shadow-lg);
            z-index: 1000;
        }
        dialog.kurye-dialog::backdrop { background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(4px); }
        .kurye-dialog__head {
            padding: 18px 20px;
            border-bottom: 1px solid var(--line);
            font-weight: 700;
            font-size: 1.05rem;
            background: #fafbfc;
        }
        .kurye-dialog__body { padding: 18px 20px 20px; display: grid; gap: 14px; }
        .kurye-dialog__row label { display: block; font-size: 12px; color: var(--muted); margin-bottom: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em; }
        .kurye-dialog__actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 6px; flex-wrap: wrap; }
        .gd-head {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid var(--line);
        }
        .gd-head__meta { color: var(--muted); font-size: 13px; margin: 6px 0 0; }
        .gd-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin: 0 0 1rem; }
        .gd-tabs a {
            padding: 10px 16px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: #fff;
            text-decoration: none;
            color: var(--text);
            font-size: 14px;
            font-weight: 500;
            transition: background 0.15s, border-color 0.15s;
        }
        .gd-tabs a:hover { background: #f8fafc; border-color: #cbd5e1; }
        .gd-tabs a.active { background: var(--soft); border-color: #a5b4fc; color: var(--primary); font-weight: 600; }
        .gd-count { opacity: 0.7; font-weight: 600; margin-left: 4px; font-size: 0.92em; }
        .tbl-wrap {
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            background: #fff;
            overflow: visible;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        }
        .tbl-orders { min-width: 920px; }
        .tbl-orders td { vertical-align: top; }
        .cust-block { font-size: 13px; line-height: 1.45; }
        .cust-block .nm { font-weight: 600; }
        .cust-block .ph { color: var(--muted); font-size: 12px; }
        .cust-block .adr { color: #475569; font-size: 12px; margin-top: 4px; max-width: 340px; }
        .pay-cell { font-weight: 600; white-space: nowrap; }
        .pay-sub { font-size: 12px; color: var(--muted); font-weight: 400; margin-top: 2px; }
        .status-wrap { min-width: 150px; overflow: visible; position: relative; z-index: 0; }
        .status-select {
            width: 100%;
            font-size: 13px;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid var(--line);
            cursor: pointer;
            background: #fff;
        }
        .status-pending { background: #fdf2f8; border-color: #fbcfe8; }
        .status-preparing { background: #fffbeb; border-color: #fcd34d; }
        .status-ready { background: #e0f2fe; border-color: #7dd3fc; }
        .status-assigned { background: #dbeafe; border-color: #93c5fd; }
        .status-picked { background: #dbeafe; border-color: #60a5fa; }
        .status-delivered { background: #dcfce7; border-color: #86efac; }
        .status-cancelled { background: #fee2e2; border-color: #fecaca; }
        .row-actions { margin-top: 8px; font-size: 12px; position: relative; z-index: 1; }
        .row-actions summary { cursor: pointer; color: var(--primary); font-weight: 600; }
        .row-actions ul {
            list-style: none;
            margin: 6px 0 0;
            padding: 10px 12px;
            display: grid;
            gap: 6px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-lg);
            position: absolute;
            left: 0;
            top: 100%;
            min-width: 220px;
            z-index: 100;
        }
        .row-actions button { border: none; background: none; color: var(--primary); cursor: pointer; text-align: left; padding: 4px 0; font: inherit; }
        .stat-toggle { display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--muted); cursor: pointer; user-select: none; }
        .stat-toggle input { accent-color: var(--primary); width: 18px; height: 18px; }
        .assign-courier-list { display: grid; gap: 10px; max-height: min(52vh, 320px); overflow: auto; margin-top: 6px; }
        .assign-courier-item {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            cursor: pointer;
            background: #fff;
            transition: border-color 0.15s, background 0.15s;
        }
        .assign-courier-item:has(input:checked) { border-color: var(--primary); background: var(--soft); }
        .assign-courier-item span { display: block; font-size: 13px; line-height: 1.4; }
        .assign-courier-item .c-name { font-weight: 600; }
        .assign-courier-item .c-meta { color: var(--muted); font-size: 12px; margin-top: 2px; }
        .row-actions button.linklike {
            border: none;
            background: none;
            color: var(--primary);
            cursor: pointer;
            padding: 0;
            font: inherit;
            text-align: left;
            text-decoration: underline;
            text-underline-offset: 2px;
        }
        .gd-stats-panel {
            margin: 0 0 16px;
            padding: 16px 18px;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }
        .gd-stats-panel .kpis { margin: 0; }
        .gd-status-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; font-size: 13px; color: var(--muted); }
        .gd-status-chips span {
            padding: 6px 12px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: #fff;
            font-size: 12px;
        }
        .tbl-orders th.is-col-hidden, .tbl-orders td.is-col-hidden { display: none !important; }
        .page-block { margin-bottom: 1.5rem; }
        .info-banner {
            margin: 0 0 14px;
            padding: 12px 16px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: var(--radius-sm);
            font-size: 13px;
            color: #1e40af;
        }
        .info-banner code { background: #fff; padding: 2px 8px; border-radius: 6px; font-size: 12px; border: 1px solid #dbeafe; }
        @media (max-width: 1024px) {
            .shell { flex-direction: column; min-height: auto; }
            .sidebar {
                width: 100%;
                max-height: none;
                position: relative;
                top: 0;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                box-shadow: none;
            }
            .sidebar__nav { max-height: 50vh; }
            .content { min-height: auto; padding: 1rem; }
            .kpis { grid-template-columns: 1fr; }
        }
        body.auth-page {
            display: grid;
            place-items: center;
            padding: 1.5rem;
            min-height: 100vh;
        }
        .auth-card {
            width: 100%;
            max-width: 420px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 1.75rem;
            box-shadow: var(--shadow-lg);
        }
        .auth-card.auth-card--wide { max-width: 460px; }
        .auth-card h1 { margin: 0 0 0.5rem; font-size: 1.5rem; font-weight: 700; letter-spacing: -0.02em; color: var(--text); }
        .auth-card > p { margin: 0 0 1rem; color: var(--muted); font-size: 14px; }
        .auth-card label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: var(--text); }
        .auth-card input {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            padding: 10px 12px;
            margin-bottom: 12px;
            font-size: 14px;
            font-family: inherit;
            box-sizing: border-box;
        }
        .auth-card input:focus {
            outline: none;
            border-color: #a5b4fc;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
        .auth-card form { display: block; }
        .auth-card .btn-primary { width: 100%; box-sizing: border-box; }
        .auth-error {
            margin-top: 12px;
            color: #991b1b;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: var(--radius-sm);
            padding: 8px 10px;
            font-size: 14px;
        }
