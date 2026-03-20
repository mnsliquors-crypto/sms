<!-- Main Sidebar Container -->
      <aside class="main-sidebar sidebar-dark-primary elevation-4 sidebar-no-expand">
        <!-- Brand Logo -->
        <a href="<?php echo base_url ?>admin" class="brand-link bg-primary text-sm">
        <img src="<?php echo validate_image($_settings->info('logo'))?>" alt="Store Logo" class="brand-image img-circle elevation-3 bg-black" style="width: 1.8rem;height: 1.8rem;max-height: unset">
        <span class="brand-text font-weight-light"><?php echo $_settings->info('short_name') ?></span>
        </a>
        <!-- Sidebar -->
        <div class="sidebar os-host os-theme-light os-host-overflow os-host-overflow-y os-host-resize-disabled os-host-transition os-host-scrollbar-horizontal-hidden">
          <div class="os-resize-observer-host observed">
            <div class="os-resize-observer" style="left: 0px; right: auto;"></div>
          </div>
          <div class="os-size-auto-observer observed" style="height: calc(100% + 1px); float: left;">
            <div class="os-resize-observer"></div>
          </div>
          <div class="os-content-glue" style="margin: 0px -8px; width: 249px; height: 646px;"></div>
          <div class="os-padding">
            <div class="os-viewport os-viewport-native-scrollbars-invisible" style="overflow-y: scroll;">
              <div class="os-content" style="padding: 0px 8px; height: 100%; width: 100%;">
                <!-- Sidebar user panel (optional) -->
                <div class="clearfix"></div>
                <!-- Sidebar Menu -->
                <nav class="mt-4">
                   <ul class="nav nav-pills nav-sidebar flex-column text-sm nav-compact nav-flat nav-child-indent nav-collapse-hide-child" data-widget="treeview" role="menu" data-accordion="false">
                    
                    <!-- ── Dashboard ── -->
                    <li class="nav-item">
                      <a href="./" class="nav-link nav-home">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>
                          Dashboard
                        </p>
                      </a>
                    </li>

                    <!-- ── Transactions ── -->
                    <li class="nav-header">Transactions</li>
                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=pos" class="nav-link nav-pos">
                        <i class="nav-icon fas fa-cash-register text-success"></i>
                        <p class="font-weight-bold">POS Entry Form</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=purchases" class="nav-link nav-purchases">
                        <i class="nav-icon fas fa-shopping-cart"></i>
                        <p>Purchases</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=payments" class="nav-link nav-payments">
                        <i class="nav-icon fas fa-credit-card"></i>
                        <p>Payments</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=sales" class="nav-link nav-sales">
                        <i class="nav-icon fas fa-file-invoice-dollar"></i>
                        <p>Sale List</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=returns" class="nav-link nav-return">
                        <i class="nav-icon fas fa-undo"></i>
                        <p>Return List</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=transactions/stock_adjustments/adjustments_index" class="nav-link nav-transactions_stock_adjustments_adjustments_index">
                        <i class="nav-icon fas fa-adjust"></i>
                        <p>Stock Adjustments</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=denominations" class="nav-link nav-denominations">
                        <i class="nav-icon fas fa-cash-register"></i>
                        <p>Cash Counting</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=expenses" class="nav-link nav-expenses">
                        <i class="nav-icon fas fa-money-bill-alt"></i>
                        <p>Expenses</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=master/accounts/transfers" class="nav-link nav-master_accounts_transfers">
                        <i class="nav-icon fas fa-exchange-alt"></i>
                        <p>Fund Transfers</p>
                      </a>
                    </li>

                    <!-- ── Lists ── -->
                    <li class="nav-header">Lists</li>
                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=customers" class="nav-link nav-customers">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Customers</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=vendors" class="nav-link nav-vendors">
                        <i class="nav-icon fas fa-building"></i>
                        <p>Vendors</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=master/bank_accounts" class="nav-link nav-master_bank_accounts">
                        <i class="nav-icon fas fa-wallet"></i>
                        <p>Accounts</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=stocks" class="nav-link nav-stocks">
                        <i class="nav-icon fas fa-table"></i>
                        <p>Stocks</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=master/categories" class="nav-link nav-master_categories">
                        <i class="nav-icon fas fa-th-list"></i>
                        <p>Categories</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=items" class="nav-link nav-items">
                        <i class="nav-icon fas fa-boxes"></i>
                        <p>Items</p>
                      </a>
                    </li>

                    <!-- ── Reports (3-Tier) ── -->
                    <li class="nav-item">
                      <a href="#" class="nav-link nav-reports nav-is-tree">
                        <i class="nav-icon fas fa-chart-pie"></i>
                        <p>
                          Reports
                          <i class="right fas fa-angle-left"></i>
                        </p>
                      </a>
                      <ul class="nav nav-treeview">

                        <li class="nav-item">
                          <a href="<?php echo base_url ?>admin/?page=reports/pos_sales" class="nav-link nav-reports_pos_sales tree-item">
                            <i class="nav-icon fas fa-cash-register text-success"></i><p>POS Sales Report</p>
                          </a>
                        </li>
                        <li class="nav-item">
                          <a href="<?php echo base_url ?>admin/?page=reports/customer_statement" class="nav-link nav-reports_customer_statement tree-item">
                            <i class="nav-icon fas fa-file-invoice"></i><p>Customer Statement</p>
                          </a>
                        </li>

                        <!-- Tier 2: Daily Reports -->
                        <li class="nav-item">
                          <a href="#" class="nav-link nav-is-tree">
                            <i class="fas fa-calendar-day nav-icon"></i>
                            <p>
                              Daily Reports
                              <i class="right fas fa-angle-left"></i>
                            </p>
                          </a>
                          <ul class="nav nav-treeview">
                            <li class="nav-item">
                              <a href="<?php echo base_url ?>admin/?page=reports/sales" class="nav-link nav-reports_sales tree-item">
                                <i class="far fa-circle nav-icon"></i><p>Sales Report</p>
                              </a>
                            </li>

                            <li class="nav-item">
                              <a href="<?php echo base_url ?>admin/?page=reports/purchase" class="nav-link nav-reports_purchase tree-item">
                                <i class="far fa-circle nav-icon"></i><p>Purchase Report (Detailed)</p>
                              </a>
                            </li>
                            <li class="nav-item">
                              <a href="<?php echo base_url ?>admin/?page=reports/daily_purchase" class="nav-link nav-reports_daily_purchase tree-item">
                                <i class="far fa-circle nav-icon"></i><p>Daily Purchase</p>
                              </a>
                            </li>
                            <li class="nav-item">
                              <a href="<?php echo base_url ?>admin/?page=reports/daily_expense" class="nav-link nav-reports_daily_expense tree-item">
                                <i class="far fa-circle nav-icon"></i><p>Expense Report</p>
                              </a>
                            </li>
                            <li class="nav-item">
                              <a href="<?php echo base_url ?>admin/?page=reports/cash_book" class="nav-link nav-reports_cash_book tree-item">
                                <i class="far fa-circle nav-icon"></i><p>Account Book</p>
                              </a>
                            </li>
                          </ul>
                        </li>

                        <!-- Tier 2: Stock Reports -->
                        <li class="nav-item">
                          <a href="#" class="nav-link nav-is-tree">
                            <i class="fas fa-boxes nav-icon"></i>
                            <p>
                              Stock Reports
                              <i class="right fas fa-angle-left"></i>
                            </p>
                          </a>
                          <ul class="nav nav-treeview">
                            <li class="nav-item">
                              <a href="<?php echo base_url ?>admin/?page=reports/stock" class="nav-link nav-reports_stock tree-item">
                                <i class="far fa-circle nav-icon"></i><p>Stock Report (Detailed)</p>
                              </a>
                            </li>
                            <li class="nav-item">
                              <a href="<?php echo base_url ?>admin/?page=reports/stock_current" class="nav-link nav-reports_stock_current tree-item">
                                <i class="far fa-circle nav-icon"></i><p>Current Stock</p>
                              </a>
                            </li>
                            <li class="nav-item">
                              <a href="<?php echo base_url ?>admin/?page=reports/stock_low" class="nav-link nav-reports_stock_low tree-item">
                                <i class="far fa-circle nav-icon"></i><p>Low Stock Alert</p>
                              </a>
                            </li>
                            <li class="nav-item">
                              <a href="<?php echo base_url ?>admin/?page=reports/item_ledger" class="nav-link nav-reports_item_ledger tree-item">
                                <i class="far fa-circle nav-icon"></i><p>Item Ledger</p>
                              </a>
                            </li>
                            <li class="nav-item">
                              <a href="<?php echo base_url ?>admin/?page=reports/stock_valuation" class="nav-link nav-reports_stock_valuation tree-item">
                                <i class="far fa-circle nav-icon"></i><p>Stock Valuation</p>
                              </a>
                            </li>
                          </ul>
                        </li>

                        <!-- Tier 2: Accounting Reports -->
                        <li class="nav-item">
                          <a href="#" class="nav-link nav-is-tree">
                            <i class="fas fa-calculator nav-icon"></i>
                            <p>
                              Accounting
                              <i class="right fas fa-angle-left"></i>
                            </p>
                          </a>
                          <ul class="nav nav-treeview">

                            <li class="nav-item">
                              <a href="<?php echo base_url ?>admin/?page=reports/customer_outstanding" class="nav-link nav-reports_customer_outstanding tree-item">
                                <i class="far fa-circle nav-icon"></i><p>Customer Outstanding</p>
                              </a>
                            </li>
                            <li class="nav-item">
                              <a href="<?php echo base_url ?>admin/?page=reports/vendor_outstanding" class="nav-link nav-reports_vendor_outstanding tree-item">
                                <i class="far fa-circle nav-icon"></i><p>Vendor Outstanding</p>
                              </a>
                            </li>
                            <li class="nav-item">
                              <a href="<?php echo base_url ?>admin/?page=reports/account_ledger" class="nav-link nav-reports_account_ledger tree-item">
                                <i class="far fa-circle nav-icon"></i><p>Account Ledger</p>
                              </a>
                            </li>
                            <li class="nav-item">
                              <a href="<?php echo base_url ?>admin/?page=reports/profit" class="nav-link nav-reports_profit tree-item">
                                <i class="far fa-circle nav-icon"></i><p>Profit & Loss</p>
                              </a>
                            </li>
                          </ul>
                        </li>

                        <!-- Tier 2: VAT Reports -->
                        <li class="nav-item">
                          <a href="#" class="nav-link nav-is-tree">
                            <i class="fas fa-percentage nav-icon"></i>
                            <p>
                              VAT / Tax
                              <i class="right fas fa-angle-left"></i>
                            </p>
                          </a>
                          <ul class="nav nav-treeview">
                            <li class="nav-item">
                              <a href="<?php echo base_url ?>admin/?page=reports/vat_sales" class="nav-link nav-reports_vat_sales tree-item">
                                <i class="far fa-circle nav-icon"></i><p>VAT Sales</p>
                              </a>
                            </li>
                            <li class="nav-item">
                              <a href="<?php echo base_url ?>admin/?page=reports/vat_purchase" class="nav-link nav-reports_vat_purchase tree-item">
                                <i class="far fa-circle nav-icon"></i><p>VAT Purchase</p>
                              </a>
                            </li>
                            <li class="nav-item">
                              <a href="<?php echo base_url ?>admin/?page=reports/vat_summary" class="nav-link nav-reports_vat_summary tree-item">
                                <i class="far fa-circle nav-icon"></i><p>VAT Summary</p>
                              </a>
                            </li>
                          </ul>
                        </li>

                      </ul>
                    </li>

                    <!-- ── Setup ── -->
                    <li class="nav-header">Setup</li>
                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=transactions/opening_balances" class="nav-link nav-transactions_opening_balances">
                        <i class="nav-icon fas fa-balance-scale"></i>
                        <p>Opening Balances</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=system/settings/ref_codes" class="nav-link nav-system_settings_ref_codes">
                        <i class="nav-icon fas fa-barcode"></i>
                        <p>Reference Codes</p>
                      </a>
                    </li>

                    <?php if($_settings->userdata('type') == 1): ?>
                    <!-- ── Users ── -->
                    <li class="nav-header">Users</li>
                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=user/list" class="nav-link nav-user_list">
                        <i class="nav-icon fas fa-users"></i>
                        <p>User List</p>
                      </a>
                    </li>

                    <!-- ── Settings ── -->
                    <li class="nav-header">Settings</li>
                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=import" class="nav-link nav-import">
                        <i class="nav-icon fas fa-file-import"></i>
                        <p>File Import</p>
                      </a>
                    </li>

                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=system/git_sync" class="nav-link nav-system_git_sync">
                        <i class="nav-icon fab fa-github text-primary"></i>
                        <p>Git Sync (Backup)</p>
                      </a>
                    </li>

                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=system/settings" class="nav-link nav-system_settings">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>System Settings</p>
                      </a>
                    </li>
                    <?php endif; ?>

                  </ul>
                </nav>
                <!-- /.sidebar-menu -->
              </div>
            </div>
          </div>
          <div class="os-scrollbar os-scrollbar-horizontal os-scrollbar-unusable os-scrollbar-auto-hidden">
            <div class="os-scrollbar-track">
              <div class="os-scrollbar-handle" style="width: 100%; transform: translate(0px, 0px);"></div>
            </div>
          </div>
          <div class="os-scrollbar os-scrollbar-vertical os-scrollbar-auto-hidden">
            <div class="os-scrollbar-track">
              <div class="os-scrollbar-handle" style="height: 55.017%; transform: translate(0px, 0px);"></div>
            </div>
          </div>
          <div class="os-scrollbar-corner"></div>
        </div>
        <!-- /.sidebar -->
      </aside>
      <script>
        var page;
    $(document).ready(function(){
      page = '<?php echo isset($_GET['page']) ? $_GET['page'] : 'home' ?>';
      page = page.replace(/\//gi,'_');

      if($('.nav-link.nav-'+page).length > 0){
        var activeLink = $('.nav-link.nav-'+page);
        activeLink.addClass('active');

        // Recursively open parent menus and add active class to parent links
        activeLink.parents('.nav-treeview').each(function(){
            $(this).parent().addClass('menu-open');
            $(this).siblings('a').addClass('active');
        });

      }
      
		$('#receive-nav').click(function(){
      $('#uni_modal').on('shown.bs.modal',function(){
        $('#find-transaction [name="tracking_code"]').focus();
      })
			uni_modal("Enter Tracking Number","transaction/find_transaction.php");
		})
    })
  </script>