<style>
    /* ══════════════════════════════════════════════
       SHARED EXECUTIVE REPORT UI (MATCHES /stock-reorder)
       ══════════════════════════════════════════════ */
    .rpt-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .85rem;
        margin-bottom: 1.05rem;
    }
    .rpt-title-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .55rem;
    }
    .rpt-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-primary, #0f172a);
        margin: 0;
        letter-spacing: -.01em;
    }
    .rpt-pill {
        display: inline-flex;
        align-items: center;
        gap: .32rem;
        padding: .18rem .6rem;
        border-radius: 50px;
        background: rgba(0, 119, 182, .08);
        color: #0077b6;
        font-size: .72rem;
        font-weight: 600;
    }
    .rpt-controls {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
    }
    .rpt-controls .btn {
        height: 34px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .35rem;
        font-size: .78rem;
        font-weight: 600;
    }

    /* ── Segmented Pills ── */
    .rpt-pills {
        display: inline-flex;
        flex-wrap: wrap;
        background: #f1f5f9;
        padding: 3px;
        border-radius: 9px;
        gap: 2px;
    }
    .rpt-pill-link {
        text-decoration: none;
        color: #475569;
        font-size: .75rem;
        font-weight: 600;
        padding: .3rem .72rem;
        border-radius: 7px;
        transition: all .15s;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: .3rem;
    }
    .rpt-pill-link:hover {
        color: #0f172a;
    }
    .rpt-pill-link.active {
        background: #fff;
        color: #0077b6;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .08);
    }

    /* ── Executive KPI Grid ── */
    .rpt-kpi-grid {
        display: grid;
        gap: .85rem;
        margin-bottom: 1.15rem;
    }
    .rpt-kpi-grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .rpt-kpi-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .rpt-kpi-grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .rpt-kpi-grid-6 { grid-template-columns: repeat(3, minmax(0, 1fr)); }

    .rpt-kpi-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 14px;
        padding: .95rem 1.05rem;
        display: flex;
        align-items: center;
        gap: .85rem;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        min-width: 0;
        height: 100%;
    }
    .rpt-kpi-card:hover {
        transform: translateY(-2px);
        border-color: #cbd5e1;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .07);
    }
    .rpt-kpi-card.is-active {
        border-color: #0077b6;
        box-shadow: 0 0 0 3px rgba(0, 119, 182, .1);
    }
    .rpt-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.12rem;
        flex-shrink: 0;
    }
    .rpt-kpi-body {
        min-width: 0;
        flex: 1;
    }
    .rpt-kpi-label {
        font-size: .69rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .045em;
        color: var(--text-secondary, #64748b);
        margin: 0 0 .18rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .rpt-kpi-val {
        font-size: clamp(.95rem, 1.3vw, 1.24rem);
        font-weight: 800;
        line-height: 1.2;
        font-variant-numeric: tabular-nums;
        letter-spacing: -.015em;
        word-break: break-word;
    }
    .rpt-kpi-val.is-currency {
        font-size: clamp(.9rem, 1.18vw, 1.16rem);
    }
    .rpt-kpi-sub {
        font-size: .71rem;
        color: #94a3b8;
        margin-top: .18rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ── Cards & Filters ── */
    .rpt-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        overflow: hidden;
    }
    .rpt-card-header {
        padding: .85rem 1.15rem;
        border-bottom: 1px solid #f1f5f9;
        background: #fff;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .65rem;
    }
    .rpt-card-title {
        font-size: .88rem;
        font-weight: 700;
        color: var(--text-primary, #0f172a);
        margin: 0;
        display: inline-flex;
        align-items: center;
        gap: .45rem;
    }
    .rpt-filter-label {
        font-size: .69rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--text-secondary, #64748b);
        margin-bottom: .3rem;
        display: block;
    }

    @media (max-width: 991.98px) {
        .rpt-kpi-grid-4,
        .rpt-kpi-grid-6 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
        }
        .rpt-kpi-grid-3 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
        }
        .rpt-kpi-grid-3 > :last-child {
            grid-column: 1 / -1;
        }
    }
    @media (max-width: 575.98px) {
        .rpt-controls {
            width: 100%;
        }
        .rpt-controls .btn {
            flex: 1;
        }
        .rpt-pills {
            width: 100%;
        }
        .rpt-pill-link {
            flex: 1;
            justify-content: center;
            padding: .3rem .4rem;
        }
        .rpt-kpi-grid-2 {
            grid-template-columns: 1fr;
            gap: .65rem;
        }
        .rpt-kpi-card {
            padding: .8rem .85rem;
            gap: .65rem;
            border-radius: 12px;
        }
        .rpt-kpi-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            font-size: 1rem;
        }
        .rpt-card-header {
            padding: .75rem .9rem;
        }
    }
    @media (max-width: 420px) {
        .rpt-kpi-grid-3,
        .rpt-kpi-grid-4,
        .rpt-kpi-grid-6 {
            grid-template-columns: 1fr;
        }
        .rpt-kpi-grid-3 > :last-child {
            grid-column: auto;
        }
    }
</style>
