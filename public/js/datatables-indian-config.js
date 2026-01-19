/**
 * DataTables Indian Number Formatting Configuration
 * 
 * Configures DataTables to use Indian number formatting (lakhs, crores)
 * Include this file after DataTables library but before initializing tables
 */

(function() {
    'use strict';

    // Wait for DataTables to be available
    if (typeof $ === 'undefined' || typeof $.fn.dataTable === 'undefined') {
        console.warn('DataTables Indian Config: jQuery or DataTables not found. Make sure DataTables is loaded before this script.');
        return;
    }

    // Override default formatNumber function for DataTables
    if ($.fn.dataTable.defaults && typeof $.fn.dataTable.defaults.formatNumber === 'function') {
        $.fn.dataTable.defaults.formatNumber = function(toFormat) {
            // Use Indian formatting if function is available, otherwise fallback
            if (typeof formatIndianNumber !== 'undefined') {
                return formatIndianNumber(toFormat, 0);
            }
            // Fallback to default with Indian locale
            return parseFloat(toFormat).toLocaleString('en-IN');
        };
    }

    // Create custom number renderer for Indian format
    if ($.fn.dataTable.render && typeof $.fn.dataTable.render.number === 'function') {
        // Store original renderer
        var originalNumberRenderer = $.fn.dataTable.render.number;

        // Override with Indian formatting
        $.fn.dataTable.render.number = function(thousands, decimal, precision, prefix, postfix) {
            return {
                display: function(d) {
                    if (typeof d !== 'number' && typeof d !== 'string') {
                        return d;
                    }

                    if (d === '' || d === null || d === undefined) {
                        return d;
                    }

                    const number = parseFloat(d);
                    if (isNaN(number)) {
                        return d;
                    }

                    // Use Indian formatting if available
                    if (typeof formatIndianNumber !== 'undefined') {
                        const formatted = formatIndianNumber(number, precision || 0);
                        return (prefix || '') + formatted + (postfix || '');
                    }

                    // Fallback to toLocaleString with Indian locale
                    const options = {
                        minimumFractionDigits: precision || 0,
                        maximumFractionDigits: precision || 0
                    };
                    const formatted = number.toLocaleString('en-IN', options);
                    return (prefix || '') + formatted + (postfix || '');
                },
                filter: function(d) {
                    // For filtering, return the numeric value
                    return parseFloat(d) || 0;
                },
                type: 'num'
            };
        };
    }

    // Create a helper function for Indian currency renderer
    $.fn.dataTable.render.indianCurrency = function(precision = 2) {
        return {
            display: function(data, type, row) {
                if (type === 'display' || type === 'filter') {
                    const number = parseFloat(data) || 0;
                    if (typeof formatIndianCurrency !== 'undefined') {
                        return formatIndianCurrency(number, precision);
                    }
                    return 'Rs. ' + formatIndianNumber(number, precision);
                }
                return data;
            },
            filter: function(data) {
                return parseFloat(data) || 0;
            },
            type: 'num'
        };
    };

    // Create a helper function for Indian number renderer
    $.fn.dataTable.render.indianNumber = function(precision = 2) {
        return {
            display: function(data, type, row) {
                if (type === 'display' || type === 'filter') {
                    const number = parseFloat(data) || 0;
                    if (typeof formatIndianNumber !== 'undefined') {
                        return formatIndianNumber(number, precision);
                    }
                    return number.toLocaleString('en-IN', {
                        minimumFractionDigits: precision,
                        maximumFractionDigits: precision
                    });
                }
                return data;
            },
            filter: function(data) {
                return parseFloat(data) || 0;
            },
            type: 'num'
        };
    };

    // Example usage:
    // $('#myTable').DataTable({
    //     columnDefs: [{
    //         targets: [2], // Amount column
    //         render: $.fn.dataTable.render.indianCurrency(2)
    //     }]
    // });

})();
