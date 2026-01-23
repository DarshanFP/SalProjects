# Reports — Provincial Monitoring

This folder holds documentation for **Provincial monitoring and analysis** of **monthly reports** (objectives/activities and budget).

## Contents

| Document | Purpose |
|----------|---------|
| **Provincial_Monthly_Report_Monitoring_Guide.md** | Full guide: data models; activity checks (scheduled not reported, reported not scheduled, ad‑hoc); budget checks (overspend, negative balance, utilisation); **project-type-specific checks** (LDP, IGE, RST, CIC, Individual, Development/CCI/Rural-Urban-Tribal/NEXT PHASE, beneficiary consistency); manual checklist; implementation outline. |
| **Provincial_Monthly_Report_Monitoring_Implementation_Plan.md** | **Phase-wise plan for the Provincial Guide:** Phase 1 Foundation, 2 Activity, 3 Budget, 4 Type-specific (LDP/IGE/RST/CIC), 5 Type-specific (Individual/Development/Beneficiary), 6 Integration and testing. Tasks, files, section refs. |
| **Report_View_Entered_Fields_Visual_Proposal.md** | Proposal for visual distinction in **report view** (all roles): “report-entered” vs “from-project” fields. Create/edit use a distinct background for entry fields; view currently does not. Proposes CSS classes `report-value-entered` / `report-cell-entered`, green left border + light background, field list per partial, and implementation steps. |
| **Phase_Wise_Implementation_Plan.md** | Phase 1 (Report view entered-fields) done. Phases 2–5: Activity, Budget, Type-specific, Integration. |
| **Implementation_Log.md** | **Log of what is implemented:** Option E (green accent), Activity monitoring (inline badges, activity_monitoring block), Budget and Type-specific monitoring, Photos (unassigned, photo_location), Objectives (per-activity photos, heading), SoA table and Budget Row badge, Provincial Forward/Revert and Back to Reports. Visibility, vars, files. |

## Scope

- **Objectives & Activities:** Compare project plan (objectives → activities → timeframes with `month`, `is_active`) vs report (DPObjective, DPActivity with `project_activity_id`, `month`).
- **Budget:** Compare project/sanctioned amounts vs report expenses (DPAccountDetail: `particulars`, `amount_sanctioned`, `total_expenses`, `balance_amount`).
- **Project-type-specific:** LDP (QRDLAnnexure), IGE (RQISAgeProfile), RST (RQSTTraineeProfile, `report->education`), CIC (RQWDInmatesProfile), Individual (ILP/IAH/IES/IIES budget and `total_beneficiaries`), Development/CCI/Rural-Urban-Tribal/NEXT PHASE (phase, `total_beneficiaries`), and beneficiary consistency across all types.

## Audience

- **Provincial users:** To monitor and analyse reports submitted by Executors/Applicants.
- **Developers:** To implement the suggested `ReportMonitoringService` and view partials.

## See also

- `Documentations/V1/Reports/COMPREHENSIVE_REPORTS_REVIEW.md` — Overall reports review.
- `Documentations/V1/Reports/TASKS_AND_STATUS.md` — Tasks and status.
- `Phase_Wise_Implementation_Plan.md` — Implementation order for all MONITORING items.
- `Implementation_Log.md` — Consolidated log of implemented features (report view, monitoring, photos, objectives, SoA, Provincial actions).
