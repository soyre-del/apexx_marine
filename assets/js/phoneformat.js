document.addEventListener('DOMContentLoaded', function() {
        const countrySelect = document.getElementById('countryCodeSelect');
        const phoneInput = document.getElementById('phoneNumberInput');

        // Map country codes to their visual formats
        const phoneFormats = {
            '+63': '917 123 4567',        // Philippines
            '+1': '(555) 123-4567',       // US / Canada
            '+65': '8123 4567',           // Singapore
            '+86': '131 1234 5678',       // China
            '+31': '6 12345678',          // Netherlands
            '+971': '50 123 4567',        // UAE
            '+27': '82 123 4567',         // South Africa
            '+507': '6123-4567',          // Panama
            '+44': '7700 900077',         // UK
            'Sat': '8816 123 45678'       // Satellite Format
        };

        // Listen for the dropdown changing
        countrySelect.addEventListener('change', function() {
            const selectedCode = this.value;
            // Update the placeholder with the correct format
            if (phoneFormats[selectedCode]) {
                phoneInput.placeholder = phoneFormats[selectedCode];
            } else {
                phoneInput.placeholder = "Enter phone number";
            }
        });
    });