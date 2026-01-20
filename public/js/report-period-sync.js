/**
 * Report Period Sync
 *
 * When user selects Reporting Month & Year in Basic Information, this script
 * propagates the same month/date to:
 * - Activity "Reporting Month" selects (objectives) → same month (1-12 or month name)
 * - Outlook "Date" inputs → last day of the selected month (YYYY-MM-DD)
 * - Account Statement Period (budget): start = 1st of month, end = last day of month
 *
 * Supports:
 * - #report_month + #report_year (ReportAll, edit, ReportCommonForm)
 * - input[name="report_month_year"] type=month (developmentProject, value YYYY-MM)
 * - #reporting_period_month + #reporting_period_year (quarterly, if present)
 */
(function () {
    'use strict';

    var MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];

    function getReportMonthYear() {
        var monthEl = document.getElementById('report_month');
        var yearEl = document.getElementById('report_year');
        if (monthEl && yearEl && monthEl.value && yearEl.value) {
            return { month: parseInt(monthEl.value, 10), year: parseInt(yearEl.value, 10) };
        }
        var monthInput = document.querySelector('input[name="report_month_year"]');
        if (monthInput && monthInput.type === 'month' && monthInput.value) {
            var parts = monthInput.value.split('-');
            if (parts.length === 2) {
                return { month: parseInt(parts[1], 10), year: parseInt(parts[0], 10) };
            }
        }
        var qm = document.getElementById('reporting_period_month') || document.querySelector('select[name="reporting_period_month"]');
        var qy = document.getElementById('reporting_period_year') || document.querySelector('select[name="reporting_period_year"]');
        if (qm && qy && qm.value && qy.value) {
            return { month: parseInt(qm.value, 10), year: parseInt(qy.value, 10) };
        }
        return null;
    }

    function lastDayOfMonth(year, month) {
        return new Date(year, month, 0).getDate();
    }

    function formatDate(year, month, day) {
        var m = String(month).padStart(2, '0');
        var d = String(day).padStart(2, '0');
        return year + '-' + m + '-' + d;
    }

    function getFirstDayOfMonth(year, month) {
        return formatDate(year, month, 1);
    }

    function getLastDayOfMonth(year, month) {
        var last = lastDayOfMonth(year, month);
        return formatDate(year, month, last);
    }

    /**
     * Set value on a month select. Handles both:
     * - value="1".."12" (objectives create/edit)
     * - value="January".."December" (e.g. developmentProject)
     */
    function setMonthSelectValue(select, month) {
        if (!select || !month) return;
        var str = String(month);
        if (select.value !== str) {
            select.value = str;
        }
        if (select.value !== str) {
            var name = MONTH_NAMES[month - 1];
            if (name) {
                select.value = name;
            }
        }
    }

    function syncReportPeriodToSections() {
        var p = getReportMonthYear();
        if (!p || !p.month || !p.year) return;

        var month = p.month;
        var year = p.year;
        var firstDay = getFirstDayOfMonth(year, month);
        var lastDay = getLastDayOfMonth(year, month);

        // 1) Activity "Reporting Month" / "Month" selects: name^="month["
        var monthSelects = document.querySelectorAll('select[name^="month["]');
        monthSelects.forEach(function (el) {
            setMonthSelectValue(el, month);
        });

        // 2) Outlook "Date" inputs: name^="date[" → last day of month
        var dateInputs = document.querySelectorAll('input[name^="date["]');
        dateInputs.forEach(function (el) {
            if (el.type === 'date') {
                el.value = lastDay;
            }
        });

        // 3) Account Statement Period: start = 1st, end = last day
        var startEls = document.querySelectorAll('input[name="account_period_start"]');
        var endEls = document.querySelectorAll('input[name="account_period_end"]');
        startEls.forEach(function (el) {
            if (el.type === 'date') el.value = firstDay;
        });
        endEls.forEach(function (el) {
            if (el.type === 'date') el.value = lastDay;
        });
    }

    function setupListeners() {
        var run = function () { syncReportPeriodToSections(); };

        var m = document.getElementById('report_month');
        var y = document.getElementById('report_year');
        if (m) m.addEventListener('change', run);
        if (y) y.addEventListener('change', run);

        var monthInput = document.querySelector('input[name="report_month_year"]');
        if (monthInput) monthInput.addEventListener('change', run);

        var qm = document.getElementById('reporting_period_month') || document.querySelector('select[name="reporting_period_month"]');
        var qy = document.getElementById('reporting_period_year') || document.querySelector('select[name="reporting_period_year"]');
        if (qm) qm.addEventListener('change', run);
        if (qy) qy.addEventListener('change', run);
    }

    function init() {
        setupListeners();
        // On load, if Reporting Month & Year are already selected, propagate
        syncReportPeriodToSections();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose for addOutlook / addActivity to call after adding new rows
    window.syncReportPeriodToSections = syncReportPeriodToSections;
})();
