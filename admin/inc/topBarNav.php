<?php
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
?>
<!-- NetSuite Layer 1: Branding & Profile -->
<div class="erp-header-top">
    <div class="ns-logo">
        <img src="<?php echo validate_image($_settings->info('logo')) ?>" alt="Logo">
        <span>MNS LIQUORS</span>
    </div>

    <div class="ns-search d-flex align-items-center" style="gap: 15px;">
        <div style="position:relative; flex-grow:1;">
            <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#999;"></i>
            <input type="text" id="ns-global-search" placeholder="Search orders, invoices, products, customers..." autocomplete="off" style="width:100%; padding:8px 12px 8px 35px; border-radius:4px; border:1px solid #d5dade;">
            <div id="ns-search-results" class="shadow-lg" style="display:none; position:absolute; top:110%; left:0; width:100%; background:#fff; z-index:1100; max-height:450px; overflow-y:auto; border:1px solid #d5dade; border-radius: 4px;"></div>
        </div>
        
        <div class="dropdown">
            <button class="btn btn-primary d-flex align-items-center justify-content-center shadow-sm" type="button" id="globalCreateNew" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="width:32px; height:32px; border-radius:50%; padding:0;">
                <i class="fas fa-plus"></i>
            </button>
            <div class="dropdown-menu shadow-lg border-0 mt-2" aria-labelledby="globalCreateNew" style="font-size:0.85rem; border-radius:4px; min-width: 200px;">
                <h6 class="dropdown-header text-uppercase font-weight-bold" style="font-size:0.7rem; letter-spacing:0.5px; color:#888;">Transactions</h6>
                <a class="dropdown-item py-2" href="<?php echo base_url ?>admin/?page=pos"><i class="fas fa-cash-register mr-2 text-success"></i> POS Entry Form</a>
                <a class="dropdown-item py-2" href="<?php echo base_url ?>admin/?page=transactions/sales/manage_sale"><i class="fas fa-file-invoice-dollar mr-2 text-primary"></i> Sale</a>
                <a class="dropdown-item py-2" href="<?php echo base_url ?>admin/?page=transactions/purchases/manage_purchase"><i class="fas fa-shopping-cart mr-2 text-info"></i> Purchase</a>
                <a class="dropdown-item py-2" href="<?php echo base_url ?>admin/?page=transactions/payments/manage_payment"><i class="fas fa-money-check-alt mr-2 text-success"></i> Payment</a>
                <a class="dropdown-item py-2" href="<?php echo base_url ?>admin/?page=transactions/expenses/manage_expense"><i class="fas fa-money-check-alt mr-2 text-success"></i> Expense</a>
                <div class="dropdown-divider"></div>
                <h6 class="dropdown-header text-uppercase font-weight-bold" style="font-size:0.7rem; letter-spacing:0.5px; color:#888;">Master records</h6>
                <a class="dropdown-item py-2" href="<?php echo base_url ?>admin/?page=master/items/manage_item"><i class="fas fa-box mr-2 text-secondary"></i> Item</a>
                <a class="dropdown-item py-2" href="<?php echo base_url ?>admin/?page=master/customers/manage_customer"><i class="fas fa-users mr-2 text-secondary"></i> Customer</a>
                <a class="dropdown-item py-2" href="<?php echo base_url ?>admin/?page=master/vendors/manage_vendor"><i class="fas fa-truck mr-2 text-secondary"></i> Vendor</a>
            </div>
        </div>
    </div>

    <div class="header-right d-flex align-items-center" style="gap: 1.5rem;">
        <div class="action-icons d-flex align-items-center" style="gap: 1rem; color: #666; font-size: 1rem;">
            <i class="far fa-bell cursor-pointer" title="Notifications"></i>
            <i class="far fa-question-circle cursor-pointer" title="Help"></i>
        </div>
        
        <div class="dropdown">
            <div class="user-profile dropdown-toggle d-flex align-items-center" data-toggle="dropdown" style="cursor:pointer; gap: 0.75rem;">
                <img src="<?php echo validate_image($_settings->userdata('avatar')) ?>" class="user-avatar" style="width: 32px; height: 32px; border-radius: 4px;" alt="Avatar">
                <div class="user-info d-none d-lg-block" style="line-height: 1;">
                    <span class="name" style="font-size: 0.85rem; font-weight: 600; color: #333;"><?php echo ucwords($_settings->userdata('firstname')) ?></span>
                    <small class="role" style="display:block; font-size: 0.7rem; color: #888; margin-top: 2px;"><?php echo $_settings->userdata('type') == 1 ? 'Administrator' : 'Staff' ?></small>
                </div>
            </div>
            <div class="dropdown-menu dropdown-menu-right shadow-lg border-0 rounded-0 mt-2 py-2" style="font-size: 0.85rem; min-width: 200px;">
                <a class="dropdown-item px-4 py-2" href="<?php echo base_url ?>admin/?page=system/users/manage_user&id=<?php echo $_settings->userdata('id') ?>">
                    <i class="far fa-user-circle mr-3"></i> My Profile
                </a>
                <a class="dropdown-item px-4 py-2" href="<?php echo base_url ?>admin/?page=system/settings">
                    <i class="fas fa-cog mr-3"></i> Preferences
                </a>
                <div class="dropdown-divider mx-3"></div>
                <a class="dropdown-item px-4 py-2 text-danger" href="<?php echo base_url.'/classes/Login.php?f=logout' ?>">
                    <i class="fas fa-power-off mr-3"></i> Log Out
                </a>
            </div>
        </div>
    </div>
</div>

<!-- NetSuite Layer 2: Main Navigation -->
<nav class="erp-nav-bar">
    <ul class="ns-menu">
        <li class="ns-menu-item <?php echo $page == 'home' ? 'active' : '' ?>">
            <a href="<?php echo base_url ?>admin" class="ns-menu-link">Dashboard</a>
        </li>

        <!-- Transactions -->
        <li class="ns-menu-item <?php echo strpos($page, 'transactions/') !== false ? 'active' : '' ?>">
            <a href="#" class="ns-menu-link">Transactions <i class="fas fa-chevron-down ml-2" style="font-size: 0.6rem;"></i></a>
            <div class="ns-dropdown">
                <a href="<?php echo base_url ?>admin/?page=pos" class="ns-dropdown-link font-weight-bold text-success"><i class="fas fa-cash-register mr-1"></i> POS Entry Form</a>
                <a href="<?php echo base_url ?>admin/?page=sales" class="ns-dropdown-link">Sales & Invoices</a>
                <a href="<?php echo base_url ?>admin/?page=purchases" class="ns-dropdown-link">Purchases</a>
                <a href="<?php echo base_url ?>admin/?page=payments" class="ns-dropdown-link">Payments</a>
                <a href="<?php echo base_url ?>admin/?page=expenses" class="ns-dropdown-link">Business Expenses</a>
                <a href="<?php echo base_url ?>admin/?page=master/accounts/transfers" class="ns-dropdown-link">Fund Transfers</a>
                <div class="dropdown-divider"></div>
                <a href="<?php echo base_url ?>admin/?page=returns" class="ns-dropdown-link">Returns List</a>
                <a href="<?php echo base_url ?>admin/?page=transactions/stock_adjustments/adjustments_index" class="ns-dropdown-link">Stock Adjustments</a>
            </div>
        </li>

        <!-- Lists -->
        <li class="ns-menu-item <?php echo strpos($page, 'master/') !== false ? 'active' : '' ?>">
            <a href="#" class="ns-menu-link">Lists <i class="fas fa-chevron-down ml-2" style="font-size: 0.6rem;"></i></a>
            <div class="ns-dropdown">
                <a href="<?php echo base_url ?>admin/?page=master/items" class="ns-dropdown-link">Items & Inventory</a>
                <a href="<?php echo base_url ?>admin/?page=master/categories" class="ns-dropdown-link">Categories</a>
                <a href="<?php echo base_url ?>admin/?page=master/stocks" class="ns-dropdown-link">Stock Registry</a>
                <div class="dropdown-divider"></div>
                <a href="<?php echo base_url ?>admin/?page=master/customers" class="ns-dropdown-link">Customers</a>
                <a href="<?php echo base_url ?>admin/?page=master/vendors" class="ns-dropdown-link">Vendors</a>
                <a href="<?php echo base_url ?>admin/?page=master/bank_accounts" class="ns-dropdown-link">Accounts</a>
                <a href="<?php echo base_url ?>admin/?page=master/accounts" class="ns-dropdown-link">Bank/Cash Accounts</a>
            </div>
        </li>

        <!-- Reports (2-layer flyout) -->
        <li class="ns-menu-item <?php echo strpos($page, 'reports/') !== false ? 'active' : '' ?>">
            <a href="#" class="ns-menu-link">Reports <i class="fas fa-chevron-down ml-2" style="font-size: 0.6rem;"></i></a>
            <div class="ns-dropdown ns-dropdown-2layer" style="min-width: 240px;">

                <!-- Layer 1 item → Layer 2 flyout -->
                <div class="ns-has-sub">
                    <a href="#" class="ns-dropdown-link d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-cash-register mr-2 text-success"></i>Daily Operations</span>
                        <i class="fas fa-chevron-right" style="font-size:0.6rem;"></i>
                    </a>
                    <div class="ns-sub-dropdown">
                        <a href="<?php echo base_url ?>admin/?page=reports/pos_sales" class="ns-dropdown-link font-weight-bold text-success"><i class="fas fa-store mr-1"></i> POS Sales Report</a>
                        <a href="<?php echo base_url ?>admin/?page=reports/sales" class="ns-dropdown-link">Sales Report</a>
                        <a href="<?php echo base_url ?>admin/?page=reports/purchase" class="ns-dropdown-link">Purchase Report</a>
                        <a href="<?php echo base_url ?>admin/?page=reports/daily_purchase" class="ns-dropdown-link">Daily Purchase Log</a>
                        <a href="<?php echo base_url ?>admin/?page=reports/daily_expense" class="ns-dropdown-link">Expense Summary</a>
                    </div>
                </div>

                <div class="ns-has-sub">
                    <a href="#" class="ns-dropdown-link d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-boxes mr-2 text-info"></i>Inventory & Stock</span>
                        <i class="fas fa-chevron-right" style="font-size:0.6rem;"></i>
                    </a>
                    <div class="ns-sub-dropdown">
                        <a href="<?php echo base_url ?>admin/?page=reports/stock" class="ns-dropdown-link">Detailed Stock Report</a>
                        <a href="<?php echo base_url ?>admin/?page=reports/stock_current" class="ns-dropdown-link">Current Stock Levels</a>
                        <a href="<?php echo base_url ?>admin/?page=reports/stock_low" class="ns-dropdown-link">Low Stock Alerts</a>
                        <a href="<?php echo base_url ?>admin/?page=reports/item_ledger" class="ns-dropdown-link">Item Ledger</a>
                        <a href="<?php echo base_url ?>admin/?page=reports/stock_valuation" class="ns-dropdown-link">Stock Valuation</a>
                    </div>
                </div>

                <div class="ns-has-sub">
                    <a href="#" class="ns-dropdown-link d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-calculator mr-2 text-primary"></i>Financial Accounting</span>
                        <i class="fas fa-chevron-right" style="font-size:0.6rem;"></i>
                    </a>
                    <div class="ns-sub-dropdown">
                        <a href="<?php echo base_url ?>admin/?page=reports/customer_statement" class="ns-dropdown-link"><i class="fas fa-file-invoice mr-1 text-primary"></i> Customer Statement</a>
                        <a href="<?php echo base_url ?>admin/?page=reports/customer_outstanding" class="ns-dropdown-link">Customer Outstanding</a>
                        <a href="<?php echo base_url ?>admin/?page=reports/vendor_outstanding" class="ns-dropdown-link">Vendor Outstanding</a>
                        <a href="<?php echo base_url ?>admin/?page=reports/account_ledger" class="ns-dropdown-link">Account Ledger</a>
                        <a href="<?php echo base_url ?>admin/?page=reports/profit" class="ns-dropdown-link">Profit &amp; Loss</a>
                        <a href="<?php echo base_url ?>admin/?page=reports/cash_book" class="ns-dropdown-link">Account Book</a>
                    </div>
                </div>

                <div class="ns-has-sub">
                    <a href="#" class="ns-dropdown-link d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-receipt mr-2 text-warning"></i>Tax &amp; Compliance</span>
                        <i class="fas fa-chevron-right" style="font-size:0.6rem;"></i>
                    </a>
                    <div class="ns-sub-dropdown">
                        <a href="<?php echo base_url ?>admin/?page=reports/vat_sales" class="ns-dropdown-link">VAT Sales Register</a>
                        <a href="<?php echo base_url ?>admin/?page=reports/vat_purchase" class="ns-dropdown-link">VAT Purchase Register</a>
                        <a href="<?php echo base_url ?>admin/?page=reports/vat_summary" class="ns-dropdown-link">VAT Summary Report</a>
                    </div>
                </div>

            </div>
        </li>

        <!-- Customizations & Setup -->
        <?php if($_settings->userdata('type') == 1): ?>
        <li class="ns-menu-item <?php echo strpos($page, 'system/') !== false ? 'active' : '' ?>">
            <a href="#" class="ns-menu-link">Setup <i class="fas fa-chevron-down ml-2" style="font-size: 0.6rem;"></i></a>
            <div class="ns-dropdown">
                <a href="<?php echo base_url ?>admin/?page=system/users" class="ns-dropdown-link">User Management</a>
                <a href="<?php echo base_url ?>admin/?page=system/settings" class="ns-dropdown-link">Company Information</a>
                <a href="<?php echo base_url ?>admin/?page=transactions/opening_balances" class="ns-dropdown-link">Opening Balances</a>
                <a href="<?php echo base_url ?>admin/?page=system/settings/ref_codes" class="ns-dropdown-link">Reference Codes</a>
            </div>
        </li>
        <?php endif; ?>
    </ul>
</nav>

<!-- Contextual Page Header (NetSuite Style Title Bar) -->
<div class="ns-page-header">
    <div class="left-ctx">
        <h1 class="ns-page-title"><?php echo ucwords(str_replace(['_', '/'], [' ', ' &bull; '], $page)) ?></h1>
        <div class="ns-breadcrumbs" style="font-size: 0.75rem; color: #888; margin-top: 4px;">
            Admin &raquo; <?php echo str_replace('/', ' &raquo; ', $page) ?>
        </div>
    </div>
    <div class="ns-actions">
        <button class="ns-btn" onclick="location.reload()" title="Refresh Page"><i class="fas fa-sync-alt"></i></button>
    </div>
</div>

<script>
$(document).ready(function(){
    var searchInput = $('#ns-global-search');
    var resultsBox = $('#ns-search-results');
    var timer = null;
    var activeIndex = -1;

    // Load Search History
    function getSearchHistory() {
        var history = localStorage.getItem('erp_search_history');
        return history ? JSON.parse(history) : [];
    }

    function saveToHistory(term) {
        var history = getSearchHistory();
        history = history.filter(h => h !== term);
        history.unshift(term);
        localStorage.setItem('erp_search_history', JSON.stringify(history.slice(0, 5)));
    }

    function showHistory() {
        var history = getSearchHistory();
        if (history.length === 0) return;
        
        var html = '<div class="ns-search-group-header ns-search-history-header">Recent Searches <span class="ns-search-clear-history">Clear</span></div>';
        history.forEach(function(term){
            html += '<div class="ns-search-item history-item" data-term="'+term+'"><i class="fas fa-history text-muted mr-3"></i><span>'+term+'</span></div>';
        });
        resultsBox.html(html).show();
    }

    searchInput.on('focus', function(){
        if ($(this).val().length < 2) showHistory();
    });

    $(document).on('click', '.ns-search-clear-history', function(e){
        e.stopPropagation();
        localStorage.removeItem('erp_search_history');
        resultsBox.hide();
    });

    $(document).on('click', '.history-item', function(){
        searchInput.val($(this).data('term')).trigger('keyup');
    });

    searchInput.on('keyup', function(e){
        var items = $('.ns-search-item');
        
        // Keyboard Navigation
        if (e.keyCode === 40) { // Down
            activeIndex = Math.min(activeIndex + 1, items.length - 1);
            updateActiveItem(items);
            return;
        }
        if (e.keyCode === 38) { // Up
            activeIndex = Math.max(activeIndex - 1, -1);
            updateActiveItem(items);
            return;
        }
        if (e.keyCode === 13) { // Enter
            if (activeIndex > -1) {
                items.eq(activeIndex).trigger('click');
            }
            return;
        }
        if (e.keyCode === 9) { // Tab
            if (items.length > 0 && activeIndex === -1) {
                activeIndex = 0;
                updateActiveItem(items);
                e.preventDefault();
            }
            return;
        }

        clearTimeout(timer);
        var q = $(this).val();
        if(q.length < 2) { 
            if(q.length === 0) showHistory();
            else resultsBox.hide(); 
            return; 
        }
        
        timer = setTimeout(function(){
            resultsBox.html('<div class="p-4 text-center"><i class="fas fa-spinner fa-spin text-primary mr-2"></i>Searching ERP Intelligence...</div>').show();
            $.ajax({
                url: '<?php echo base_url ?>admin/api/global_search.php',
                data: { q: q },
                dataType: 'json',
                success: function(groups){
                    if (Object.keys(groups).length === 0) {
                        resultsBox.html('<div class="p-4 text-center text-muted">No records found matching "<b>'+q+'</b>"</div>');
                        return;
                    }
                    
                    var html = '';
                    var iconMap = {
                        'customers': 'fa-user-tie text-navy',
                        'vendors': 'fa-truck text-orange',
                        'items': 'fa-box text-olive',
                        'transactions': 'fa-exchange-alt text-primary',
                        'users': 'fa-user-circle text-maroon',
                        'reports': 'fa-chart-line text-purple'
                    };

                    for (var group in groups) {
                        html += '<div class="ns-search-group-header">' + group.replace('_', ' ') + '</div>';
                        groups[group].forEach(function(item){
                            var icon = iconMap[group] || 'fa-file-alt text-secondary';
                            if (group === 'transactions') {
                                // Specific transaction icons
                                if(item.type == 'sale') icon = 'fa-file-invoice-dollar text-primary';
                                else if(item.type == 'purchase') icon = 'fa-shopping-cart text-info';
                                else if(item.type == 'expense') icon = 'fa-receipt text-danger';
                            }

                            var viewUrl = '<?php echo base_url ?>admin/?page=' + item.view_url;
                            
                            html += '<div class="ns-search-item" onclick="handleResultClick(\''+viewUrl+'\', \''+q+'\')">' +
                                        '<div class="mr-3 text-center"><i class="fas '+icon+'"></i></div>' +
                                        '<div class="flex-grow-1">' +
                                            '<div class="font-weight-bold mb-0" style="color: #333;">' + item.title_highlighted + '</div>' +
                                            '<div class="text-muted" style="font-size: 0.72rem;">' + item.subtitle_highlighted + '</div>' +
                                        '</div>' +
                                        (item.amount > 0 ? '<div class="font-weight-bold text-dark ml-2">Rs.' + parseFloat(item.amount).toLocaleString(undefined, {minimumFractionDigits: 2}) + '</div>' : '') +
                                    '</div>';
                        });
                    }
                    resultsBox.html(html);
                    activeIndex = -1;
                }
            });
        }, 300);
    });

    function updateActiveItem(items) {
        items.removeClass('active');
        if (activeIndex > -1) {
            var activeItem = items.eq(activeIndex);
            activeItem.addClass('active');
            activeItem[0].scrollIntoView({ block: 'nearest' });
        }
    }

    // ── 2-Layer flyout sub-menu (hover) ──────────────────────────
    var subTimer;
    $(document).on('mouseenter', '.ns-has-sub', function(){
        var $el = $(this);
        clearTimeout(subTimer);
        $('.ns-has-sub').not($el).removeClass('ns-sub-open');
        $el.addClass('ns-sub-open');
    }).on('mouseleave', '.ns-has-sub', function(){
        var $el = $(this);
        subTimer = setTimeout(function(){ $el.removeClass('ns-sub-open'); }, 120);
    });
    // Prevent clicking the category link from navigating
    $(document).on('click', '.ns-has-sub > a', function(e){ e.preventDefault(); });
});

function handleResultClick(url, q) {
    var history = localStorage.getItem('erp_search_history');
    var historyArr = history ? JSON.parse(history) : [];
    historyArr = historyArr.filter(h => h !== q);
    historyArr.unshift(q);
    localStorage.setItem('erp_search_history', JSON.stringify(historyArr.slice(0, 5)));
    location.href = url;
}
</script>

<style>
/* ── 2-Layer Reports Sub-Menu ────────────────────────────── */
.ns-has-sub {
    position: relative;
}
.ns-has-sub > .ns-sub-dropdown {
    display: none;
    position: absolute;
    left: 100%;
    top: 0;
    min-width: 230px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    box-shadow: 4px 4px 16px rgba(0,0,0,.12);
    z-index: 9999;
    padding: 4px 0;
}
.ns-has-sub.ns-sub-open > .ns-sub-dropdown {
    display: block;
}
/* Highlight the parent row when sub is open */
.ns-has-sub.ns-sub-open > a,
.ns-has-sub > a:hover {
    background: #f0f4ff;
    color: #1a73e8;
}
/* Make sure the 2-layer container doesn't cut off flyouts */
.ns-dropdown-2layer {
    overflow: visible !important;
}
/* Small arrow indicator */
.ns-has-sub > a .fa-chevron-right {
    opacity: 0.45;
    transition: opacity .15s;
}
.ns-has-sub.ns-sub-open > a .fa-chevron-right,
.ns-has-sub > a:hover .fa-chevron-right {
    opacity: 1;
}
</style>