/**
 * Customer Search Modal
 * Handles customer search with balance details and pagination
 */

(function() {
    'use strict';

    let currentPage = 1;
    let searchTerm = '';
    const limit = 10;

    // Open modal
    $(document).on('click', '#openCustomerSearchModal', function() {
        currentPage = 1;
        searchTerm = '';
        $('#custSearchInput').val('');
        $('#customerSearchModal').modal('show');
        loadCustomers();
    });

    // Search input with debounce
    let searchTimeout;
    $(document).on('input', '#custSearchInput', function() {
        clearTimeout(searchTimeout);
        searchTerm = $.trim($(this).val());
        searchTimeout = setTimeout(function() {
            currentPage = 1;
            loadCustomers();
        }, 400);
    });

    // Pagination click
    $(document).on('click', '#custSearchPagination .page-link', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        if (!href) return;
        
        const match = href.match(/\/(\d+)$/);
        if (match) {
            currentPage = parseInt(match[1]);
            loadCustomers();
        }
    });

    // Customer selection
    $(document).on('click', '.cust-search-item', function() {
        const custUID = $(this).data('uid');
        const custName = $(this).data('name');
        
        if (custUID && custName) {
            // Update Select2 dropdown
            const $select = $('#customerSearch');
            
            // Check if option exists, if not create it
            if ($select.find('option[value="' + custUID + '"]').length === 0) {
                const newOption = new Option(custName, custUID, true, true);
                $select.append(newOption);
            } else {
                $select.val(custUID);
            }
            
            $select.trigger('change');
            
            // Close modal
            $('#customerSearchModal').modal('hide');
        }
    });

    // Load customers function
    function loadCustomers() {
        const $results = $('#custSearchResults');
        
        // Show loading
        $results.html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');

        $.ajax({
            url: '/customers/getCustomerSearchList',
            method: 'POST',
            data: {
                PageNo: currentPage,
                RowLimit: limit,
                Search: searchTerm,
                [CsrfName]: CsrfToken
            },
            success: function(response) {
                if (response.Error) {
                    $results.html('<div class="text-center py-5 text-danger"><i class="bx bx-error-circle fs-3 d-block mb-2"></i>' + (response.Message || 'Error loading customers') + '</div>');
                    return;
                }

                if (!response.Customers || response.Customers.length === 0) {
                    $results.html('<div class="text-center py-5 text-muted"><i class="bx bx-user-x fs-3 d-block mb-2"></i>No customers found</div>');
                    $('#custSearchPagination').html('');
                    return;
                }

                // Build customer list HTML
                let html = '';
                response.Customers.forEach(function(cust) {
                    const balance = parseFloat(cust.Balance || 0);
                    const balanceType = cust.BalanceType || 'Debit';
                    const balanceClass = balanceType === 'Debit' ? 'debit' : 'credit';
                    const balanceLabel = balanceType === 'Debit' ? 'Receivable' : 'Payable';
                    const currency = (typeof CurrencySymbol !== 'undefined' && CurrencySymbol) ? CurrencySymbol : '₹';
                    
                    html += '<div class="cust-search-item" data-uid="' + cust.CustomerUID + '" data-name="' + escapeHtml(cust.Name) + '">';
                    html += '<div class="d-flex justify-content-between align-items-start">';
                    html += '<div class="flex-grow-1">';
                    html += '<div class="cust-name">' + escapeHtml(cust.Name) + '</div>';
                    
                    if (cust.Area) {
                        html += '<div class="cust-meta"><i class="bx bx-map me-1"></i>' + escapeHtml(cust.Area) + '</div>';
                    }
                    
                    if (cust.MobileNumber) {
                        html += '<div class="cust-meta"><i class="bx bx-phone me-1"></i>' + escapeHtml(cust.MobileNumber) + '</div>';
                    }
                    
                    html += '</div>';
                    html += '<div class="text-end">';
                    html += '<div class="cust-balance ' + balanceClass + '">' + currency + ' ' + formatNumber(balance, 2) + '</div>';
                    html += '<div class="cust-meta">' + balanceLabel + '</div>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                });

                $results.html(html);
                $('#custSearchPagination').html(response.Pagination || '');
            },
            error: function() {
                $results.html('<div class="text-center py-5 text-danger"><i class="bx bx-error-circle fs-3 d-block mb-2"></i>Failed to load customers</div>');
            }
        });
    }

    // Helper function to escape HTML
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text || '').replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Helper function to format numbers
    function formatNumber(num, decimals) {
        return parseFloat(num || 0).toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

})();
