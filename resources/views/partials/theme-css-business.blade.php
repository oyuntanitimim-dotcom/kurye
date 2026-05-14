        .business-brand-sub { margin: 0.35rem 0 0; font-size: 12px; color: #94a3b8; line-height: 1.35; }
        .cards { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; margin-bottom: 14px; }
        .card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 12px; }
        .card .k { font-size: 12px; color: var(--muted); display: block; margin-bottom: 7px; }
        .card .v { font-size: 18px; font-weight: 700; color: var(--text); }
        .kpi-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin: 0 0 14px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            overflow: hidden;
        }
        .kpi-3 .item { padding: 18px 24px; border-right: 1px solid var(--line); }
        .kpi-3 .item:last-child { border-right: 0; }
        .kpi-3 .label { font-size: 17px; margin-bottom: 10px; color: var(--text); }
        .kpi-3 .value { font-size: 40px; line-height: 1.1; font-weight: 500; color: var(--text); }
        .placeholder {
            background: var(--surface);
            border: 1px dashed #c7d2fe;
            border-radius: var(--radius-sm);
            padding: 14px;
            color: #475569;
        }
        .section { background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 12px; }
        .section h3 { margin: 0 0 10px; font-size: 15px; }
        .space-between { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
        .btn-ghost {
            border: 1px solid #b7c4d8;
            background: #fff;
            color: #1e293b;
            border-radius: var(--radius-sm);
            padding: 8px 12px;
            cursor: pointer;
            font-family: inherit;
            font-size: 14px;
        }
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, 0.55);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 50;
            padding: 20px;
        }
        .modal.open { display: flex; }
        .modal-card {
            width: min(560px, 95vw);
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            padding: 18px;
        }
        .modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .modal-head h3 { margin: 0; }
        .icon-btn { border: 0; background: transparent; font-size: 24px; line-height: 1; cursor: pointer; color: #475569; }
        .form-grid { display: grid; gap: 10px; }
        .form-grid label { font-size: 13px; color: #334155; }
        .form-grid input, .form-grid textarea, .form-grid select {
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            padding: 10px;
            font-size: 14px;
            width: 100%;
            background: #fff;
            font-family: inherit;
            box-sizing: border-box;
        }
        .form-grid textarea { min-height: 72px; resize: vertical; }
        .hint { margin-top: 4px; color: var(--muted); font-size: 12px; }
        .toggle { display: flex; align-items: center; gap: 8px; }
        .toggle input { width: auto; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 8px; }
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .address-box {
            border: 1px solid #cfd8e8;
            border-radius: var(--radius-sm);
            background: #f8fbff;
            height: 140px;
            display: grid;
            place-items: center;
            color: var(--muted);
        }
        .muted-link { color: var(--primary); text-decoration: none; }
        .muted-link:hover { text-decoration: underline; }
        .pagination { display: flex; gap: 8px; justify-content: flex-end; padding: 10px; }
        .sidebar__footer .btn { width: 100%; }
        @media (max-width: 1100px) {
            .cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .kpi-3 { grid-template-columns: 1fr; }
            .two-col { grid-template-columns: 1fr; }
        }
