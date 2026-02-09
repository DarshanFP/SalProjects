/**
 * budget-calculations.js
 * Centralized budget arithmetic. Formulas match DerivedCalculationService.
 * Phase 2.4 — JS Budget Arithmetic Centralization (Step 1)
 */
(function () {
    'use strict';

    /**
     * Calculate row total: rateQuantity × rateMultiplier × rateDuration
     * Matches DerivedCalculationService::calculateRowTotal
     */
    function calculateRowTotal(rateQuantity, rateMultiplier, rateDuration) {
        var q = parseFloat(rateQuantity) || 0;
        var m = parseFloat(rateMultiplier) || 0;
        var d = parseFloat(rateDuration) || 0;
        return q * m * d;
    }

    /**
     * Calculate phase total as sum of row totals.
     * Accepts array of objects with rate_quantity, rate_multiplier, rate_duration
     * or array of { rateQuantity, rateMultiplier, rateDuration }.
     * Matches DerivedCalculationService::calculatePhaseTotal
     */
    function calculatePhaseTotal(rows) {
        var total = 0;
        var i;
        for (i = 0; i < rows.length; i++) {
            var row = rows[i];
            var q = parseFloat(row.rate_quantity !== undefined ? row.rate_quantity : row.rateQuantity) || 0;
            var m = parseFloat(row.rate_multiplier !== undefined ? row.rate_multiplier : row.rateMultiplier) || 0;
            var d = parseFloat(row.rate_duration !== undefined ? row.rate_duration : row.rateDuration) || 0;
            total += calculateRowTotal(q, m, d);
        }
        return total;
    }

    /**
     * Calculate project total from phase totals or numeric values.
     * Matches DerivedCalculationService::calculateProjectTotal
     */
    function calculateProjectTotal(values) {
        var total = 0;
        var i;
        for (i = 0; i < values.length; i++) {
            var v = values[i];
            if (typeof v === 'number' && !isNaN(v)) {
                total += v;
            } else {
                total += parseFloat(v) || 0;
            }
        }
        return total;
    }

    /**
     * Calculate remaining balance: total budget minus total expenses.
     * Matches DerivedCalculationService::calculateRemainingBalance
     */
    function calculateRemainingBalance(totalBudget, totalExpenses) {
        return (parseFloat(totalBudget) || 0) - (parseFloat(totalExpenses) || 0);
    }

    /**
     * Calculate amount sanctioned: overall budget minus combined (forwarded + local).
     * Matches budget field: amountSanctioned = overallBudget - combinedSanctioned
     */
    function calculateAmountSanctioned(overallBudget, combinedSanctioned) {
        return (parseFloat(overallBudget) || 0) - (parseFloat(combinedSanctioned) || 0);
    }

    window.BudgetCalculations = {
        calculateRowTotal: calculateRowTotal,
        calculatePhaseTotal: calculatePhaseTotal,
        calculateProjectTotal: calculateProjectTotal,
        calculateRemainingBalance: calculateRemainingBalance,
        calculateAmountSanctioned: calculateAmountSanctioned
    };
})();
