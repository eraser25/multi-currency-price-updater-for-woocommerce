jQuery(document).ready(function($) {
    let currencyRates = {};
    let products = [];
    let updateInterval = parseInt($('#mcpufwUpdateInterval').val());
    let intervalId;

    // Notification function
    function showNotification(message, type = 'success') {
        const notification = $('<div class="mcpufw-notification mcpufw-notification-' + type + '">' + message + '</div>');
        $('#mcpufwNotificationContainer').append(notification);
        setTimeout(() => notification.remove(), 3000);
    }

    // Fetch exchange rates
    async function fetchExchangeRates() {
        $('#mcpufwLoadingSpinner').show();
        
        try {
            const response = await fetch('https://finans.truncgil.com/v4/today.json');
            const data = await response.json();
            
            currencyRates = {
                'USD': parseFloat(data.USD.Selling),
                'EUR': parseFloat(data.EUR.Selling),
                'GBP': parseFloat(data.GBP.Selling),
                'JPY': parseFloat(data.JPY.Selling),
                'CAD': parseFloat(data.CAD.Selling),
                'TRY': 1.0
            };
            
            updateCurrencyDisplay();
            updatePricesWithRates();
            updateStats();
            
            // Save rates to server
            await fetch(mcpufw_ajax.ajax_url + '?action=mcpufw_save_current_rates', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ rates: currencyRates })
            });
            
        } catch (error) {
            console.error('Currency rate error:', error);
            showNotification('Error fetching exchange rates', 'error');
        } finally {
            $('#mcpufwLoadingSpinner').hide();
        }
    }

    // Initialize the plugin
    fetchExchangeRates();
    fetchProducts();
    
    // Set auto-update interval
    intervalId = setInterval(fetchExchangeRates, updateInterval * 1000);
    
    // Event handlers
    $('#mcpufwSaveInterval').on('click', function() {
        const newInterval = parseInt($('#mcpufwUpdateInterval').val());
        updateInterval = newInterval;
        
        clearInterval(intervalId);
        intervalId = setInterval(fetchExchangeRates, updateInterval * 1000);
        
        saveUpdateInterval(newInterval);
        showNotification('Update interval saved', 'success');
    });
});