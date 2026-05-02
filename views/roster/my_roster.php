<?php
/**
 * My Roster — premium crew self-service view.
 *
 * Variables: $year, $month, $daysInMonth, $byDate, $upcoming, $summary,
 *            $dutyTypes, $activePeriod, $myChanges,
 *            $prevMonth, $prevYear, $nextMonth, $nextYear
 *
 * Visual language: matches OpsOne dark cockpit theme defined in app.css.
 * All colors come from CSS variables (--bg-card / --accent-* / --text-*) so
 * the page automatically follows the design system.
 *
 * iPad parity:
 *   - Sectors heading (matches DutyDetailView.swift)
 *   - 3-letter duty pills FLT / SBY / TRN / SIM / LVE / OFF
 *   - Calendar / List view toggle (mirrors iPad segmented control)
 *
 * Web-first additions (web leads, iPad to follow — see IPAD_PARITY.md):
 *   - Crew block in duty drawer with avatars + role pills
 *   - Acknowledge Roster action with persistent state
 *   - Briefing documents listing pulled from flight_bag_files
 */

$today     = date('Y-m-d');
$dutyTypes = $dutyTypes ?? RosterModel::dutyTypes();

// View mode persisted in URL so the user's preference survives nav.
$viewMode  = ($_GET['view'] ?? 'calendar') === 'list' ? 'list' : 'calendar';

// CSRF token reused for the inline acknowledge POST + leave/correction modal.
$csrf = csrfToken();
?>
<style>
/* ── Layout shell ─────────────────────────────────────────────────────── */
.myr-layout{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:24px;align-items:start;}
@media (max-width: 1100px){ .myr-layout{ grid-template-columns: 1fr; } }

/* ── Top header strip (month nav + view toggle + legend) ──────────────── */
.myr-tools{display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:18px;}
.myr-month-nav{display:flex;align-items:center;gap:6px;}
.myr-month-nav .nav-btn{
    width:32px;height:32px;display:flex;align-items:center;justify-content:center;
    border-radius:8px;border:1px solid var(--border-color);background:var(--bg-card);
    color:var(--text-secondary);text-decoration:none;font-size:14px;font-weight:700;
    transition:all .14s;
}
.myr-month-nav .nav-btn:hover{background:var(--bg-card-hover);color:var(--text-primary);border-color:var(--accent-blue);}
.myr-month-label{font-size:20px;font-weight:800;letter-spacing:-0.01em;color:var(--text-primary);min-width:170px;text-align:center;}

.myr-view-toggle{display:inline-flex;background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:10px;padding:3px;}
.myr-view-toggle a{
    padding:6px 14px;font-size:12px;font-weight:600;color:var(--text-secondary);
    text-decoration:none;border-radius:7px;transition:all .14s;
}
.myr-view-toggle a.is-active{
    background:linear-gradient(135deg,var(--accent-blue),var(--accent-cyan));
    color:#fff;box-shadow:0 1px 3px rgba(59,130,246,.35);
}

.myr-legend{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-left:auto;font-size:11px;}
.myr-legend-pill{display:inline-flex;align-items:center;gap:5px;color:var(--text-tertiary);}
.myr-legend-dot{width:9px;height:9px;border-radius:3px;}

/* ── Status banner (period state) ─────────────────────────────────────── */
.myr-banner{display:flex;align-items:center;gap:10px;padding:11px 16px;border-radius:10px;
    border:1px solid;margin-bottom:14px;font-size:13px;}
.myr-banner-pub{border-color:rgba(16,185,129,.4);background:rgba(16,185,129,.07);color:#a7f3d0;}
.myr-banner-fro{border-color:rgba(139,92,246,.4);background:rgba(139,92,246,.07);color:#ddd6fe;}
.myr-banner-dft{border-color:rgba(245,158,11,.4);background:rgba(245,158,11,.07);color:#fde68a;}

/* ── Calendar (premium) ───────────────────────────────────────────────── */
.myr-cal-wrap{
    background:var(--bg-card);border:1px solid var(--border-color);border-radius:14px;overflow:hidden;
    box-shadow:var(--shadow-card,0 1px 2px rgba(0,0,0,.4));
}
.myr-cal-body{padding:18px;}
.myr-dow-row{display:grid;grid-template-columns:repeat(7,1fr);gap:6px;margin-bottom:8px;}
.myr-dow-cell{
    text-align:center;font-size:10px;font-weight:700;text-transform:uppercase;
    letter-spacing:.08em;color:var(--text-tertiary);padding:4px 0;
}
.myr-cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:6px;}
.myr-day{
    position:relative;aspect-ratio:1/.95;
    background:var(--bg-secondary);border:1px solid var(--border-light);border-radius:10px;
    padding:8px 8px 6px;display:flex;flex-direction:column;
    transition:all .15s ease;
}
.myr-day:not(.is-empty):hover{
    background:var(--bg-card-hover);border-color:var(--accent-blue);
    transform:translateY(-1px);
    box-shadow:0 4px 12px rgba(0,0,0,.25);
}
.myr-day.has-duty{cursor:pointer;}
.myr-day.is-today{
    border-color:var(--accent-cyan);
    box-shadow:0 0 0 1.5px var(--accent-cyan);
}
.myr-day.is-today .myr-day-num{color:var(--accent-cyan);}
.myr-day.is-selected{
    background:linear-gradient(135deg, rgba(59,130,246,.18), rgba(6,182,212,.10));
    border-color:var(--accent-blue);
    box-shadow:0 0 0 1.5px var(--accent-blue),0 4px 16px rgba(59,130,246,.20);
}
.myr-day.is-wknd{background:rgba(15,23,42,.45);}
.myr-day.is-empty{visibility:hidden;}
.myr-day.is-acknowledged::after{
    content:'';position:absolute;top:6px;right:6px;width:6px;height:6px;border-radius:50%;
    background:var(--accent-green);box-shadow:0 0 0 2px rgba(16,185,129,.25);
}
.myr-day-num{
    font-size:13px;font-weight:700;line-height:1;color:var(--text-primary);
    margin-bottom:auto;
}
.myr-duty-pill{
    display:inline-flex;align-items:center;justify-content:center;
    border-radius:5px;padding:3px 6px;font-size:10px;font-weight:800;
    letter-spacing:.06em;width:100%;
}
.myr-day-flight{
    font-size:10px;font-weight:600;color:var(--text-secondary);margin-top:3px;
    overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-variant-numeric:tabular-nums;
}
.myr-empty-day{color:var(--border-color);font-size:18px;text-align:center;line-height:1;margin:auto auto;}

/* ── List view ─────────────────────────────────────────────────────── */
.myr-list-wrap{background:var(--bg-card);border:1px solid var(--border-color);border-radius:14px;overflow:hidden;}
.myr-list-row{
    display:flex;align-items:center;gap:14px;padding:14px 18px;border-bottom:1px solid var(--border-light);
    cursor:pointer;transition:background .12s;
}
.myr-list-row:last-child{border-bottom:none;}
.myr-list-row:hover{background:var(--bg-card-hover);}
.myr-list-row.is-today{background:rgba(6,182,212,.06);}
.myr-list-date{
    width:54px;flex-shrink:0;text-align:center;
    border-right:1px solid var(--border-light);padding-right:14px;
}
.myr-list-day{font-size:22px;font-weight:800;line-height:1;color:var(--text-primary);}
.myr-list-dow{font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:var(--text-tertiary);margin-top:3px;}
.myr-list-pill-col{flex-shrink:0;width:60px;}
.myr-list-pill-col .myr-duty-pill{display:inline-flex;width:auto;padding:4px 9px;font-size:11px;}
.myr-list-detail{flex:1;min-width:0;}
.myr-list-title{font-size:14px;font-weight:600;color:var(--text-primary);}
.myr-list-meta{font-size:12px;color:var(--text-tertiary);margin-top:2px;}
.myr-list-ack{font-size:10px;font-weight:700;color:var(--accent-green);}
.myr-list-empty{padding:40px;text-align:center;color:var(--text-tertiary);font-size:13px;}

/* ── Summary cards row ───────────────────────────────────────────────── */
.myr-summary-row{
    display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:18px;
}
.myr-summary-card{
    background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;
    padding:18px 20px;position:relative;overflow:hidden;
}
.myr-summary-card::before{
    content:'';position:absolute;top:0;left:0;right:0;height:3px;
    background:var(--card-accent,var(--accent-blue));
}
.myr-summary-card.--blue::before{background:var(--accent-blue);}
.myr-summary-card.--amber::before{background:var(--accent-yellow);}
.myr-summary-card.--green::before{background:var(--accent-green);}
.myr-summary-card-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-tertiary);}
.myr-summary-card-val{font-size:28px;font-weight:800;color:var(--text-primary);margin-top:6px;line-height:1;}
.myr-summary-card-sub{font-size:11px;color:var(--text-tertiary);margin-top:6px;}
@media (max-width: 720px){ .myr-summary-row{ grid-template-columns: 1fr 1fr; } .myr-summary-card.--green{ grid-column: span 2; } }

/* ── Right rail (compact) ─────────────────────────────────────────────── */
.myr-rail-card{
    background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;
    overflow:hidden;margin-bottom:16px;
}
.myr-rail-hdr{
    padding:14px 16px;border-bottom:1px solid var(--border-light);background:var(--bg-secondary);
    display:flex;align-items:center;justify-content:space-between;
}
.myr-rail-hdr strong{font-size:13px;font-weight:700;color:var(--text-primary);letter-spacing:.01em;}
.myr-rail-hdr a{font-size:12px;color:var(--accent-cyan);text-decoration:none;font-weight:600;}
.myr-rail-hdr a:hover{text-decoration:underline;}
.myr-rail-body{padding:14px 16px;}

/* selected-duty quick card */
.myr-quick-empty{padding:28px 16px;text-align:center;color:var(--text-tertiary);font-size:12px;line-height:1.5;}
.myr-quick-empty svg{display:block;margin:0 auto 10px;opacity:.6;}
.myr-quick-pill{font-size:11px;font-weight:800;letter-spacing:.06em;padding:3px 9px;border-radius:5px;}
.myr-quick-route{font-size:18px;font-weight:800;color:var(--text-primary);margin-top:8px;letter-spacing:.04em;}
.myr-quick-meta{font-size:12px;color:var(--text-tertiary);margin-top:5px;}
.myr-quick-btn{margin-top:12px;width:100%;padding:10px;border-radius:8px;border:none;cursor:pointer;
    background:linear-gradient(135deg,var(--accent-blue),var(--accent-cyan));color:#fff;
    font-size:13px;font-weight:700;transition:opacity .15s;}
.myr-quick-btn:hover{opacity:.9;}

/* recent requests compact list */
.myr-req-row{display:flex;align-items:flex-start;gap:10px;padding:10px 0;border-bottom:1px solid var(--border-light);}
.myr-req-row:last-child{border-bottom:none;}
.myr-req-icon{
    width:28px;height:28px;border-radius:8px;background:var(--bg-secondary);
    border:1px solid var(--border-light);display:flex;align-items:center;justify-content:center;
    flex-shrink:0;color:var(--text-secondary);
}
.myr-req-meta{flex:1;min-width:0;}
.myr-req-line1{font-size:12px;color:var(--text-primary);overflow:hidden;text-overflow:ellipsis;
    white-space:nowrap;line-height:1.4;}
.myr-req-line2{font-size:10px;color:var(--text-tertiary);margin-top:2px;}
.myr-status{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;
    padding:2px 7px;border-radius:4px;display:inline-block;}
.myr-status.--pending{background:rgba(245,158,11,.15);color:#fde68a;}
.myr-status.--approved{background:rgba(16,185,129,.15);color:#a7f3d0;}
.myr-status.--rejected{background:rgba(239,68,68,.15);color:#fecaca;}
.myr-status.--noted{background:rgba(139,92,246,.12);color:#ddd6fe;}

/* ── Drawer (richer, premium) ─────────────────────────────────────────── */
.dd-overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:60;
    opacity:0;pointer-events:none;transition:opacity .18s;backdrop-filter:blur(2px);}
.dd-overlay.is-open{opacity:1;pointer-events:auto;}
.dd-drawer{
    position:fixed;top:0;right:0;height:100vh;width:560px;max-width:96vw;
    background:var(--bg-card);border-left:1px solid var(--border-color);
    box-shadow:var(--shadow-modal,-12px 0 32px rgba(0,0,0,.5));
    z-index:61;
    transform:translateX(100%);transition:transform .22s cubic-bezier(.16,1,.3,1);
    display:flex;flex-direction:column;
}
.dd-drawer.is-open{transform:translateX(0);}
@media (max-width: 768px){
    .dd-drawer{
        width:100%;height:90vh;top:auto;bottom:0;
        border-left:none;border-top:1px solid var(--border-color);
        border-radius:18px 18px 0 0;
        transform:translateY(100%);
    }
    .dd-drawer.is-open{transform:translateY(0);}
}

.dd-hdr{
    padding:18px 22px;border-bottom:1px solid var(--border-light);
    display:flex;align-items:flex-start;gap:14px;flex-shrink:0;
    background:linear-gradient(180deg,var(--bg-card-hover),var(--bg-card));
}
.dd-pill-lg{
    flex-shrink:0;padding:6px 12px;border-radius:8px;font-size:13px;font-weight:800;
    letter-spacing:.08em;
}
.dd-title{font-size:14px;font-weight:600;color:var(--text-primary);line-height:1.3;}
.dd-date{font-size:18px;font-weight:800;color:var(--text-primary);margin-top:2px;letter-spacing:-0.01em;}
.dd-ack-row{display:flex;align-items:center;gap:6px;font-size:11px;font-weight:600;margin-top:6px;}
.dd-ack-row.--yes{color:var(--accent-green);}
.dd-ack-row.--no{color:var(--accent-yellow);}
.dd-ack-dot{width:8px;height:8px;border-radius:50%;background:currentColor;}
.dd-close{
    margin-left:auto;background:transparent;border:1px solid var(--border-light);width:32px;height:32px;
    border-radius:8px;cursor:pointer;color:var(--text-secondary);font-size:18px;line-height:1;
    flex-shrink:0;transition:all .14s;
}
.dd-close:hover{background:var(--bg-secondary);color:var(--text-primary);border-color:var(--accent-blue);}

.dd-body{padding:18px 22px;overflow:auto;flex:1;}
.dd-section{margin-bottom:22px;}
.dd-section-label{
    font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;
    color:var(--text-tertiary);margin-bottom:10px;
}
.dd-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px 18px;}
.dd-cell{padding:8px 0;border-bottom:1px dashed var(--border-light);font-size:13px;}
.dd-cell-label{font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:var(--text-tertiary);}
.dd-cell-val{font-weight:600;color:var(--text-primary);margin-top:3px;font-variant-numeric:tabular-nums;}

.dd-sector{
    padding:14px 16px;border:1px solid var(--border-light);border-radius:10px;margin-bottom:10px;
    background:var(--bg-secondary);
}
.dd-sector:last-child{margin-bottom:0;}
.dd-sec-top{display:flex;align-items:baseline;justify-content:space-between;gap:10px;margin-bottom:10px;}
.dd-flightno{font-family:'JetBrains Mono','Menlo',monospace;font-size:15px;font-weight:800;color:var(--text-primary);}
.dd-acreg{font-size:11px;color:var(--text-tertiary);font-family:'JetBrains Mono','Menlo',monospace;}
.dd-route-big{font-size:20px;font-weight:800;color:var(--text-primary);letter-spacing:.04em;}
.dd-route-sep{color:var(--accent-cyan);margin:0 8px;}
.dd-times-row{
    display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-top:12px;
    padding-top:10px;border-top:1px solid var(--border-light);
}
.dd-time-cell{font-size:11px;}
.dd-time-cell .dd-cell-label{display:block;}
.dd-time-cell .dd-cell-val{font-size:13px;margin-top:2px;}

.dd-crew-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
@media (max-width: 600px){ .dd-crew-grid{ grid-template-columns: 1fr; } }
.dd-crew-card{
    display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;
    background:var(--bg-secondary);border:1px solid var(--border-light);
}
.dd-crew-card.--self{border-color:var(--accent-blue);background:rgba(59,130,246,.06);}
.dd-avatar{
    width:36px;height:36px;border-radius:50%;background:var(--bg-card);
    display:flex;align-items:center;justify-content:center;
    color:var(--text-secondary);font-weight:700;font-size:13px;
    flex-shrink:0;border:1px solid var(--border-color);overflow:hidden;
}
.dd-avatar img{width:100%;height:100%;object-fit:cover;}
.dd-crew-meta{flex:1;min-width:0;}
.dd-crew-name{font-size:13px;font-weight:600;color:var(--text-primary);
    overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.dd-crew-role{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;
    color:var(--accent-cyan);margin-top:2px;}
.dd-crew-self{font-size:9px;font-weight:700;color:var(--accent-blue);margin-left:auto;
    padding:2px 6px;border:1px solid var(--accent-blue);border-radius:4px;
    text-transform:uppercase;letter-spacing:.06em;}
.dd-contact-btn{
    background:transparent;border:none;color:var(--text-tertiary);cursor:not-allowed;
    width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;
    flex-shrink:0;
}

.dd-doc-row{
    display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;
    background:var(--bg-secondary);border:1px solid var(--border-light);margin-bottom:8px;
    text-decoration:none;color:var(--text-primary);transition:all .14s;
}
.dd-doc-row:hover{border-color:var(--accent-blue);background:var(--bg-card-hover);}
.dd-doc-icon{
    width:32px;height:32px;border-radius:8px;background:var(--bg-card);
    display:flex;align-items:center;justify-content:center;color:var(--accent-cyan);flex-shrink:0;
}
.dd-doc-name{font-size:13px;font-weight:600;}
.dd-doc-meta{font-size:11px;color:var(--text-tertiary);margin-top:1px;}

.dd-empty{padding:24px;text-align:center;color:var(--text-tertiary);font-size:12px;
    border:1px dashed var(--border-light);border-radius:10px;}
.dd-loading{padding:60px 24px;text-align:center;color:var(--text-tertiary);font-size:13px;}

/* sticky action footer */
.dd-actions{
    padding:14px 22px;border-top:1px solid var(--border-light);
    display:flex;gap:8px;flex-wrap:wrap;flex-shrink:0;
    background:var(--bg-secondary);
}
.dd-action-btn{
    flex:1;min-width:0;padding:11px 12px;font-size:13px;font-weight:700;
    border-radius:8px;border:1px solid var(--border-color);background:var(--bg-card);
    color:var(--text-primary);cursor:pointer;
    display:flex;align-items:center;justify-content:center;gap:6px;
    transition:all .14s;
}
.dd-action-btn:hover{border-color:var(--accent-blue);background:var(--bg-card-hover);}
.dd-action-btn.--primary{
    background:linear-gradient(135deg,var(--accent-green),#059669);
    color:#fff;border-color:transparent;
}
.dd-action-btn.--primary:hover{filter:brightness(1.08);}
.dd-action-btn[disabled]{opacity:.45;cursor:not-allowed;pointer-events:none;}

/* ── Inline modal (leave / correction quick form) ─────────────────────── */
.qm-overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:62;display:none;
    align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(2px);}
.qm-overlay.is-open{display:flex;}
.qm-modal{background:var(--bg-card);border:1px solid var(--border-color);border-radius:14px;
    width:100%;max-width:520px;box-shadow:var(--shadow-modal,0 24px 64px rgba(0,0,0,.55));
    display:flex;flex-direction:column;max-height:90vh;}
.qm-hdr{padding:18px 22px;border-bottom:1px solid var(--border-light);display:flex;align-items:center;gap:10px;}
.qm-title{font-size:16px;font-weight:800;}
.qm-body{padding:18px 22px;overflow:auto;}
.qm-body label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;
    letter-spacing:.06em;color:var(--text-tertiary);margin-bottom:6px;}
.qm-body input, .qm-body select, .qm-body textarea{
    width:100%;padding:10px 12px;border-radius:8px;border:1px solid var(--border-color);
    background:var(--bg-input);color:var(--text-primary);font-size:14px;font-family:inherit;
    margin-bottom:14px;
}
.qm-body textarea{min-height:120px;resize:vertical;}
.qm-actions{padding:14px 22px;border-top:1px solid var(--border-light);
    display:flex;gap:10px;justify-content:flex-end;background:var(--bg-secondary);}
.qm-cancel{padding:10px 16px;border-radius:8px;border:1px solid var(--border-color);
    background:transparent;color:var(--text-secondary);cursor:pointer;font-size:13px;font-weight:600;}
.qm-submit{padding:10px 18px;border-radius:8px;border:none;cursor:pointer;
    background:linear-gradient(135deg,var(--accent-blue),var(--accent-cyan));color:#fff;
    font-size:13px;font-weight:700;}

/* ── Toast ────────────────────────────────────────────────────────────── */
.myr-toast{
    position:fixed;left:50%;bottom:32px;transform:translateX(-50%) translateY(20px);
    background:var(--bg-card);border:1px solid var(--accent-green);
    color:var(--text-primary);padding:12px 18px;border-radius:10px;font-size:13px;font-weight:600;
    box-shadow:var(--shadow-lg,0 10px 25px rgba(0,0,0,.5));
    opacity:0;pointer-events:none;transition:all .25s;z-index:80;
    display:flex;align-items:center;gap:10px;
}
.myr-toast.is-open{opacity:1;transform:translateX(-50%) translateY(0);pointer-events:auto;}
.myr-toast.--err{border-color:var(--accent-red);}
</style>

<!-- ── Period banner ──────────────────────────────────────────────────── -->
<?php if ($activePeriod): ?>
<div class="myr-banner <?= $activePeriod['status'] === 'published' ? 'myr-banner-pub' :
                          ($activePeriod['status'] === 'frozen' ? 'myr-banner-fro' : 'myr-banner-dft') ?>">
    <?php if ($activePeriod['status'] === 'published'): ?>
        ✓ Roster published for <strong><?= e($activePeriod['name']) ?></strong>
        (<?= date('d M', strtotime($activePeriod['start_date'])) ?> – <?= date('d M Y', strtotime($activePeriod['end_date'])) ?>)
    <?php elseif ($activePeriod['status'] === 'frozen'): ?>
        🔒 Roster frozen for <strong><?= e($activePeriod['name']) ?></strong> — no further changes
    <?php else: ?>
        ⚠ Roster for <strong><?= e($activePeriod['name']) ?></strong> is still in draft — check back soon
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ── Header strip: month nav · view toggle · legend ─────────────────── -->
<div class="myr-tools">
    <div class="myr-month-nav">
        <a href="/my-roster?year=<?= $prevYear ?>&month=<?= $prevMonth ?>&view=<?= e($viewMode) ?>" class="nav-btn" aria-label="Previous month">‹</a>
        <div class="myr-month-label"><?= date('F Y', mktime(0,0,0,$month,1,$year)) ?></div>
        <a href="/my-roster?year=<?= $nextYear ?>&month=<?= $nextMonth ?>&view=<?= e($viewMode) ?>" class="nav-btn" aria-label="Next month">›</a>
    </div>

    <div class="myr-view-toggle" role="tablist" aria-label="View mode">
        <a href="/my-roster?year=<?= $year ?>&month=<?= $month ?>&view=calendar"
           class="<?= $viewMode === 'calendar' ? 'is-active' : '' ?>" role="tab">Calendar</a>
        <a href="/my-roster?year=<?= $year ?>&month=<?= $month ?>&view=list"
           class="<?= $viewMode === 'list' ? 'is-active' : '' ?>" role="tab">List</a>
    </div>

    <div class="myr-legend">
        <?php
        $legendOrder = ['flight','standby','training','sim','leave','off'];
        foreach ($legendOrder as $k):
            $m = $dutyTypes[$k] ?? null;
            if (!$m) continue;
        ?>
        <span class="myr-legend-pill">
            <span class="myr-legend-dot" style="background:<?= $m['color'] ?>;"></span>
            <?= e($m['code']) ?>
        </span>
        <?php endforeach; ?>
    </div>
</div>

<div class="myr-layout">
    <!-- LEFT MAIN: calendar / list ──────────────────────────────────── -->
    <div>
        <?php if ($viewMode === 'calendar'): ?>
        <div class="myr-cal-wrap">
            <div class="myr-cal-body">
                <div class="myr-dow-row">
                    <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dow): ?>
                        <div class="myr-dow-cell"><?= $dow ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="myr-cal-grid">
                    <?php
                    $firstDay = (int) date('N', mktime(0,0,0,$month,1,$year));
                    for ($e = 1; $e < $firstDay; $e++) echo '<div class="myr-day is-empty"></div>';

                    for ($d = 1; $d <= $daysInMonth; $d++):
                        $dt     = sprintf('%04d-%02d-%02d', $year, $month, $d);
                        $entry  = $byDate[$dt] ?? null;
                        $dow    = (int) date('N', strtotime($dt));
                        $isWknd = $dow >= 6;
                        $isTdy  = $dt === $today;
                        $cls    = 'myr-day';
                        if ($isTdy)  $cls .= ' is-today';
                        if ($isWknd) $cls .= ' is-wknd';
                        if ($entry)  $cls .= ' has-duty';
                        if ($entry && !empty($entry['acknowledged_at'])) $cls .= ' is-acknowledged';
                        $dtype  = $entry['duty_type'] ?? null;
                        $dtMeta = $dtype ? ($dutyTypes[$dtype] ?? null) : null;
                    ?>
                    <div class="<?= $cls ?>"<?= $entry ? ' data-duty-id="' . (int)$entry['id'] . '"' : '' ?>>
                        <div class="myr-day-num"><?= $d ?></div>
                        <?php if ($entry && $dtMeta): ?>
                            <div class="myr-duty-pill" style="background:<?= $dtMeta['bg'] ?>;color:<?= $dtMeta['color'] ?>;">
                                <?= e($entry['duty_code'] ?: $dtMeta['code']) ?>
                            </div>
                        <?php elseif (!$entry): ?>
                            <div class="myr-empty-day">·</div>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <?php else: /* List view */ ?>
        <div class="myr-list-wrap">
            <?php
            $listEntries = array_values($byDate);
            usort($listEntries, fn($a, $b) => strcmp($a['roster_date'], $b['roster_date']));
            ?>
            <?php if (empty($listEntries)): ?>
                <div class="myr-list-empty">No duties scheduled for this month yet.</div>
            <?php else: foreach ($listEntries as $entry):
                $dt     = $entry['roster_date'];
                $dtMeta = $dutyTypes[$entry['duty_type']] ?? null;
                $isTdy  = $dt === $today;
            ?>
                <div class="myr-list-row <?= $isTdy ? 'is-today' : '' ?>" data-duty-id="<?= (int)$entry['id'] ?>">
                    <div class="myr-list-date">
                        <div class="myr-list-day"><?= (int)date('j', strtotime($dt)) ?></div>
                        <div class="myr-list-dow"><?= date('D', strtotime($dt)) ?></div>
                    </div>
                    <div class="myr-list-pill-col">
                        <?php if ($dtMeta): ?>
                        <span class="myr-duty-pill" style="background:<?= $dtMeta['bg'] ?>;color:<?= $dtMeta['color'] ?>;">
                            <?= e($entry['duty_code'] ?: $dtMeta['code']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="myr-list-detail">
                        <div class="myr-list-title">
                            <?= e($dtMeta['label'] ?? ucfirst($entry['duty_type'])) ?>
                            <?php if (!empty($entry['acknowledged_at'])): ?>
                                <span class="myr-list-ack">· acknowledged</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($entry['notes'])): ?>
                        <div class="myr-list-meta"><?= e($entry['notes']) ?></div>
                        <?php elseif ($isTdy): ?>
                        <div class="myr-list-meta">Today</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
        <?php endif; ?>

        <!-- ── Summary cards ─────────────────────────────────────────── -->
        <div class="myr-summary-row">
            <div class="myr-summary-card --blue">
                <div class="myr-summary-card-label">Flying days</div>
                <div class="myr-summary-card-val"><?= (int)($summary['flight'] ?? 0) ?></div>
                <div class="myr-summary-card-sub">Flight + positioning + deadhead</div>
            </div>
            <div class="myr-summary-card --amber">
                <div class="myr-summary-card-label">Standby / training</div>
                <div class="myr-summary-card-val"><?= (int)(($summary['standby'] ?? 0) + ($summary['reserve'] ?? 0) + ($summary['training'] ?? 0)) ?></div>
                <div class="myr-summary-card-sub">On-call + sim + ground</div>
            </div>
            <div class="myr-summary-card --green">
                <div class="myr-summary-card-label">Days off / leave</div>
                <div class="myr-summary-card-val"><?= (int)(($summary['off'] ?? 0) + ($summary['rest'] ?? 0) + ($summary['leave'] ?? 0)) ?></div>
                <div class="myr-summary-card-sub">OFF + rest + leave</div>
            </div>
        </div>
    </div>

    <!-- RIGHT RAIL: compact selected duty + recent requests ─────────── -->
    <div>
        <!-- Quick card driven by the drawer's last loaded duty -->
        <div class="myr-rail-card" id="quickCard">
            <div class="myr-rail-hdr">
                <strong>Selected duty</strong>
                <span style="font-size:11px;color:var(--text-tertiary);" id="quickHint">Click any date</span>
            </div>
            <div class="myr-rail-body" id="quickBody">
                <div class="myr-quick-empty">
                    <?= sidebarIcon('calendar', 28) ?>
                    Click any date on the calendar (or row in the list) to see full duty details, crew assignments and briefing documents.
                </div>
            </div>
        </div>

        <!-- Recent requests, latest 3 only -->
        <div class="myr-rail-card">
            <div class="myr-rail-hdr">
                <strong>Recent requests</strong>
                <a href="/my-roster/requests">View all →</a>
            </div>
            <div class="myr-rail-body">
                <?php if (empty($myChanges)): ?>
                    <div style="font-size:12px;color:var(--text-tertiary);text-align:center;padding:20px 0;">
                        No requests yet. Submit a leave or correction from any duty in the calendar.
                    </div>
                <?php else:
                    $recent3 = array_slice($myChanges, 0, 3);
                    $crTypeIcon = [
                        'leave_request' => 'calendar-days',
                        'swap_request'  => 'arrow-path',
                        'correction'    => 'pencil',
                        'comment'       => 'chat-bubble',
                    ];
                    $crTypeLabel = [
                        'leave_request' => 'Leave',
                        'swap_request'  => 'Swap',
                        'correction'    => 'Correction',
                        'comment'       => 'Comment',
                    ];
                    foreach ($recent3 as $cr):
                        $icon  = $crTypeIcon[$cr['change_type']] ?? 'document-text';
                        $label = $crTypeLabel[$cr['change_type']] ?? ucfirst($cr['change_type']);
                ?>
                <a class="myr-req-row" href="/my-roster/requests?type=<?= e($cr['change_type']) ?>"
                   style="text-decoration:none;color:inherit;">
                    <div class="myr-req-icon"><?= sidebarIcon($icon, 14) ?></div>
                    <div class="myr-req-meta">
                        <div class="myr-req-line1">
                            <strong><?= $label ?>:</strong>
                            <?= e(mb_substr($cr['message'], 0, 50)) ?><?= mb_strlen($cr['message']) > 50 ? '…' : '' ?>
                        </div>
                        <div class="myr-req-line2">
                            <?= date('d M', strtotime($cr['created_at'])) ?> ·
                            <span class="myr-status --<?= e($cr['status']) ?>"><?= e(ucfirst($cr['status'])) ?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Drawer ─────────────────────────────────────────────────────────── -->
<div class="dd-overlay" id="ddOverlay"></div>
<aside class="dd-drawer" id="ddDrawer" role="dialog" aria-modal="true" aria-labelledby="ddDate" aria-hidden="true">
    <div class="dd-hdr">
        <div class="dd-pill-lg" id="ddPill">—</div>
        <div style="flex:1;min-width:0;">
            <div class="dd-title" id="ddTitle">Duty</div>
            <div class="dd-date" id="ddDate"></div>
            <div class="dd-ack-row --no" id="ddAckRow">
                <span class="dd-ack-dot"></span>
                <span id="ddAckText">Pending acknowledgement</span>
            </div>
        </div>
        <button type="button" class="dd-close" id="ddClose" aria-label="Close">×</button>
    </div>
    <div class="dd-body" id="ddBody">
        <div class="dd-loading">Loading duty details…</div>
    </div>
    <div class="dd-actions" id="ddActions" style="display:none;">
        <button type="button" class="dd-action-btn --primary" id="ddAcknowledge">
            <?= sidebarIcon('check-badge', 14) ?> Acknowledge Roster
        </button>
        <button type="button" class="dd-action-btn" id="ddCorrection">
            <?= sidebarIcon('pencil', 14) ?> Request Correction
        </button>
        <button type="button" class="dd-action-btn" id="ddLeave">
            <?= sidebarIcon('calendar-days', 14) ?> Request Leave
        </button>
    </div>
</aside>

<!-- ── Quick request modal ────────────────────────────────────────────── -->
<div class="qm-overlay" id="qmOverlay">
    <form class="qm-modal" id="qmForm" method="POST" action="/roster/changes/request">
        <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="redirect" value="/my-roster?year=<?= $year ?>&month=<?= $month ?>&view=<?= e($viewMode) ?>">
        <input type="hidden" name="change_type" id="qmType" value="leave_request">
        <input type="hidden" name="roster_id"   id="qmRosterId" value="">
        <div class="qm-hdr">
            <div class="dd-pill-lg" id="qmPill" style="font-size:12px;padding:4px 10px;">—</div>
            <div>
                <div class="qm-title" id="qmTitle">New Request</div>
                <div style="font-size:12px;color:var(--text-tertiary);" id="qmDate"></div>
            </div>
            <button type="button" class="dd-close" id="qmClose" style="margin-left:auto;">×</button>
        </div>
        <div class="qm-body">
            <label for="qmMessage" id="qmMessageLabel">Message</label>
            <textarea id="qmMessage" name="message" required maxlength="1000"
                      placeholder="Describe your request — include dates, flight numbers, or relevant context."></textarea>
            <div style="font-size:11px;color:var(--text-tertiary);">
                <span id="qmCharCount">0</span>/1000 · Average response time 24–48 h · Submitted to scheduling.
            </div>
        </div>
        <div class="qm-actions">
            <button type="button" class="qm-cancel" id="qmCancel">Cancel</button>
            <button type="submit" class="qm-submit" id="qmSubmit">Submit Request</button>
        </div>
    </form>
</div>

<!-- ── Toast container ────────────────────────────────────────────────── -->
<div class="myr-toast" id="myrToast">
    <span id="myrToastIcon">✓</span>
    <span id="myrToastMsg">Done.</span>
</div>

<script>
(function () {
    'use strict';

    // ── DOM refs ────────────────────────────────────────────────────────
    const overlay   = document.getElementById('ddOverlay');
    const drawer    = document.getElementById('ddDrawer');
    const pillEl    = document.getElementById('ddPill');
    const titleEl   = document.getElementById('ddTitle');
    const dateEl    = document.getElementById('ddDate');
    const ackRow    = document.getElementById('ddAckRow');
    const ackTxt    = document.getElementById('ddAckText');
    const bodyEl    = document.getElementById('ddBody');
    const actionsEl = document.getElementById('ddActions');
    const btnClose  = document.getElementById('ddClose');
    const btnAck    = document.getElementById('ddAcknowledge');
    const btnCorr   = document.getElementById('ddCorrection');
    const btnLeave  = document.getElementById('ddLeave');

    const qmOverlay = document.getElementById('qmOverlay');
    const qmForm    = document.getElementById('qmForm');
    const qmTitle   = document.getElementById('qmTitle');
    const qmDate    = document.getElementById('qmDate');
    const qmPill    = document.getElementById('qmPill');
    const qmType    = document.getElementById('qmType');
    const qmRoster  = document.getElementById('qmRosterId');
    const qmMessage = document.getElementById('qmMessage');
    const qmCharCount = document.getElementById('qmCharCount');
    const qmCancel  = document.getElementById('qmCancel');
    const qmCloseB  = document.getElementById('qmClose');

    const toastEl   = document.getElementById('myrToast');
    const toastMsg  = document.getElementById('myrToastMsg');
    const toastIcon = document.getElementById('myrToastIcon');

    const quickHint = document.getElementById('quickHint');
    const quickBody = document.getElementById('quickBody');

    let currentDuty = null;

    // ── Helpers ─────────────────────────────────────────────────────────
    const escape = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[c]));

    function fmtDate(iso) {
        if (!iso) return '';
        const d = new Date(iso + 'T00:00:00');
        return d.toLocaleDateString(undefined, {
            weekday:'long', day:'numeric', month:'long', year:'numeric'
        });
    }
    function fmtTime(t) {
        // accepts "HH:MM:SS" or "HH:MM" → "HH:MM"
        if (!t) return '—';
        const m = String(t).match(/^(\d{1,2}):(\d{2})/);
        return m ? (m[1].padStart(2,'0') + ':' + m[2]) : escape(t);
    }
    function fmtMins(mins) {
        if (mins == null) return '—';
        const h = Math.floor(mins / 60);
        const m = mins % 60;
        return h + 'h ' + String(m).padStart(2,'0') + 'm';
    }
    function initials(name) {
        if (!name) return '?';
        return name.split(/\s+/).filter(Boolean).slice(0,2)
            .map(p => p[0].toUpperCase()).join('');
    }
    function avatarHtml(c) {
        if (c.photo) {
            return '<div class="dd-avatar"><img src="/' +
                escape(c.photo.replace(/^\//,'')) +
                '" alt=""></div>';
        }
        return '<div class="dd-avatar">' + escape(initials(c.name)) + '</div>';
    }
    function showToast(msg, isErr) {
        toastEl.classList.toggle('--err', !!isErr);
        toastIcon.textContent = isErr ? '⚠' : '✓';
        toastMsg.textContent = msg;
        toastEl.classList.add('is-open');
        setTimeout(() => toastEl.classList.remove('is-open'), 2800);
    }

    // ── Drawer open / close ─────────────────────────────────────────────
    function openDrawer() {
        overlay.classList.add('is-open');
        drawer.classList.add('is-open');
        drawer.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    function closeDrawer() {
        overlay.classList.remove('is-open');
        drawer.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    // ── Render duty into drawer + quick card ────────────────────────────
    function renderDuty(d) {
        currentDuty = d;

        // Header: pill + date + ack badge
        const pillBg = d.duty_type_bg || '#1e293b';
        const pillFg = d.duty_type_color || '#e8eaf0';
        pillEl.style.background = pillBg;
        pillEl.style.color = pillFg;
        pillEl.textContent = d.duty_type_code || (d.duty_type || '').slice(0,3).toUpperCase();

        titleEl.textContent = d.duty_type_label + (d.duty_code ? ' · ' + d.duty_code : '');
        dateEl.textContent  = fmtDate(d.date);
        if (d.is_acknowledged) {
            ackRow.classList.remove('--no'); ackRow.classList.add('--yes');
            const when = d.acknowledged_at ? new Date(d.acknowledged_at.replace(' ','T'))
                .toLocaleString(undefined, {day:'numeric', month:'short', hour:'2-digit', minute:'2-digit'}) : '';
            ackTxt.textContent = 'Acknowledged' + (when ? ' · ' + when : '');
        } else {
            ackRow.classList.remove('--yes'); ackRow.classList.add('--no');
            ackTxt.textContent = 'Pending acknowledgement';
        }

        // ── Summary block ──
        let html = '<div class="dd-section">'
                 + '<div class="dd-section-label">Duty summary</div>'
                 + '<div class="dd-grid">'
                 + cell('Status', escape(d.duty_type_label))
                 + cell('Code',   escape(d.duty_code || '—'))
                 + cell('Station', escape(d.station || '—'))
                 + cell('Fleet',   escape(d.fleet_name || '—'))
                 + cell('Reserve type', escape(d.reserve_type || '—'))
                 + cell('Est. duty', fmtMins(d.est_duty_minutes))
                 + '</div>';
        if (d.routing) {
            html += '<div class="dd-cell" style="margin-top:8px;border-bottom:none;">'
                  + '<div class="dd-cell-label">Routing</div>'
                  + '<div class="dd-cell-val" style="font-size:14px;">' + escape(d.routing) + '</div>'
                  + '</div>';
        }
        html += '</div>';

        // ── Sectors ──
        if (d.sectors && d.sectors.length) {
            html += '<div class="dd-section"><div class="dd-section-label">Sectors</div>';
            d.sectors.forEach((s) => {
                html += '<div class="dd-sector">'
                     + '<div class="dd-sec-top">'
                     +   '<span class="dd-flightno">' + escape(s.flight_number || '—') + '</span>'
                     +   (s.aircraft_reg ? '<span class="dd-acreg">' + escape(s.aircraft_reg)
                            + (s.aircraft_type ? ' · ' + escape(s.aircraft_type) : '') + '</span>' : '')
                     + '</div>'
                     + '<div class="dd-route-big">' + escape(s.departure || '???')
                     +   '<span class="dd-route-sep">→</span>' + escape(s.arrival || '???')
                     + '</div>'
                     + '<div class="dd-times-row">'
                     +   timeCell('STD', s.std)
                     +   timeCell('ETD', s.etd)
                     +   timeCell('STA', s.sta)
                     +   timeCell('ETA', s.eta)
                     + '</div>'
                     + '</div>';
            });
            html += '</div>';
        } else if (['flight','pos','deadhead'].includes(d.duty_type)) {
            html += '<div class="dd-section"><div class="dd-section-label">Sectors</div>'
                  + '<div class="dd-empty">No sectors published for this duty yet.</div></div>';
        }

        // ── Crew block ──
        if (d.crew && d.crew.length) {
            html += '<div class="dd-section"><div class="dd-section-label">Crew (' + d.crew.length + ')</div>'
                  + '<div class="dd-crew-grid">';
            d.crew.forEach((c) => {
                html += '<div class="dd-crew-card' + (c.is_self ? ' --self' : '') + '">'
                     +   avatarHtml(c)
                     +   '<div class="dd-crew-meta">'
                     +     '<div class="dd-crew-name">' + escape(c.name || 'Unknown') + '</div>'
                     +     '<div class="dd-crew-role">' + escape(c.role_label || c.role) + '</div>'
                     +   '</div>'
                     +   (c.is_self
                            ? '<span class="dd-crew-self">You</span>'
                            : '<button class="dd-contact-btn" disabled title="Contact coming soon" aria-label="Contact">'
                                 + '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>'
                              + '</button>')
                     + '</div>';
            });
            html += '</div></div>';
        } else if (['flight','pos','deadhead'].includes(d.duty_type)) {
            html += '<div class="dd-section"><div class="dd-section-label">Crew</div>'
                  + '<div class="dd-empty">Crew not yet assigned for this flight.</div></div>';
        }

        // ── Documents block ──
        html += '<div class="dd-section"><div class="dd-section-label">Briefing &amp; documents</div>';
        if (d.briefing_docs && d.briefing_docs.length) {
            d.briefing_docs.forEach((doc) => {
                const sizeKb = doc.file_size ? Math.max(1, Math.round(doc.file_size / 1024)) + ' KB' : '';
                html += '<a class="dd-doc-row" href="/flights/' + (doc.flight_id|0) + '" target="_blank" rel="noopener">'
                     +   '<div class="dd-doc-icon">'
                     +     '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>'
                     +   '</div>'
                     +   '<div style="flex:1;min-width:0;">'
                     +     '<div class="dd-doc-name">' + escape(doc.title || doc.file_name) + '</div>'
                     +     '<div class="dd-doc-meta">' + escape((doc.file_type || 'document').toUpperCase())
                                + (sizeKb ? ' · ' + sizeKb : '') + '</div>'
                     +   '</div>'
                     + '</a>';
            });
        } else {
            html += '<div class="dd-empty">No briefing documents linked. '
                  + '<a href="/manuals" style="color:var(--accent-cyan);text-decoration:none;">Browse manuals →</a>'
                  + '</div>';
        }
        html += '</div>';

        // ── Operational remarks ──
        if (d.notes) {
            html += '<div class="dd-section"><div class="dd-section-label">Operational remarks</div>'
                  + '<div style="font-size:13px;line-height:1.5;color:var(--text-primary);'
                  +   'padding:12px 14px;background:var(--bg-secondary);border:1px solid var(--border-light);'
                  +   'border-left:3px solid var(--accent-yellow);border-radius:8px;">'
                  + escape(d.notes).replace(/\n/g, '<br>')
                  + '</div></div>';
        }

        bodyEl.innerHTML = html;
        actionsEl.style.display = 'flex';

        // Toggle action availability
        btnAck.disabled  = !d.can_acknowledge;
        btnAck.style.display  = d.is_acknowledged ? 'none' : '';
        btnCorr.disabled = !d.can_request_correction;
        btnLeave.disabled = !d.can_request_leave;

        // ── Update right-rail quick card too ──
        renderQuickCard(d);
    }

    function cell(label, val) {
        return '<div class="dd-cell"><div class="dd-cell-label">' + escape(label) + '</div>'
             + '<div class="dd-cell-val">' + val + '</div></div>';
    }
    function timeCell(label, val) {
        return '<div class="dd-time-cell"><div class="dd-cell-label">' + label + '</div>'
             + '<div class="dd-cell-val">' + fmtTime(val) + '</div></div>';
    }

    function renderQuickCard(d) {
        const pillBg = d.duty_type_bg || '#1e293b';
        const pillFg = d.duty_type_color || '#e8eaf0';
        const code   = d.duty_type_code || (d.duty_type || '').slice(0,3).toUpperCase();
        let routeHtml = '';
        if (d.routing) {
            routeHtml = '<div class="myr-quick-route">' + escape(d.routing) + '</div>';
        } else if (d.sectors && d.sectors.length) {
            const s = d.sectors[0];
            routeHtml = '<div class="myr-quick-route">'
                      + escape(s.departure || '???') + ' <span style="color:var(--accent-cyan);">→</span> '
                      + escape(s.arrival   || '???') + '</div>';
        }
        let metaHtml = '';
        if (d.sectors && d.sectors.length) {
            const std = d.sectors[0].std;
            const sta = d.sectors[d.sectors.length - 1].sta;
            metaHtml = '<div class="myr-quick-meta">'
                     + 'STD ' + fmtTime(std) + ' · STA ' + fmtTime(sta)
                     + (d.est_duty_minutes != null ? ' · ' + fmtMins(d.est_duty_minutes) : '')
                     + '</div>';
        } else if (d.notes) {
            metaHtml = '<div class="myr-quick-meta">' + escape(d.notes) + '</div>';
        }
        quickHint.textContent = fmtDate(d.date);
        quickBody.innerHTML = ''
            + '<span class="myr-quick-pill" style="background:' + pillBg + ';color:' + pillFg + ';">' + escape(code) + '</span>'
            + ' <span style="font-size:13px;color:var(--text-secondary);margin-left:8px;">' + escape(d.duty_type_label) + '</span>'
            + routeHtml
            + metaHtml
            + '<button type="button" class="myr-quick-btn" id="quickViewBtn">View full detail</button>';
        const qb = document.getElementById('quickViewBtn');
        if (qb) qb.addEventListener('click', openDrawer);
    }

    // ── Load duty by id ─────────────────────────────────────────────────
    async function loadDuty(id, andOpen) {
        bodyEl.innerHTML = '<div class="dd-loading">Loading duty details…</div>';
        actionsEl.style.display = 'none';
        if (andOpen) openDrawer();
        try {
            const res = await fetch('/my-roster/duty/' + encodeURIComponent(id), {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
            });
            if (!res.ok) {
                bodyEl.innerHTML = '<div class="dd-empty">Could not load duty (HTTP ' + res.status + ').</div>';
                return;
            }
            renderDuty(await res.json());
        } catch (err) {
            bodyEl.innerHTML = '<div class="dd-empty">Failed to load duty.</div>';
        }
    }

    // ── Acknowledge ─────────────────────────────────────────────────────
    btnAck.addEventListener('click', async () => {
        if (!currentDuty || !currentDuty.id) return;
        btnAck.disabled = true;
        btnAck.textContent = 'Acknowledging…';
        try {
            const fd = new FormData();
            fd.append('_csrf_token', '<?= e($csrf) ?>');
            fd.append('roster_id', String(currentDuty.id));
            const res = await fetch('/my-roster/acknowledge', {
                method: 'POST', credentials: 'same-origin', body: fd,
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (!res.ok || !data.ok) {
                showToast(data.error || 'Could not acknowledge', true);
                btnAck.disabled = false;
                btnAck.innerHTML = '<?= str_replace("'", "\\'", sidebarIcon('check-badge', 14)) ?> Acknowledge Roster';
                return;
            }
            currentDuty.is_acknowledged = true;
            currentDuty.acknowledged_at = data.acknowledged_at;
            renderDuty(currentDuty);
            showToast('Roster acknowledged');
            // mark the calendar/list cell visually
            const cell = document.querySelector('[data-duty-id="' + currentDuty.id + '"]');
            if (cell) cell.classList.add('is-acknowledged');
        } catch (err) {
            showToast('Network error', true);
            btnAck.disabled = false;
        }
    });

    // ── Request modals ──────────────────────────────────────────────────
    function openQm(type) {
        if (!currentDuty) return;
        qmType.value = type;
        qmRoster.value = currentDuty.id;
        const code = currentDuty.duty_type_code || '';
        qmPill.textContent = code;
        qmPill.style.background = currentDuty.duty_type_bg || '#1e293b';
        qmPill.style.color = currentDuty.duty_type_color || '#e8eaf0';
        if (type === 'leave_request') {
            qmTitle.textContent = 'Request leave';
            qmMessage.placeholder = 'Type of leave (annual / sick / study), reason, and any preferred dates.';
            qmMessage.value = 'Leave request for ' + currentDuty.date + '.\n\n';
        } else {
            qmTitle.textContent = 'Request roster correction';
            qmMessage.placeholder = 'Describe what is incorrect on this duty and what it should be.';
            qmMessage.value = 'Roster correction for ' + currentDuty.date + ' (' + currentDuty.duty_type_label + ').\n\n';
        }
        qmCharCount.textContent = qmMessage.value.length;
        qmDate.textContent = fmtDate(currentDuty.date) + ' · ' + currentDuty.duty_type_label;
        qmOverlay.classList.add('is-open');
        setTimeout(() => qmMessage.focus(), 50);
    }
    function closeQm() { qmOverlay.classList.remove('is-open'); }

    btnCorr.addEventListener('click',  () => openQm('correction'));
    btnLeave.addEventListener('click', () => openQm('leave_request'));
    qmCancel.addEventListener('click', closeQm);
    qmCloseB.addEventListener('click', closeQm);
    qmOverlay.addEventListener('click', (ev) => { if (ev.target === qmOverlay) closeQm(); });
    qmMessage.addEventListener('input', () => { qmCharCount.textContent = qmMessage.value.length; });

    // ── Wire date/list cells ────────────────────────────────────────────
    document.querySelectorAll('[data-duty-id]').forEach((el) => {
        el.addEventListener('click', () => {
            const id = el.getAttribute('data-duty-id');
            if (!id) return;
            // mark as selected in calendar
            document.querySelectorAll('.myr-day.is-selected').forEach(s => s.classList.remove('is-selected'));
            if (el.classList.contains('myr-day')) el.classList.add('is-selected');
            loadDuty(id, true);
        });
    });

    overlay.addEventListener('click', closeDrawer);
    btnClose.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        if (qmOverlay.classList.contains('is-open')) { closeQm(); return; }
        if (drawer.classList.contains('is-open')) closeDrawer();
    });

    // ── Optional: auto-load today's duty into the quick card ────────────
    const todayCell = document.querySelector('.myr-day.is-today[data-duty-id]')
                   || document.querySelector('[data-duty-id]');
    if (todayCell) {
        const id = todayCell.getAttribute('data-duty-id');
        if (id) loadDuty(id, false);  // load without opening drawer
    }
})();
</script>
