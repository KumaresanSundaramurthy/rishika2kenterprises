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
        $('#custSearchClear').toggleClass('d-none', searchTerm === '');
        // Clear pagination immediately on new search
        $('#custSearchPagination').html('');
        $('#custSearchPageInfo').text('');
        $('#custSearchPaginationWrap').addClass('d-none');
        searchTimeout = setTimeout(function() {
            currentPage = 1;
            loadCustomers();
        }, 400);
    });

    // Clear search
    $(document).on('click', '#custSearchClear', function() {
        $('#custSearchInput').val('').trigger('input');
    });

    // Pagination click
    $(document).on('click', '#custSearchPagination .page-link', function(e) {
        e.preventDefault();
        var page = parseInt($(this).data('page'));
        if (!page || page < 1) return;
        currentPage = page;
        loadCustomers();
    });

    // Customer selection
    $(document).on('click', '.cust-search-item', function() {
        const custUID    = $(this).data('uid');
        const custName   = $(this).data('name');
        const address    = $(this).data('address');
        const state      = $(this).data('state');
        const countryISO2 = $(this).data('country') || 'IN';

        if (custUID && custName) {
            const $select = $('#customerSearch');

            if ($select.find('option[value="' + custUID + '"]').length === 0) {
                const newOption = new Option(custName, custUID, true, true);
                $select.append(newOption);
            } else {
                $select.val(custUID);
            }
            $select.trigger('change');

            // Show address box
            if (address) {
                var addrHtml = '<div><strong>Shipping Address:</strong></div>'
                             + '<div>' + (address.Line1 || '') + '</div>'
                             + '<div>' + (address.Line2 || '') + '</div>'
                             + '<div>' + [address.City, address.State].filter(Boolean).join(', ') + (address.Pincode ? ' - ' + address.Pincode : '') + '</div>';
                $('#customerAddressBox').html(addrHtml).removeClass('d-none');
                if (typeof window._onCustStateSelected === 'function') {
                    window._onCustStateSelected((state || (address && address.State) || '').trim());
                }
            } else {
                $('#customerAddressBox').addClass('d-none').empty();
            }

            // Show customer type indicator (also sets billManager inter-state)
            if (typeof _showCustTypeIndicator === 'function') {
                _showCustTypeIndicator({
                    countryISO2: countryISO2,
                    address: address || null
                });
            }

            $('#customerSearchModal').modal('hide');
        }
    });

    // Load customers function
    function loadCustomers() {
        const $results = $('#custSearchResults');
        AjaxLoading = 0;
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
                    AjaxLoading = 1;
                    $results.html('<div class="text-center py-5 text-danger"><i class="bx bx-error-circle fs-3 d-block mb-2"></i>' + (response.Message || 'Error loading customers') + '</div>');
                    return;
                }

                if (!response.Customers || response.Customers.length === 0) {
                    AjaxLoading = 1;
                    var noResHtml =
                        '<div class="text-center py-4 text-muted">' +
                            '<i class="bx bx-user-x fs-3 d-block mb-2"></i>' +
                            '<div class="mb-3" style="font-size:.9rem;">No results — create Customer</div>' +
                            '<button type="button" class="btn btn-primary btn-sm px-3" id="custSearchCreateBtn">' +
                                '<i class="bx bx-plus me-1"></i>Create Customer' +
                            '</button>' +
                        '</div>';
                    $results.html(noResHtml);
                    $('#custSearchPagination').html('');
                    $('#custSearchPageInfo').text('');
                    $('#custSearchPaginationWrap').addClass('d-none');
                    return;
                }

                // Build customer list HTML
                let html = '';
                response.Customers.forEach(function(cust, index) {
                    const balance = parseFloat(cust.Balance || 0);
                    const balanceType = cust.BalanceType || 'Debit';
                    const balanceClass = balanceType === 'Debit' ? 'debit' : 'credit';
                    const balanceLabel = balanceType === 'Debit' ? 'Receivable' : 'Payable';
                    const currency = (typeof CurrencySymbol !== 'undefined' && CurrencySymbol) ? CurrencySymbol : '₹';
                    const addrAttr    = cust.address ? ' data-address=\'' + JSON.stringify(cust.address).replace(/'/g, '&#39;') + '\'' : '';
                    const stateAttr   = cust.address ? ' data-state="' + escapeHtml(cust.address.State || '') + '"' : '';
                    const countryAttr = ' data-country="' + escapeHtml(cust.CountryISO2 || 'IN') + '"';
                    const serialNo    = ((currentPage - 1) * limit) + index + 1;

                    html += '<div class="cust-search-item" data-uid="' + cust.CustomerUID + '" data-name="' + escapeHtml(cust.Name) + '"' + addrAttr + stateAttr + countryAttr + '>';
                    html += '<div class="d-flex align-items-start gap-2">';
                    html += '<div class="cust-serial">' + serialNo + '</div>';
                    html += '<div class="flex-grow-1">';
                    html += '<div class="cust-name">' + escapeHtml(cust.Name) + '</div>';

                    if (cust.Area) {
                        html += '<div class="cust-meta"><i class="bx bx-map me-1"></i>' + escapeHtml(cust.Area) + '</div>';
                    }
                    if (cust.MobileNumber) {
                        html += '<div class="cust-meta"><i class="bx bx-phone me-1"></i>' + escapeHtml(cust.MobileNumber) + '</div>';
                    }
                    if (cust.address && (cust.address.Line1 || cust.address.City)) {
                        var addrParts = [cust.address.Line1, cust.address.City, cust.address.State].filter(Boolean);
                        html += '<div class="cust-meta"><i class="bx bx-buildings me-1"></i>' + escapeHtml(addrParts.join(', ')) + '</div>';
                    }
                    
                    html += '</div>'; // flex-grow-1
                    html += '<div class="text-end flex-shrink-0">';
                    html += '<div class="cust-balance ' + balanceClass + '">' + currency + ' ' + formatNumber(balance, 2) + '</div>';
                    html += '<div class="cust-meta">' + balanceLabel + '</div>';
                    html += '</div>';
                    html += '</div>'; // d-flex
                    html += '</div>'; // cust-search-item
                });

                $results.html(html);

                // Build pagination manually from TotalCount
                var total = parseInt(response.TotalCount || 0);
                var from  = ((currentPage - 1) * limit) + 1;
                var to    = Math.min(currentPage * limit, total);
                var totalPages = Math.ceil(total / limit);

                $('#custSearchPageInfo').text(total > 0 ? 'Showing ' + from + ' – ' + to + ' of ' + total + ' Results' : '');

                var paginHtml = '';
                if (totalPages > 1) {
                    var start = Math.max(1, currentPage - 2);
                    var end   = Math.min(totalPages, start + 4);
                    start = Math.max(1, end - 4);

                    paginHtml += '<li class="page-item' + (currentPage <= 1 ? ' disabled' : '') + '">'
                              +    '<a class="page-link" href="#" data-page="' + (currentPage - 1) + '">&lsaquo;</a>'
                              + '</li>';
                    for (var p = start; p <= end; p++) {
                        paginHtml += '<li class="page-item' + (p === currentPage ? ' active' : '') + '">'
                                  +    '<a class="page-link" href="#" data-page="' + p + '">' + p + '</a>'
                                  + '</li>';
                    }
                    paginHtml += '<li class="page-item' + (currentPage >= totalPages ? ' disabled' : '') + '">'
                              +    '<a class="page-link" href="#" data-page="' + (currentPage + 1) + '">&rsaquo;</a>'
                              + '</li>';
                }
                $('#custSearchPagination').html(paginHtml);
                $('#custSearchPaginationWrap').removeClass('d-none');
                AjaxLoading = 1;
            },
            error: function() {
                AjaxLoading = 1;
                $results.html('<div class="text-center py-5 text-danger"><i class="bx bx-error-circle fs-3 d-block mb-2"></i>Failed to load customers</div>');
            }
        });
    }

    // ── Create Customer — triggered from header button or no-results button ────
    function openCreateCustomerModal() {
        var prefill = $('#custSearchInput').val().trim();
        $('#customerSearchModal').modal('hide');
        setTimeout(function () {
            CustomerForm.open('add', null, {
                prefillName  : prefill,
                onSaveSuccess: function (response) {
                    var c = response.Customer;
                    if (!c || !c.id) return;
                    var $select = $('#customerSearch');
                    if ($select.find('option[value="' + c.id + '"]').length === 0) {
                        $select.append(new Option(c.text, c.id, true, true));
                    } else {
                        $select.val(c.id);
                    }
                    $select.trigger('change');
                    $select.trigger({ type: 'select2:select', params: { data: { id: c.id, text: c.text } } });
                    if (c.address) {
                        var a = c.address;
                        var addrHtml = '<div><strong>Shipping Address:</strong></div>'
                            + '<div>' + (a.Line1 || '') + '</div>'
                            + '<div>' + (a.Line2 || '') + '</div>'
                            + '<div>' + [a.City, a.State].filter(Boolean).join(', ') + (a.Pincode ? ' - ' + a.Pincode : '') + '</div>';
                        $('#customerAddressBox').html(addrHtml).removeClass('d-none');
                    } else {
                        $('#customerAddressBox').addClass('d-none').empty();
                    }
                }
            });
        }, 300); // wait for search modal to fully close
    }

    // Header "+ Create Customer" button
    $(document).on('click', '#btnCreateCustomerFromSearch', function () {
        openCreateCustomerModal();
    });

    // No-results "+ Create Customer" button (dynamically rendered)
    $(document).on('click', '#custSearchCreateBtn', function () {
        openCreateCustomerModal();
    });

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
