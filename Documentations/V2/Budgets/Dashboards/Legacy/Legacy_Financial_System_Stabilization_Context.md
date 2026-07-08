# Legacy Financial System Stabilization

## 1. Background

Many projects were approved before the financial invariant architecture was introduced.

These legacy projects may contain:

- missing `opening_balance`
- incorrect `opening_balance`
- missing `amount_sanctioned`

## 2. Stabilization Phases

- **Phase 0 — Pre-Implementation Audit**
- **Phase 1 — Financial Invariant Rule Correction**
- **Phase 2 — Legacy Financial Data Repair**
- **Phase 3 — Dashboard Validation**
- **Phase 4 — Financial Integrity Verification**

## 3. Current Status

- Phase 0 completed
- Phase 1 completed
- Phase 2 pending

## 4. Repair Scope

27 projects require financial repair.

Excluded projects:

- DP-0041
- IIES-0060
- DP-0078
- DP-0080

These already satisfy the canonical rule.

## 5. Canonical Financial Rule

```
opening_balance = amount_sanctioned + amount_forwarded + local_contribution
```
