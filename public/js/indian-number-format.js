/**
 * Indian Number Formatting Helper Functions
 * 
 * Converts numbers from American style (1,000,000) to Indian style (10,00,000)
 * Indian numbering system: First 3 digits, then every 2 digits (lakhs, crores)
 */

/**
 * Format number in Indian style (lakhs, crores)
 * Example: 1000000 becomes "10,00,000"
 * 
 * @param {number} number - The number to format
 * @param {number} decimals - Number of decimal places (default: 2)
 * @returns {string} Formatted number string
 */
function formatIndianNumber(number, decimals = 2) {
    // Handle null, undefined, or non-numeric values
    if (number === null || number === undefined || isNaN(number)) {
        number = 0;
    }

    // Convert to number if string
    number = parseFloat(number);

    if (number == 0) {
        return parseFloat(0).toFixed(decimals);
    }

    const negative = number < 0;
    number = Math.abs(number);

    // Split into integer and decimal parts
    const fixed = number.toFixed(decimals);
    const parts = fixed.split('.');
    let integerPart = parts[0];
    const decimalPart = parts[1] || '';

    // Format integer part in Indian style
    let formattedInteger = '';
    const length = integerPart.length;

    if (length <= 3) {
        formattedInteger = integerPart;
    } else {
        // First 3 digits from right
        formattedInteger = integerPart.slice(-3);
        let remaining = integerPart.slice(0, -3);

        // Then every 2 digits
        while (remaining.length > 2) {
            formattedInteger = remaining.slice(-2) + ',' + formattedInteger;
            remaining = remaining.slice(0, -2);
        }

        if (remaining.length > 0) {
            formattedInteger = remaining + ',' + formattedInteger;
        }
    }

    let result = formattedInteger;
    if (decimals > 0 && decimalPart) {
        result += '.' + decimalPart;
    } else if (decimals > 0 && !decimalPart) {
        // Add decimal part with zeros if needed
        result += '.' + '0'.repeat(decimals);
    }

    return (negative ? '-' : '') + result;
}

/**
 * Format currency in Indian style with Rs. prefix
 * 
 * @param {number} number - The number to format
 * @param {number} decimals - Number of decimal places (default: 2)
 * @returns {string} Formatted currency string
 */
function formatIndianCurrency(number, decimals = 2) {
    return 'Rs. ' + formatIndianNumber(number, decimals);
}

/**
 * Format percentage in Indian style
 * 
 * @param {number} number - The percentage value
 * @param {number} decimals - Number of decimal places (default: 1)
 * @returns {string} Formatted percentage string
 */
function formatIndianPercentage(number, decimals = 1) {
    return formatIndianNumber(number, decimals) + '%';
}

/**
 * Format number as integer in Indian style (no decimals)
 * 
 * @param {number} number - The number to format
 * @returns {string} Formatted integer string
 */
function formatIndianInteger(number) {
    return formatIndianNumber(number, 0);
}

/**
 * Format number using toLocaleString with Indian locale
 * This is a wrapper that ensures consistent Indian formatting
 * 
 * @param {number} number - The number to format
 * @param {Object} options - Intl.NumberFormat options
 * @returns {string} Formatted number string
 */
function formatIndianLocale(number, options = {}) {
    // Handle null, undefined, or non-numeric values
    if (number === null || number === undefined || isNaN(number)) {
        number = 0;
    }

    const defaultOptions = {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        ...options
    };
    
    return parseFloat(number).toLocaleString('en-IN', defaultOptions);
}

/**
 * Format currency using toLocaleString with Indian locale and Rs. prefix
 * 
 * @param {number} number - The number to format
 * @param {Object} options - Intl.NumberFormat options
 * @returns {string} Formatted currency string
 */
function formatIndianLocaleCurrency(number, options = {}) {
    const defaultOptions = {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        ...options
    };
    
    return 'Rs. ' + formatIndianLocale(number, defaultOptions);
}

// Export functions for use in modules (if using ES6 modules)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        formatIndianNumber,
        formatIndianCurrency,
        formatIndianPercentage,
        formatIndianInteger,
        formatIndianLocale,
        formatIndianLocaleCurrency
    };
}
