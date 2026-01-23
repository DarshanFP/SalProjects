# Photo–Activity Mapping (Photo Rearrangement)

This folder contains the viability review for replacing photo **descriptions** with **activity mapping** in reports: linking up to 3 photos per objective/activity and showing them with activities in the view.

## Documents

- **[Photo-Activity-Mapping_Viability_Review.md](./Photo-Activity-Mapping_Viability_Review.md)** – Feasibility for create, edit, and view across monthly and quarterly reports and all project types.
- **[Photo_Optimization_Service_Proposal.md](./Photo_Optimization_Service_Proposal.md)** – Service to shrink report photos (resize, JPEG, strip EXIF) for minimal storage, WhatsApp-style, with fallback to original so uploads don’t break.
- **[Current_Photo_Naming_And_Storage.md](./Current_Photo_Naming_And_Storage.md)** – Current photo naming and storage folders for monthly and quarterly reports.
- **[Phase_Wise_Implementation_Plan.md](./Phase_Wise_Implementation_Plan.md)** – Phase-wise implementation plan for activity mapping, optimization, activity-based naming, and location display.
- **[Status_Completed_And_Remaining.md](./Status_Completed_And_Remaining.md)** – What has been completed and what remains (phases, tests, Phase 9, environment).
- **[Issues_And_Discrepancies.md](./Issues_And_Discrepancies.md)** – Issues, doc-vs-code discrepancies, and gaps identified from reviewing all docs and the implementation.
- **[Final_Phase_Wise_Implementation_Plan.md](./Final_Phase_Wise_Implementation_Plan.md)** – Phase-wise plan to **fix** and **complete** all remaining work (edit flow, tests, environment, docs); use after Phases 1–8.

## Summary

- **Monthly reports:** Viable for create, edit, and view. Requires `activity_id` on `DP_Photos`, activity selector in photo partials, and view/export grouping by activity.
- **Quarterly reports:** Partially viable; depends on adding `activity_id` (or equivalent) to the relevant photo tables and aligning forms.
- **View:** Photos are shown with the activities (e.g. under each activity in the Objectives section) instead of a separate block grouped by description.
- **Photo optimization:** A dedicated service resizes and re-encodes report photos to JPEG, caps stored size at **350 KB** (by lowering quality and/or resolution if needed), extracts and stores GPS before stripping other EXIF; on error it falls back to the original so storage never breaks. Can be implemented independently of the activity-mapping change.
