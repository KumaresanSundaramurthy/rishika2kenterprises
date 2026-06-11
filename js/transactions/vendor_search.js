/**
 * Vendor Search Modal
 * Exact mirror of customer_search.js — calls /vendors/getVendorSearchList (PHP → DB)
 */

(function () {
    'use strict';

    let currentPage = 1;
    let searchTerm  = '';
    const limit     = 10;

    // ── Open modal ────────────────────────────────────────────────────────────
    $(document).on('click', '#openVendorSearchModal', function () {
        currentPage = 1;
        searchTerm  = '';
        $('#vendSearchInput').val('');
        $('#vendSearchClear').addClass('d-none');
        $('#vendorSearchModal').modal('show');
        loadVendors();
    });

    // ── Search input with debounce ────────────────────────────────────────────
    let searchTimeout;
    $(document).on('input', '#vendSearchInput', function () {
        clearTimeout(searchTimeout);
        searchTerm = $.trim($(this).val());
        $('#vendSearchClear').toggleClass('d-none', searchTerm === '');
        $('#vendSearchPagination').html('');
        $('#vendSearchPageInfo').text('');
        $('#vendSearchPaginationWrap').addClass('d-none');
        searchTimeout = setTimeout(function () {
            currentPage = 1;
            loadVendors();
        }, 400);
    });

    // ── Clear search ──────────────────────────────────────────────────────────
    $(document).on('click', '#vendSearchClear', function () {
        $('#vendSearchInput').val('').trigger('input');
    });

    // ── Pagination ────────────────────────────────────────────────────────────
    $(document).on('click', '#vendSearchPagination .page-link', function (e) {
        e.preventDefault();
        var page = parseInt($(this).data('page'));
        if (!page || page < 1) return;
        currentPage = page;
        loadVendors();
    });

    // ── Vendor row selection ──────────────────────────────────────────────────
    $(document).on('click', '.vend-search-item', function () {
        var vendUID  = $(this).data('uid');
        var vendName = $(this).data('name');
        var address  = $(this).data('address');
        var state    = $(this).data('state');

        if (!vendUID || !vendName) return;

        var $select = $('#vendorSearch');

        if ($select.find('option[value="' + vendUID + '"]').length === 0) {
            $select.append(new Option(vendName, vendUID, true, true));
        } else {
            $select.val(vendUID);
        }
        $select.trigger('change');

        // Show vendor address box
        if (address) {
            var addrHtml = '<div><strong>Billing Address:</strong></div>'
                         + '<div>' + (address.Line1 || '') + '</div>'
                         + '<div>' + (address.Line2 || '') + '</div>'
                         + '<div>' + [address.City, address.State].filter(Boolean).join(', ') + (address.Pincode ? ' - ' + address.Pincode : '') + '</div>';
            $('#vendorAddressBox').html(addrHtml).removeClass('d-none');
        } else {
            $('#vendorAddressBox').addClass('d-none').empty();
        }

        // Update inter-state flag
        if (typeof billManager !== 'undefined' && typeof _orgState !== 'undefined' && state) {
            billManager.setInterState(state.trim().toLowerCase() !== _orgState.trim().toLowerCase());
        }

        $('#vendorSearchModal').modal('hide');
    });

    // ── Load vendors from PHP endpoint (same as customer_search.js pattern) ──
    function loadVendors() {
        var $results = $('#vendSearchResults');
        AjaxLoading = 0;
        $results.html(
            '<div class="text-center py-5">' +
                '<div class="spinner-border text-primary" role="status">' +
                    '<span class="visually-hidden">Loading...</span>' +
                '</div>' +
            '</div>'
        );

        $.ajax({
            url    : '/vendors/getVendorSearchList',
            method : 'POST',
            data   : {
                PageNo   : currentPage,
                RowLimit : limit,
                Search   : searchTerm,
                [CsrfName]: CsrfToken,
            },
            success: function (response) {
                if (response.Error) {
                    AjaxLoading = 1;
                    $results.html(
                        '<div class="text-center py-5 text-danger">' +
                            '<i class="bx bx-error-circle fs-3 d-block mb-2"></i>' +
                            (response.Message || 'Error loading vendors') +
                        '</div>'
                    );
                    return;
                }

                if (!response.Vendors || response.Vendors.length === 0) {
                    AjaxLoading = 1;
                    $results.html(
                        '<div class="text-center py-4 text-muted">' +
                            '<i class="bx bx-store fs-3 d-block mb-2"></i>' +
                            '<div class="mb-3" style="font-size:.9rem;">No vendors found</div>' +
                            '<button type="button" class="btn btn-primary btn-sm px-3" id="vendSearchCreateBtn">' +
                                '<i class="bx bx-plus me-1"></i>Create Vendor' +
                            '</button>' +
                        '</div>'
                    );
                    $('#vendSearchPagination').html('');
                    $('#vendSearchPageInfo').text('');
                    $('#vendSearchPaginationWrap').addClass('d-none');
                    return;
                }

                // Build vendor list HTML
                var html     = '';
                var currency = (typeof CurrencySymbol !== 'undefined' && CurrencySymbol) ? CurrencySymbol : '₹';

                response.Vendors.forEach(function (vend, index) {
                    var balance     = parseFloat(vend.Balance || 0);
                    var balType     = vend.BalanceType || 'Credit';
                    var balClass    = balType === 'Credit' ? 'credit' : 'debit';
                    var balLabel    = balType === 'Credit' ? 'Payable'    : 'Receivable';
                    var serialNo    = ((currentPage - 1) * limit) + index + 1;
                    var addrAttr    = vend.address ? ' data-address=\'' + JSON.stringify(vend.address).replace(/'/g, '&#39;') + '\'' : '';
                    var stateAttr   = vend.address ? ' data-state="' + escapeHtml(vend.address.State || '') + '"' : '';

                    html += '<div class="vend-search-item" data-uid="' + vend.VendorUID + '" data-name="' + escapeHtml(vend.Name) + '"' + addrAttr + stateAttr + '>';
                    html += '<div class="d-flex align-items-start gap-2">';
                    html += '<div class="vend-serial">' + serialNo + '</div>';
                    html += '<div class="flex-grow-1">';
                    html += '<div class="vend-name">' + escapeHtml(vend.Name) + '</div>';
                    if (vend.Area) {
                        html += '<div class="vend-meta"><i class="bx bx-map me-1"></i>' + escapeHtml(vend.Area) + '</div>';
                    }
                    if (vend.MobileNumber) {
                        html += '<div class="vend-meta"><i class="bx bx-phone me-1"></i>' + escapeHtml(vend.MobileNumber) + '</div>';
                    }
                    if (vend.address && (vend.address.Line1 || vend.address.City)) {
                        var addrParts = [vend.address.Line1, vend.address.City, vend.address.State].filter(Boolean);
                        html += '<div class="vend-meta"><i class="bx bx-buildings me-1"></i>' + escapeHtml(addrParts.join(', ')) + '</div>';
                    }
                    html += '</div>';
                    html += '<div class="text-end flex-shrink-0">';
                    html += '<div class="vend-balance ' + balClass + '">' + currency + ' ' + formatNumber(balance, 2) + '</div>';
                    html += '<div class="vend-meta">' + balLabel + '</div>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                });

                $results.html(html);

                // Pagination
                var total      = parseInt(response.TotalCount || 0);
                var from       = ((currentPage - 1) * limit) + 1;
                var to         = Math.min(currentPage * limit, total);
                var totalPages = Math.ceil(total / limit);

                $('#vendSearchPageInfo').text(total > 0 ? 'Showing ' + from + ' – ' + to + ' of ' + total + ' Results' : '');

                var paginHtml = '';
                if (totalPages > 1) {
                    var start = Math.max(1, currentPage - 2);
                    var end   = Math.min(totalPages, start + 4);
                    start     = Math.max(1, end - 4);

                    paginHtml += '<li class="page-item' + (currentPage <= 1 ? ' disabled' : '') + '">' +
                                   '<a class="page-link" href="#" data-page="' + (currentPage - 1) + '">&lsaquo;</a>' +
                                 '</li>';
                    for (var p = start; p <= end; p++) {
                        paginHtml += '<li class="page-item' + (p === currentPage ? ' active' : '') + '">' +
                                       '<a class="page-link" href="#" data-page="' + p + '">' + p + '</a>' +
                                     '</li>';
                    }
                    paginHtml += '<li class="page-item' + (currentPage >= totalPages ? ' disabled' : '') + '">' +
                                   '<a class="page-link" href="#" data-page="' + (currentPage + 1) + '">&rsaquo;</a>' +
                                 '</li>';
                }
                $('#vendSearchPagination').html(paginHtml);
                $('#vendSearchPaginationWrap').removeClass('d-none');
                AjaxLoading = 1;
            },
            error: function () {
                AjaxLoading = 1;
                $results.html(
                    '<div class="text-center py-5 text-danger">' +
                        '<i class="bx bx-error-circle fs-3 d-block mb-2"></i>Failed to load vendors' +
                    '</div>'
                );
            }
        });
    }

    // ── Create Vendor — header button and no-results button ──────────────────
    function openCreateVendorModal() {
        $('#vendorSearchModal').modal('hide');
        setTimeout(function () {
            $('#addTransVendor').trigger('click');
        }, 300);
    }

    $(document).on('click', '#btnCreateVendorFromSearch', function () { openCreateVendorModal(); });
    $(document).on('click', '#vendSearchCreateBtn',       function () { openCreateVendorModal(); });

    // ── Helpers ───────────────────────────────────────────────────────────────
    function escapeHtml(text) {
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text || '').replace(/[&<>"']/g, function (m) { return map[m]; });
    }

    function formatNumber(num, decimals) {
        return parseFloat(num || 0).toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

}());
