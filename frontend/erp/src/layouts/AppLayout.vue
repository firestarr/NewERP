<!-- src/layouts/AppLayout.vue - Template Section -->
<template>
    <div class="app-container" :class="{ 'dark': isDarkMode, 'sidebar-collapsed': sidebarCollapsed }">
        <!-- Animated Background -->
        <div class="background-animation">
            <div class="floating-orbs">
                <div class="orb orb-1"></div>
                <div class="orb orb-2"></div>
                <div class="orb orb-3"></div>
                <div class="orb orb-4"></div>
                <div class="orb orb-5"></div>
            </div>
        </div>

        <!-- Sidebar Navigation -->
        <aside class="sidebar" :class="{ 'collapsed': sidebarCollapsed }">
            <!-- Brand Section -->
            <div class="sidebar-header">
                <div class="brand-logo">
                    <div class="logo-icon">
                        <i class="fas fa-cube"></i>
                    </div>
                    <div class="brand-text" v-show="!sidebarCollapsed">
                        <h2>Inventory ERP</h2>
                        <p>Modern Business Suite</p>
                    </div>
                </div>
                <button class="sidebar-toggle" @click="toggleSidebar">
                    <i :class="sidebarCollapsed ? 'fas fa-chevron-right' : 'fas fa-chevron-left'"></i>
                </button>
            </div>

            <!-- Sidebar Menu -->
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <!-- Dashboard -->
                    <div class="nav-item">
                        <router-link to="/dashboard" class="nav-link" :class="{ active: $route.path === '/dashboard' }">
                            <i class="fas fa-tachometer-alt"></i>
                            <span v-show="!sidebarCollapsed">Dashboard</span>
                        </router-link>
                    </div>

                    <!-- Inventory -->
                    <div class="nav-item has-submenu" :class="{ active: activeSubmenu === 'inventory' }">
                        <div class="nav-link" @click="toggleSubmenu('inventory')">
                            <i class="fas fa-box"></i>
                            <span v-show="!sidebarCollapsed">Inventory</span>
                            <i v-show="!sidebarCollapsed" class="fas fa-chevron-down submenu-arrow" :class="{ rotated: activeSubmenu === 'inventory' }"></i>
                        </div>
                        <div class="submenu" v-show="activeSubmenu === 'inventory' && !sidebarCollapsed">
                            <div class="submenu-section">
                                <h4>Items & Categories</h4>
                                <router-link to="/items" class="submenu-link">
                                    <i class="fas fa-cubes"></i>
                                    <span>Items</span>
                                </router-link>
                                <router-link to="/item-categories" class="submenu-link">
                                    <i class="fas fa-tags"></i>
                                    <span>Item Group</span>
                                </router-link>
                                <router-link to="/unit-of-measures" class="submenu-link">
                                    <i class="fas fa-ruler"></i>
                                    <span>Units</span>
                                </router-link>
                            </div>
                            <div class="submenu-section">
                                <h4>Storage & Planning</h4>
                                <router-link to="/warehouses" class="submenu-link">
                                    <i class="fas fa-warehouse"></i>
                                    <span>Warehouses</span>
                                </router-link>
                                <router-link to="/materials/plans" class="submenu-link">
                                    <i class="fas fa-clipboard-list"></i>
                                    <span>Material Planning</span>
                                </router-link>
                                <router-link to="/materials/plans/generate" class="submenu-link">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Generate Material Plans</span>
                                </router-link>
                                <router-link to="/purchasing/requisitions/generate-from-material-plan" class="submenu-link">
                                    <i class="fas fa-file-plus"></i>
                                    <span>Generate PR from Plans</span>
                                </router-link>
                                <router-link to="/batches/expiry-dashboard" class="submenu-link">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Expiry Management</span>
                                </router-link>
                            </div>
                            <div class="submenu-section">
                                <h4>Pricing</h4>
                                <!-- <router-link to="/item-prices" class="submenu-link">
                                    <i class="fas fa-tags"></i>
                                    <span>Item Prices</span>
                                </router-link>
                                <router-link to="/price-comparison" class="submenu-link">
                                    <i class="fas fa-balance-scale"></i>
                                    <span>Price Comparison</span>
                                </router-link> -->
                                <router-link to="/item-prices-management" class="submenu-link">
                                    <i class="fas fa-tags"></i>
                                    <span>Item Prices Management</span>
                                </router-link>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Management -->
                    <div class="nav-item has-submenu" :class="{ active: activeSubmenu === 'stockManagement' }">
                        <div class="nav-link" @click="toggleSubmenu('stockManagement')">
                            <i class="fas fa-boxes"></i>
                            <span v-show="!sidebarCollapsed">Stock</span>
                            <i v-show="!sidebarCollapsed" class="fas fa-chevron-down submenu-arrow" :class="{ rotated: activeSubmenu === 'stockManagement' }"></i>
                        </div>
                        <div class="submenu" v-show="activeSubmenu === 'stockManagement' && !sidebarCollapsed">
                            <div class="submenu-section">
                                <h4>Stock Overview</h4>
                                <router-link to="/item-stocks" class="submenu-link">
                                    <i class="fas fa-box"></i>
                                    <span>Inventory Stock</span>
                                </router-link>
                                <router-link to="/item-stocks/warehouse" class="submenu-link">
                                    <i class="fas fa-warehouse"></i>
                                    <span>Warehouse Stock</span>
                                </router-link>
                                <router-link to="/item-stocks/negative" class="submenu-link">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span>Negative Stocks</span>
                                </router-link>
                            </div>
                            <div class="submenu-section">
                                <h4>Stock Operations</h4>
                                <!-- <router-link to="/item-stocks/transfer" class="submenu-link">
                                    <i class="fas fa-exchange-alt"></i>
                                    <span>Stock Transfer</span>
                                </router-link>
                                <router-link to="/item-stocks/adjust" class="submenu-link">
                                    <i class="fas fa-sliders-h"></i>
                                    <span>Stock Adjustment</span>
                                </router-link> -->
                                <router-link to="/item-stocks/reserve" class="submenu-link">
                                    <i class="fas fa-lock"></i>
                                    <span>Stock Reservation</span>
                                </router-link>
                            </div>
                            <div class="submenu-section">
                                <h4>Transactions & Counting</h4>
                                <router-link to="/stock-transactions" class="submenu-link">
                                    <i class="fas fa-random"></i>
                                    <span>Transactions</span>
                                </router-link>
                                <router-link to="/stock-adjustments" class="submenu-link">
                                    <i class="fas fa-sliders-h"></i>
                                    <span>Adjustments</span>
                                </router-link>
                                <router-link to="/cycle-counts" class="submenu-link">
                                    <i class="fas fa-clipboard-check"></i>
                                    <span>Cycle Counting</span>
                                </router-link>
                                <router-link to="/cycle-counts/generate" class="submenu-link">
                                    <i class="fas fa-tasks"></i>
                                    <span>Generate Count Tasks</span>
                                </router-link>
                            </div>
                        </div>
                    </div>

                    <!-- Purchasing -->
                    <div class="nav-item has-submenu" :class="{ active: activeSubmenu === 'purchasing' }">
                        <div class="nav-link" @click="toggleSubmenu('purchasing')">
                            <i class="fas fa-shopping-bag"></i>
                            <span v-show="!sidebarCollapsed">Purchasing</span>
                            <i v-show="!sidebarCollapsed" class="fas fa-chevron-down submenu-arrow" :class="{ rotated: activeSubmenu === 'purchasing' }"></i>
                        </div>
                        <div class="submenu" v-show="activeSubmenu === 'purchasing' && !sidebarCollapsed">
                            <div class="submenu-section">
                                <h4>Vendors</h4>
                                <router-link to="/purchasing/vendors" class="submenu-link">
                                    <i class="fas fa-users"></i>
                                    <span>Vendor Management</span>
                                </router-link>
                                <!-- <router-link to="/purchasing/vendor-performance" class="submenu-link">
                                    <i class="fas fa-star"></i>
                                    <span>Performance</span>
                                </router-link>
                                <router-link to="/purchasing/evaluations" class="submenu-link">
                                    <i class="fas fa-star"></i>
                                    <span>Vendor Evaluations</span>
                                </router-link>
                                <router-link to="/purchasing/evaluation-dashboard" class="submenu-link">
                                    <i class="fa-solid fa-gauge-high"></i>
                                    <span>Evaluation Dashboard</span>
                                </router-link> -->
                            </div>
                            <div class="submenu-section">
                                <h4>Requisitions & RFQ</h4>
                                <router-link to="/purchasing/requisitions" class="submenu-link">
                                    <i class="fas fa-file-alt"></i>
                                    <span>Requisitions</span>
                                </router-link>
                                <router-link to="/purchasing/requisitions/approvals" class="submenu-link">
                                    <i class="fas fa-check-circle"></i>
                                    <span>PR Approvals</span>
                                </router-link>
                                <router-link to="/purchasing/requisitions/to-rfq" class="submenu-link">
                                    <i class="fas fa-exchange-alt"></i>
                                    <span>PR to RFQ</span>
                                </router-link>
                                <router-link to="/purchasing/rfqs" class="submenu-link">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                    <span>RFQs</span>
                                </router-link>
                            </div>
                            <div class="submenu-section">
                                <h4>Quotations & Orders</h4>
                                <router-link to="/purchasing/quotations" class="submenu-link">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                    <span>Vendor Quotations</span>
                                </router-link>
                                <router-link to="/purchasing/quotations/compare" class="submenu-link">
                                    <i class="fas fa-balance-scale"></i>
                                    <span>Compare Quotations</span>
                                </router-link>
                                <router-link to="/purchasing/orders" class="submenu-link">
                                    <i class="fas fa-clipboard-list"></i>
                                    <span>Purchase Orders</span>
                                </router-link>
                                <router-link to="/purchasing/po-status" class="submenu-link">
                                    <i class="fas fa-clipboard-check"></i>
                                    <span>PO Status</span>
                                </router-link>
                            </div>
                            <div class="submenu-section">
                                <h4>Receipts & Analytics</h4>
                                <router-link to="/purchasing/goods-receipts" class="submenu-link">
                                    <i class="fas fa-truck-loading"></i>
                                    <span>Goods Receipts</span>
                                </router-link>
                                <!-- <router-link to="/purchasing/goods-receipts/dashboard" class="submenu-link">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Receipts Dashboard</span>
                                </router-link> -->
                                <router-link to="/purchasing/vendor-invoices" class="submenu-link">
                                    <i class="fas fa-file-invoice"></i>
                                    <span>Vendor Invoices</span>
                                </router-link>
                                <!-- <router-link to="/purchasing/contracts" class="submenu-link">
                                    <i class="fas fa-file-contract"></i>
                                    <span>Vendor Contracts</span>
                                </router-link>
                                <router-link to="/purchasing/contracts/expiry-dashboard" class="submenu-link">
                                    <i class="fas fa-chart-line"></i>
                                    <span>Contract Expiry Dashboard</span>
                                </router-link> -->
                            </div>
                            <div class="submenu-section">
                                <h4>Analytics</h4>
                                <!-- <router-link to="/purchasing/dashboard" class="submenu-link">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Dashboard</span>
                                </router-link>
                                <router-link to="/purchasing/spend-analysis" class="submenu-link">
                                    <i class="fas fa-chart-pie"></i>
                                    <span>Spend Analysis</span>
                                </router-link>
                                <router-link to="/purchasing/price-trend" class="submenu-link">
                                    <i class="fas fa-chart-line"></i>
                                    <span>Price Trends</span>
                                </router-link> -->
                            </div>
                        </div>
                    </div>

                    <!-- Sales -->
                    <div class="nav-item has-submenu" :class="{ active: activeSubmenu === 'sales' }">
                        <div class="nav-link" @click="toggleSubmenu('sales')">
                            <i class="fas fa-shopping-cart"></i>
                            <span v-show="!sidebarCollapsed">Sales</span>
                            <i v-show="!sidebarCollapsed" class="fas fa-chevron-down submenu-arrow" :class="{ rotated: activeSubmenu === 'sales' }"></i>
                        </div>
                        <div class="submenu" v-show="activeSubmenu === 'sales' && !sidebarCollapsed">
                            <div class="submenu-section">
                                <h4>Customers</h4>
                                <router-link to="/sales/customers" class="submenu-link">
                                    <i class="fas fa-users"></i>
                                    <span>Customer Management</span>
                                </router-link>
                                <router-link to="/sales/quotations" class="submenu-link">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                    <span>Quotations</span>
                                </router-link>
                            </div>
                            <div class="submenu-section">
                                <h4>Orders & Delivery</h4>
                                <router-link to="/sales/orders" class="submenu-link">
                                    <i class="fas fa-file-signature"></i>
                                    <span>Sales Orders</span>
                                </router-link>
                                <router-link to="/sales/deliveries" class="submenu-link">
                                    <i class="fas fa-truck"></i>
                                    <span>Deliveries</span>
                                </router-link>
                                <router-link to="/sales/packinglist" class="submenu-link">
                                    <i class="fas fa-box"></i>
                                    <span>Packing List</span>
                                </router-link>
                                <router-link to="/sales/invoices" class="submenu-link">
                                    <i class="fas fa-file-invoice"></i>
                                    <span>Invoices</span>
                                </router-link>
                                <router-link to="/sales/returns" class="submenu-link">
                                    <i class="fas fa-undo"></i>
                                    <span>Returns</span>
                                </router-link>
                            </div>
                            <div class="submenu-section">
                                <h4>Forecasting</h4>
                                <router-link to="/sales/forecasts" class="submenu-link">
                                    <i class="fas fa-chart-line"></i>
                                    <span>Sales Forecasts</span>
                                </router-link>
                                <router-link to="/sales/forecasts/dashboard" class="submenu-link">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Forecast Dashboard</span>
                                </router-link>
                                <router-link to="/sales/forecasts/volatility-dashboard" class="submenu-link">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span>Volatility Monitor</span>
                                </router-link>
                                <router-link to="/sales/forecasts/trend-analysis" class="submenu-link">
                                    <i class="fas fa-chart-area"></i>
                                    <span>Trend Analysis</span>
                                </router-link>
                                <router-link to="/sales/forecasts/consolidated" class="submenu-link">
                                    <i class="fas fa-table"></i>
                                    <span>Consolidated View</span>
                                </router-link>
                                <router-link to="/sales/forecasts/import" class="submenu-link">
                                    <i class="fas fa-file-import"></i>
                                    <span>Import Forecast</span>
                                </router-link>
                            </div>
                            <div class="submenu-section">
                                <h4>Analysis</h4>
                                <router-link to="/sales/forecasts/accuracy" class="submenu-link">
                                    <i class="fas fa-bullseye"></i>
                                    <span>Accuracy Analysis</span>
                                </router-link>
                                <router-link to="/sales/forecasts/update-actuals" class="submenu-link">
                                    <i class="fas fa-sync-alt"></i>
                                    <span>Update Actuals</span>
                                </router-link>
                                <router-link to="/sales/forecasts/history" class="submenu-link">
                                    <i class="fas fa-history"></i>
                                    <span>Version History</span>
                                </router-link>
                            </div>
                        </div>
                    </div>

                    <!-- Manufacturing -->
                    <div class="nav-item has-submenu" :class="{ active: activeSubmenu === 'manufacturing' }">
                        <div class="nav-link" @click="toggleSubmenu('manufacturing')">
                            <i class="fas fa-industry"></i>
                            <span v-show="!sidebarCollapsed">Manufacturing</span>
                            <i v-show="!sidebarCollapsed" class="fas fa-chevron-down submenu-arrow" :class="{ rotated: activeSubmenu === 'manufacturing' }"></i>
                        </div>
                        <div class="submenu" v-show="activeSubmenu === 'manufacturing' && !sidebarCollapsed">
                            <div class="submenu-section">
                                <h4>Planning</h4>
                                <router-link to="/manufacturing/boms" class="submenu-link">
                                    <i class="fas fa-clipboard-list"></i>
                                    <span>Bill of Materials</span>
                                </router-link>
                                <router-link to="/manufacturing/routings" class="submenu-link">
                                    <i class="fas fa-project-diagram"></i>
                                    <span>Routing</span>
                                </router-link>
                            </div>
                            <div class="submenu-section">
                                <h4>Production</h4>
                                <router-link to="/manufacturing/work-centers" class="submenu-link">
                                    <i class="fas fa-industry"></i>
                                    <span>Work Centers</span>
                                </router-link>
                                <router-link to="/manufacturing/work-orders" class="submenu-link">
                                    <i class="fas fa-clipboard-list"></i>
                                    <span>Job Orders</span>
                                </router-link>
                                <router-link to="/manufacturing/production-orders" class="submenu-link">
                                    <i class="fas fa-cogs"></i>
                                    <span>Job Order Process</span>
                                </router-link>
                                <router-link to="/manufacturing/job-tickets" class="submenu-link">
                                    <i class="fas fa-ticket-alt"></i>
                                    <span>Job Tickets</span>
                                </router-link>
                            </div>
                        </div>
                    </div>

                    <!-- Quality -->
                    <div class="nav-item has-submenu" :class="{ active: activeSubmenu === 'quality' }">
                        <div class="nav-link" @click="toggleSubmenu('quality')">
                            <i class="fas fa-check-circle"></i>
                            <span v-show="!sidebarCollapsed">Quality</span>
                            <i v-show="!sidebarCollapsed" class="fas fa-chevron-down submenu-arrow" :class="{ rotated: activeSubmenu === 'quality' }"></i>
                        </div>
                        <div class="submenu" v-show="activeSubmenu === 'quality' && !sidebarCollapsed">
                            <div class="submenu-section">
                                <h4>Quality Control</h4>
                                <router-link to="/quality-inspections" class="submenu-link">
                                    <i class="fas fa-clipboard-check"></i>
                                    <span>Inspections</span>
                                </router-link>
                                <router-link to="/quality-parameters/create" class="submenu-link">
                                    <i class="fas fa-sliders-h"></i>
                                    <span>Parameters</span>
                                </router-link>
                                <router-link to="/dashboard" class="submenu-link">
                                    <i class="fas fa-chart-line"></i>
                                    <span>Quality Dashboard</span>
                                </router-link>
                            </div>
                        </div>
                    </div>

                    <!-- Accounting -->
                    <div class="nav-item has-submenu" :class="{ active: activeSubmenu === 'accounting' }">
                        <div class="nav-link" @click="toggleSubmenu('accounting')">
                            <i class="fas fa-calculator"></i>
                            <span v-show="!sidebarCollapsed">Accounting</span>
                            <i v-show="!sidebarCollapsed" class="fas fa-chevron-down submenu-arrow" :class="{ rotated: activeSubmenu === 'accounting' }"></i>
                        </div>
                        <div class="submenu" v-show="activeSubmenu === 'accounting' && !sidebarCollapsed">
                            <div class="submenu-section">
                                <h4>Financial Management</h4>
                                <router-link to="/currency-rates" class="submenu-link">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span>Exchange Rates</span>
                                </router-link>
                                <router-link to="/currency-converter" class="submenu-link">
                                    <i class="fas fa-exchange-alt"></i>
                                    <span>Currency Converter</span>
                                </router-link>
                            </div>
                        </div>
                    </div>

                    <!-- Reports -->
                    <div class="nav-item has-submenu" :class="{ active: activeSubmenu === 'reports' }">
                        <div class="nav-link" @click="toggleSubmenu('reports')">
                            <i class="fas fa-chart-bar"></i>
                            <span v-show="!sidebarCollapsed">Reports</span>
                            <i v-show="!sidebarCollapsed" class="fas fa-chevron-down submenu-arrow" :class="{ rotated: activeSubmenu === 'reports' }"></i>
                        </div>
                        <div class="submenu" v-show="activeSubmenu === 'reports' && !sidebarCollapsed">
                            <div class="submenu-section">
                                <h4>Inventory Reports</h4>
                                <router-link to="/reports/stock" class="submenu-link">
                                    <i class="fas fa-boxes"></i>
                                    <span>Stock Report</span>
                                </router-link>
                                <router-link to="/reports/movement" class="submenu-link">
                                    <i class="fas fa-chart-line"></i>
                                    <span>Movement Report</span>
                                </router-link>
                            </div>
                            <div class="submenu-section">
                                <h4>Business Reports</h4>
                                <router-link to="/reports/sales" class="submenu-link">
                                    <i class="fas fa-chart-pie"></i>
                                    <span>Sales Report</span>
                                </router-link>
                                <router-link to="/purchasing/spend-analysis" class="submenu-link">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span>Purchase Analysis</span>
                                </router-link>
                            </div>
                        </div>
                    </div>

                    <!-- Admin -->
                    <div class="nav-item has-submenu" :class="{ active: activeSubmenu === 'admin' }">
                        <div class="nav-link" @click="toggleSubmenu('admin')">
                            <i class="fas fa-user-shield"></i>
                            <span v-show="!sidebarCollapsed">Admin</span>
                            <i v-show="!sidebarCollapsed" class="fas fa-chevron-down submenu-arrow" :class="{ rotated: activeSubmenu === 'admin' }"></i>
                        </div>
                        <div class="submenu" v-show="activeSubmenu === 'admin' && !sidebarCollapsed">
                            <div class="submenu-section">
                                <h4>System Administration</h4>
                                <router-link to="/admin/users" class="submenu-link">
                                    <i class="fas fa-users-cog"></i>
                                    <span>User Management</span>
                                </router-link>
                                <router-link to="/admin/settings" class="submenu-link">
                                    <i class="fas fa-cogs"></i>
                                    <span>System Settings</span>
                                </router-link>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Sidebar Footer -->
            <div class="sidebar-footer" v-show="!sidebarCollapsed">
                <div class="footer-stats">
                    <div class="stat-item">
                        <i class="fas fa-server"></i>
                        <span>System Uptime: 99.9%</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="main-wrapper">
            <!-- Top Header -->
            <header class="top-header">
                <div class="header-left">
                    <button class="mobile-menu-toggle" @click="toggleSidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>

                <div class="header-center">
                    <!-- Search -->
                    <div class="search-container">
                        <div class="search-box" :class="{ active: searchFocused }">
                            <i class="fas fa-search search-icon"></i>
                            <input
                                type="text"
                                class="search-input"
                                placeholder="Search anything..."
                                v-model="searchQuery"
                                @focus="searchFocused = true"
                                @blur="handleSearchBlur"
                            />
                            <Transition name="fade">
                                <div v-if="searchFocused && searchQuery" class="search-results">
                                    <div class="search-category">
                                        <h4>Quick Actions</h4>
                                        <div class="search-item">
                                            <i class="fas fa-plus"></i>
                                            <span>Add New Item</span>
                                        </div>
                                        <div class="search-item">
                                            <i class="fas fa-shopping-cart"></i>
                                            <span>Create Order</span>
                                        </div>
                                    </div>
                                    <div class="search-category">
                                        <h4>Navigation</h4>
                                        <div class="search-item">
                                            <i class="fas fa-box"></i>
                                            <span>Items Management</span>
                                        </div>
                                        <div class="search-item">
                                            <i class="fas fa-users"></i>
                                            <span>Customer List</span>
                                        </div>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>

                <div class="header-right">
                    <!-- Quick Actions -->
                    <div class="quick-actions">
                        <button class="action-btn" @click="quickCreateItem" title="Add Item">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button class="action-btn" @click="showNotifications = !showNotifications" title="Notifications">
                            <i class="fas fa-bell"></i>
                            <span v-if="notificationCount > 0" class="notification-badge">{{ notificationCount }}</span>
                        </button>
                        <button class="action-btn" @click="toggleTheme" title="Toggle Theme">
                            <i :class="isDarkMode ? 'fas fa-sun' : 'fas fa-moon'"></i>
                        </button>
                    </div>

                    <!-- User Menu -->
                    <div class="user-section" @click="toggleUserMenu">
                        <div class="user-avatar">
                            <span>{{ user.name ? user.name.charAt(0).toUpperCase() : 'U' }}</span>
                        </div>
                        <div class="user-info">
                            <span class="username">{{ user.name || 'John Doe' }}</span>
                            <span class="user-role">{{ user.role || 'Administrator' }}</span>
                        </div>
                        <i class="fas fa-chevron-down user-arrow" :class="{ rotated: userMenuOpen }"></i>
                    </div>

                    <!-- Notifications Dropdown -->
                    <Transition name="slide-down">
                        <div v-if="showNotifications" class="notifications-dropdown">
                            <div class="notifications-header">
                                <h3>Notifications</h3>
                                <button @click="markAllRead" class="mark-all-btn">Mark all read</button>
                            </div>
                            <div class="notifications-list">
                                <div v-for="notification in notifications" :key="notification.id" class="notification-item">
                                    <div class="notification-icon" :class="notification.type">
                                        <i :class="notification.icon"></i>
                                    </div>
                                    <div class="notification-content">
                                        <p class="notification-title">{{ notification.title }}</p>
                                        <p class="notification-time">{{ notification.time }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>

                    <!-- User Dropdown -->
                    <Transition name="slide-down">
                        <div v-if="userMenuOpen" class="user-dropdown">
                            <div class="user-profile">
                                <div class="user-avatar-large">
                                    <span>{{ user.name ? user.name.charAt(0).toUpperCase() : 'U' }}</span>
                                </div>
                                <div class="user-details">
                                    <h4>{{ user.name || 'John Doe' }}</h4>
                                    <p>{{ user.email || 'john@company.com' }}</p>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <div class="user-menu-items">
                                <div class="dropdown-item" @click="navigateToProfile">
                                    <i class="fas fa-user"></i>
                                    <span>Profile Settings</span>
                                </div>
                                <div class="dropdown-item" @click="navigateToSettings">
                                    <i class="fas fa-cog"></i>
                                    <span>System Settings</span>
                                </div>
                                <div class="dropdown-item">
                                    <i class="fas fa-bell"></i>
                                    <span>Notification Settings</span>
                                </div>
                                <div class="dropdown-divider"></div>
                                <div class="dropdown-item logout-item" @click="logout">
                                    <div class="logout-icon">
                                        <i class="fas fa-sign-out-alt"></i>
                                    </div>
                                    <span>Logout</span>
                                    <i class="fas fa-arrow-right logout-arrow"></i>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </header>

            <!-- Main Content -->
            <main class="main-content">
                <!-- Breadcrumb -->
                <div class="breadcrumb-section">
                    <div class="breadcrumb">
                        <!-- Always start with Dashboard -->
                        <router-link to="/dashboard" class="breadcrumb-item">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </router-link>

                        <!-- Dynamic breadcrumb items -->
                        <template v-for="(item, index) in breadcrumbItems" :key="index">
                            <i class="fas fa-chevron-right breadcrumb-separator"></i>
                            <router-link
                                v-if="item.route && index < breadcrumbItems.length - 1"
                                :to="item.route"
                                class="breadcrumb-item"
                            >
                                <i v-if="item.icon" :class="item.icon"></i>
                                <span>{{ item.label }}</span>
                            </router-link>
                            <span
                                v-else
                                class="breadcrumb-current"
                                :class="{ 'with-icon': item.icon }"
                            >
                                <i v-if="item.icon" :class="item.icon"></i>
                                <span>{{ item.label }}</span>
                            </span>
                        </template>
                    </div>
                    <div class="page-actions">
                        <button class="action-button primary" @click="quickCreateItem">
                            <i class="fas fa-plus"></i>
                            <span>Quick Add</span>
                        </button>
                        <button class="action-button secondary" @click="showQuickActions = !showQuickActions">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                    </div>
                </div>

                <!-- Page Header -->
                <div class="page-header">
                    <div class="page-title-section">
                        <h1 class="page-title">{{ pageTitle }}</h1>
                        <p class="page-subtitle">Manage your {{ pageTitle.toLowerCase() }} efficiently</p>
                    </div>

                    <!-- Quick Stats Cards -->
                    <div class="quick-stats" v-if="$route.name === 'Dashboard'">
                        <div class="stat-card" v-for="(stat, index) in quickStats" :key="index">
                            <div class="stat-icon" :style="{ background: stat.gradient }">
                                <i :class="stat.icon"></i>
                            </div>
                            <div class="stat-content">
                                <h3>{{ stat.value }}</h3>
                                <p>{{ stat.label }}</p>
                                <div class="stat-trend" :class="stat.trend">
                                    <i :class="stat.trend === 'up' ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
                                    <span>{{ stat.change }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="content-area">
                    <router-view />
                </div>
            </main>
        </div>

        <!-- Floating Action Menu -->
        <div class="floating-actions" :class="{ active: showQuickActions }">
            <button class="fab-main" @click="showQuickActions = !showQuickActions">
                <i class="fas fa-plus" :class="{ rotated: showQuickActions }"></i>
            </button>

            <Transition name="scale-stagger">
                <div v-if="showQuickActions" class="fab-menu">
                    <button class="fab-item" @click="quickCreateItem" title="Add Item">
                        <i class="fas fa-box"></i>
                        <span>Add Item</span>
                    </button>
                    <button class="fab-item" @click="quickCreateOrder" title="New Order">
                        <i class="fas fa-shopping-cart"></i>
                        <span>New Order</span>
                    </button>
                    <button class="fab-item" @click="quickCreateCustomer" title="Add Customer">
                        <i class="fas fa-user-plus"></i>
                        <span>Add Customer</span>
                    </button>
                    <button class="fab-item" @click="viewAnalytics" title="Analytics">
                        <i class="fas fa-chart-line"></i>
                        <span>Analytics</span>
                    </button>
                </div>
            </Transition>
        </div>

        <!-- Loading Overlay -->
        <Transition name="fade">
            <div v-if="isLoading" class="loading-overlay">
                <div class="loading-content">
                    <div class="loading-spinner"></div>
                    <p>Processing your request...</p>
                </div>
            </div>
        </Transition>

        <!-- Sidebar Overlay (Mobile) -->
        <div
            v-if="!sidebarCollapsed && isMobile"
            class="sidebar-overlay"
            @click="closeSidebar"
        ></div>
    </div>
</template>
// src/layouts/AppLayout.vue - Script Section
<script>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import axios from "axios";

export default {
    name: "AppLayout",
    setup() {
        const router = useRouter();
        const route = useRoute();

        // Reactive states
        const user = ref(JSON.parse(localStorage.getItem("user") || "{}"));
        const searchQuery = ref("");
        const searchFocused = ref(false);
        const showNotifications = ref(false);
        const showQuickActions = ref(false);
        const isDarkMode = ref(localStorage.getItem("darkMode") === "true");
        const isLoading = ref(false);
        const userMenuOpen = ref(false);
        const sidebarCollapsed = ref(localStorage.getItem("sidebarCollapsed") === "true");
        const activeSubmenu = ref(null);
        const isMobile = ref(window.innerWidth < 768);

        // Breadcrumb route hierarchy mapping
        const routeHierarchy = {
            // Sales Orders
            'SalesOrders': [
                { label: 'Sales Orders', route: '/sales/orders', icon: 'fas fa-shopping-cart' }
            ],
            'CreateSalesOrder': [
                { label: 'Sales Orders', route: '/sales/orders', icon: 'fas fa-shopping-cart' },
                { label: 'Create Sales Order', icon: 'fas fa-plus' }
            ],
            'SalesOrderDetail': [
                { label: 'Sales Orders', route: '/sales/orders', icon: 'fas fa-shopping-cart' },
                { label: 'Sales Order Detail', icon: 'fas fa-eye' }
            ],
            'EditSalesOrder': [
                { label: 'Sales Orders', route: '/sales/orders', icon: 'fas fa-shopping-cart' },
                { label: 'Edit Sales Order', icon: 'fas fa-edit' }
            ],

            // Sales Quotations
            'SalesQuotations': [
                { label: 'Sales Quotations', route: '/sales/quotations', icon: 'fas fa-file-invoice-dollar' }
            ],
            'CreateSalesQuotation': [
                { label: 'Sales Quotations', route: '/sales/quotations', icon: 'fas fa-file-invoice-dollar' },
                { label: 'Create Quotation', icon: 'fas fa-plus' }
            ],
            'SalesQuotationDetail': [
                { label: 'Sales Quotations', route: '/sales/quotations', icon: 'fas fa-file-invoice-dollar' },
                { label: 'Quotation Detail', icon: 'fas fa-eye' }
            ],

            // Customers
            'customers.index': [
                { label: 'Customers', route: '/sales/customers', icon: 'fas fa-users' }
            ],
            'customers.create': [
                { label: 'Customers', route: '/sales/customers', icon: 'fas fa-users' },
                { label: 'Add New Customer', icon: 'fas fa-user-plus' }
            ],
            'customers.show': [
                { label: 'Customers', route: '/sales/customers', icon: 'fas fa-users' },
                { label: 'Customer Details', icon: 'fas fa-eye' }
            ],
            'customers.edit': [
                { label: 'Customers', route: '/sales/customers', icon: 'fas fa-users' },
                { label: 'Edit Customer', icon: 'fas fa-edit' }
            ],

            // Sales Invoices
            'SalesInvoices': [
                { label: 'Sales Invoices', route: '/sales/invoices', icon: 'fas fa-file-invoice' }
            ],
            'CreateSalesInvoice': [
                { label: 'Sales Invoices', route: '/sales/invoices', icon: 'fas fa-file-invoice' },
                { label: 'Create Invoice', icon: 'fas fa-plus' }
            ],
            'SalesInvoiceDetail': [
                { label: 'Sales Invoices', route: '/sales/invoices', icon: 'fas fa-file-invoice' },
                { label: 'Invoice Detail', icon: 'fas fa-eye' }
            ],

            // Deliveries
            'DeliveryList': [
                { label: 'Deliveries', route: '/sales/deliveries', icon: 'fas fa-truck' }
            ],
            'CreateDelivery': [
                { label: 'Deliveries', route: '/sales/deliveries', icon: 'fas fa-truck' },
                { label: 'Create Delivery', icon: 'fas fa-plus' }
            ],
            'DeliveryDetail': [
                { label: 'Deliveries', route: '/sales/deliveries', icon: 'fas fa-truck' },
                { label: 'Delivery Detail', icon: 'fas fa-eye' }
            ],

            // Purchase Orders
            'PurchaseOrders': [
                { label: 'Purchase Orders', route: '/purchasing/orders', icon: 'fas fa-clipboard-list' }
            ],
            'CreatePurchaseOrder': [
                { label: 'Purchase Orders', route: '/purchasing/orders', icon: 'fas fa-clipboard-list' },
                { label: 'Create Purchase Order', icon: 'fas fa-plus' }
            ],
            'PurchaseOrderDetail': [
                { label: 'Purchase Orders', route: '/purchasing/orders', icon: 'fas fa-clipboard-list' },
                { label: 'Purchase Order Detail', icon: 'fas fa-eye' }
            ],

            // Vendors
            'VendorList': [
                { label: 'Vendors', route: '/purchasing/vendors', icon: 'fas fa-users' }
            ],
            'VendorCreate': [
                { label: 'Vendors', route: '/purchasing/vendors', icon: 'fas fa-users' },
                { label: 'Add New Vendor', icon: 'fas fa-user-plus' }
            ],
            'VendorDetail': [
                { label: 'Vendors', route: '/purchasing/vendors', icon: 'fas fa-users' },
                { label: 'Vendor Details', icon: 'fas fa-eye' }
            ],

            // Inventory Items
            'Items': [
                { label: 'Items Management', route: '/items', icon: 'fas fa-box' }
            ],
            'ItemDetail': [
                { label: 'Items Management', route: '/items', icon: 'fas fa-box' },
                { label: 'Item Details', icon: 'fas fa-eye' }
            ],

            // Item Categories
            'ItemCategories': [
                { label: 'Item Categories', route: '/item-categories', icon: 'fas fa-tags' }
            ],

            // Stock Transactions
            'StockTransactions': [
                { label: 'Stock Transactions', route: '/stock-transactions', icon: 'fas fa-random' }
            ],
            'CreateStockTransaction': [
                { label: 'Stock Transactions', route: '/stock-transactions', icon: 'fas fa-random' },
                { label: 'Create Transaction', icon: 'fas fa-plus' }
            ],
            'StockTransactionDetail': [
                { label: 'Stock Transactions', route: '/stock-transactions', icon: 'fas fa-random' },
                { label: 'Transaction Detail', icon: 'fas fa-eye' }
            ],

            // Warehouses
            'Warehouses': [
                { label: 'Warehouses', route: '/warehouses', icon: 'fas fa-warehouse' }
            ],
            'WarehouseDetail': [
                { label: 'Warehouses', route: '/warehouses', icon: 'fas fa-warehouse' },
                { label: 'Warehouse Details', icon: 'fas fa-eye' }
            ],

            // Manufacturing
            'BOMList': [
                { label: 'Bill of Materials', route: '/manufacturing/boms', icon: 'fas fa-clipboard-list' }
            ],
            'CreateBOM': [
                { label: 'Bill of Materials', route: '/manufacturing/boms', icon: 'fas fa-clipboard-list' },
                { label: 'Create BOM', icon: 'fas fa-plus' }
            ],
            'BOMDetail': [
                { label: 'Bill of Materials', route: '/manufacturing/boms', icon: 'fas fa-clipboard-list' },
                { label: 'BOM Details', icon: 'fas fa-eye' }
            ],

            // Job Orders
            'WorkOrders': [
                { label: 'Job Orders', route: '/manufacturing/work-orders', icon: 'fas fa-clipboard-list' }
            ],
            'CreateWorkOrder': [
                { label: 'Job Orders', route: '/manufacturing/work-orders', icon: 'fas fa-clipboard-list' },
                { label: 'Create Job Order', icon: 'fas fa-plus' }
            ],
            'WorkOrderDetail': [
                { label: 'Job Orders', route: '/manufacturing/work-orders', icon: 'fas fa-clipboard-list' },
                { label: 'Job Order Details', icon: 'fas fa-eye' }
            ],

            // Accounting
            'CurrencyRates': [
                { label: 'Exchange Rates', route: '/currency-rates', icon: 'fas fa-money-bill-wave' }
            ],
            'CreateCurrencyRate': [
                { label: 'Exchange Rates', route: '/currency-rates', icon: 'fas fa-money-bill-wave' },
                { label: 'Create Exchange Rate', icon: 'fas fa-plus' }
            ],

            // Quality Management
            'quality-inspections': [
                { label: 'Quality Inspections', route: '/quality-inspections', icon: 'fas fa-clipboard-check' }
            ],
            'quality-inspections-create': [
                { label: 'Quality Inspections', route: '/quality-inspections', icon: 'fas fa-clipboard-check' },
                { label: 'Create Inspection', icon: 'fas fa-plus' }
            ]
        };

        // Enhanced breadcrumb computation
        const breadcrumbItems = computed(() => {
            const routeName = route.name;

            // If route has specific hierarchy, use it
            if (routeHierarchy[routeName]) {
                return routeHierarchy[routeName];
            }

            // Fallback: try to create breadcrumb from route path
            const pathSegments = route.path.split('/').filter(segment => segment);
            const items = [];

            if (pathSegments.length > 0) {
                // Create breadcrumb from path segments
                let currentPath = '';
                pathSegments.forEach((segment, index) => {
                    currentPath += '/' + segment;

                    // Skip if it's just an ID (number)
                    if (!isNaN(segment)) {
                        return;
                    }

                    // Determine if this is the last segment
                    const isLast = index === pathSegments.length - 1;

                    // Create readable label
                    const label = segment
                        .split('-')
                        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                        .join(' ');

                    items.push({
                        label: label,
                        route: isLast ? null : currentPath,
                        icon: getIconForSegment(segment)
                    });
                });
            }

            // If no items generated, show current page title
            if (items.length === 0) {
                items.push({
                    label: pageTitle.value,
                    route: null,
                    icon: 'fas fa-file'
                });
            }

            return items;
        });

        // Helper function to get icon for path segment
        const getIconForSegment = (segment) => {
            const iconMap = {
                'sales': 'fas fa-shopping-cart',
                'purchasing': 'fas fa-shopping-bag',
                'inventory': 'fas fa-box',
                'manufacturing': 'fas fa-industry',
                'quality': 'fas fa-check-circle',
                'accounting': 'fas fa-calculator',
                'customers': 'fas fa-users',
                'vendors': 'fas fa-users',
                'orders': 'fas fa-clipboard-list',
                'quotations': 'fas fa-file-invoice-dollar',
                'invoices': 'fas fa-file-invoice',
                'deliveries': 'fas fa-truck',
                'items': 'fas fa-box',
                'warehouses': 'fas fa-warehouse',
                'reports': 'fas fa-chart-bar',
                'admin': 'fas fa-user-shield',
                'create': 'fas fa-plus',
                'edit': 'fas fa-edit',
                'view': 'fas fa-eye'
            };

            return iconMap[segment] || 'fas fa-file';
        };

        // Sample data
        const notificationCount = ref(3);
        const notifications = ref([
            {
                id: 1,
                title: "New order received from ABC Corp",
                time: "5 minutes ago",
                icon: "fas fa-shopping-cart",
                type: "success"
            },
            {
                id: 2,
                title: "Low stock alert for Product X",
                time: "1 hour ago",
                icon: "fas fa-exclamation-triangle",
                type: "warning"
            },
            {
                id: 3,
                title: "Monthly report is ready",
                time: "2 hours ago",
                icon: "fas fa-file-alt",
                type: "info"
            }
        ]);

        const quickStats = ref([
            // {
            //     value: "1,234",
            //     label: "Total Items",
            //     icon: "fas fa-box",
            //     gradient: "linear-gradient(135deg, #667eea 0%, #764ba2 100%)",
            //     trend: "up",
            //     change: "+12%"
            // },
            // {
            //     value: "$45,678",
            //     label: "Monthly Sales",
            //     icon: "fas fa-chart-line",
            //     gradient: "linear-gradient(135deg, #f093fb 0%, #f5576c 100%)",
            //     trend: "up",
            //     change: "+8.5%"
            // },
            // {
            //     value: "89",
            //     label: "Pending Orders",
            //     icon: "fas fa-clock",
            //     gradient: "linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)",
            //     trend: "down",
            //     change: "-5%"
            // },
            // {
            //     value: "96%",
            //     label: "System Uptime",
            //     icon: "fas fa-server",
            //     gradient: "linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)",
            //     trend: "up",
            //     change: "+2%"
            // }
        ]);

        const pageTitle = computed(() => {
            const titleMap = {
                // Dashboard
                "Dashboard": "Dashboard",

                // Inventory Management
                "Items": "Items Management",
                "ItemDetail": "Item Details",
                "ItemCategories": "Item Categories",
                "ItemCategoriesEnhanced": "Enhanced Item Categories",
                "UnitOfMeasures": "Units of Measure",
                "UnitOfMeasureDetail": "Unit of Measure Details",
                "ItemPriceManagement": "Item Prices Management",

                // Stock Management
                "StockTransactions": "Stock Transactions",
                "CreateStockTransaction": "Create Stock Transaction",
                "StockTransactionDetail": "Stock Transaction Details",
                "ItemMovementHistory": "Item Movement History",
                "StockTransfer": "Stock Transfer",
                "ItemStocks": "Inventory Stock",
                "ItemStockDetail": "Item Stock Details",
                "WarehouseStock": "Warehouse Stock",
                "StockAdjustment": "Stock Adjustment",
                "StockReservation": "Stock Reservation",
                "NegativeStocks": "Negative Stocks",
                "StockAdjustments": "Stock Adjustments",
                "CreateStockAdjustment": "Create Stock Adjustment",
                "StockAdjustmentDetail": "Stock Adjustment Details",
                "EditStockAdjustment": "Edit Stock Adjustment",
                "ApproveStockAdjustment": "Approve Stock Adjustment",

                // Cycle Counting
                "CycleCountList": "Cycle Counting",
                "CreateCycleCount": "Create Cycle Count",
                "GenerateCycleCounts": "Generate Count Tasks",
                "CycleCountDetail": "Cycle Count Details",
                "EditCycleCount": "Edit Cycle Count",
                "CycleCountApproval": "Cycle Count Approval",

                // Batch Management
                "ItemBatches": "Item Batches",
                "CreateBatch": "Create Batch",
                "BatchDetail": "Batch Details",
                "EditBatch": "Edit Batch",
                "ExpiryDashboard": "Expiry Management",

                // Customers
                "customers.index": "Customer Management",
                "customers.create": "Add New Customer",
                "customers.show": "Customer Details",
                "customers.edit": "Edit Customer",

                // Sales Quotations
                "SalesQuotations": "Sales Quotations",
                "CreateSalesQuotation": "Create Sales Quotation",
                "SalesQuotationDetail": "Sales Quotation Details",
                "EditSalesQuotation": "Edit Sales Quotation",
                "PrintSalesQuotation": "Print Sales Quotation",

                // Sales Forecasts
                "SalesForecastsList": "Sales Forecasts",
                "SalesForecastDetail": "Sales Forecast Details",
                "SalesForecastAnalytics": "Sales Forecast Analytics",
                "CreateSalesForecast": "Create Sales Forecast",
                "EditSalesForecast": "Edit Sales Forecast",
                "ConsolidatedForecastView": "Consolidated Forecast View",
                "ImportForecastForm": "Import Forecast",
                "ForecastAccuracyAnalysis": "Forecast Accuracy Analysis",
                "ForecastDashboard": "Forecast Dashboard",
                "UpdateActualsPage": "Update Actuals",
                "ForecastHistoryView": "Forecast Version History",
                "ForecastVolatilityDashboard": "Forecast Volatility Monitor",
                "ForecastTrendAnalysis": "Forecast Trend Analysis",

                // Sales Orders
                "SalesOrders": "Sales Orders",
                "CreateSalesOrder": "Create Sales Order",
                "SalesOrderDetail": "Sales Order Details",
                "EditSalesOrder": "Edit Sales Order",
                "CreateOrderFromQuotation": "Create Order from Quotation",
                "PrintSalesOrder": "Print Sales Order",

                // Sales Invoices
                "SalesInvoices": "Sales Invoices",
                "CreateSalesInvoice": "Create Sales Invoice",
                "CreateInvoiceFromDelivery": "Create Invoice from Delivery",
                "SalesInvoiceDetail": "Sales Invoice Details",
                "EditSalesInvoice": "Edit Sales Invoice",
                "SalesInvoicePayment": "Sales Invoice Payment",
                "PrintSalesInvoice": "Print Sales Invoice",

                // Sales Deliveries
                "DeliveryList": "Deliveries",
                "CreateDelivery": "Create Delivery",
                "DeliveryDetail": "Delivery Details",
                "EditDelivery": "Edit Delivery",
                "PrintDeliveryOrder": "Print Delivery Order",

                // Sales Returns
                "SalesReturns": "Sales Returns",
                "CreateSalesReturn": "Create Sales Return",
                "SalesReturnDetail": "Sales Return Details",
                "EditSalesReturn": "Edit Sales Return",

                // Manufacturing - BOM
                "BOMList": "Bill of Materials",
                "CreateBOM": "Create BOM",
                "BOMDetail": "BOM Details",
                "EditBOM": "Edit BOM",

                // Manufacturing - Routing
                "RoutingList": "Routing",
                "CreateRouting": "Create Routing",
                "RoutingDetail": "Routing Details",
                "EditRouting": "Edit Routing",

                // Manufacturing - Work Centers
                "WorkCentersList": "Work Centers",
                "CreateWorkCenter": "Create Work Center",
                "WorkCenterDetail": "Work Center Details",
                "EditWorkCenter": "Edit Work Center",
                "WorkCenterSchedule": "Work Center Schedule",

                // Manufacturing - Job Orders
                "WorkOrders": "Job Orders",
                "CreateWorkOrder": "Create Job Order",
                "WorkOrderDetail": "Job Order Details",
                "EditWorkOrder": "Edit Job Order",
                "WorkOrderOperation": "Job Order Operation",
                "ManufacturingDashboard": "Manufacturing Dashboard",

                // Manufacturing - Job Orders Process
                "ProductionOrders": "Job Orders Process",
                "CreateProductionOrder": "Create Job Orders Process",
                "ProductionOrderDetail": "Job Orders Process Details",
                "EditProductionOrder": "Edit Job Orders Process",
                "AddProductionConsumption": "Add Job Orders Process Consumption",
                "EditProductionConsumption": "Edit Job Orders Process Consumption",
                "CompleteProductionOrder": "Complete Job Orders Process",

                // Purchasing - Vendors
                "VendorList": "Vendor Management",
                "VendorCreate": "Add New Vendor",
                "VendorDetail": "Vendor Details",
                "VendorEdit": "Edit Vendor",

                // Purchasing - Requisitions
                "PurchaseRequisitionList": "Purchase Requisitions",
                "CreatePurchaseRequisition": "Create Purchase Requisition",
                "PurchaseRequisitionDetail": "Purchase Requisition Details",
                "EditPurchaseRequisition": "Edit Purchase Requisition",
                "ApprovePurchaseRequisition": "Approve Purchase Requisition",
                "ConvertToRFQ": "Convert to RFQ",
                "PRApprovalList": "PR Approvals",
                "PRToRFQList": "PR to RFQ",

                // Purchasing - RFQ
                "RFQList": "Request for Quotations",
                "CreateRFQ": "Create RFQ",
                "RFQDetail": "RFQ Details",
                "EditRFQ": "Edit RFQ",
                "SendRFQ": "Send RFQ",
                "CompareRFQ": "Compare RFQ",

                // Purchasing - Vendor Quotations
                "VendorQuotations": "Vendor Quotations",
                "CreateVendorQuotation": "Create Vendor Quotation",
                "VendorQuotationDetail": "Vendor Quotation Details",
                "EditVendorQuotation": "Edit Vendor Quotation",
                "CompareVendorQuotations": "Compare Quotations",
                "CreatePOFromQuotation": "Create PO from Quotation",

                // Purchasing - Purchase Orders
                "PurchaseOrders": "Purchase Orders",
                "CreatePurchaseOrder": "Create Purchase Order",
                "PurchaseOrderDetail": "Purchase Order Details",
                "EditPurchaseOrder": "Edit Purchase Order",
                "PurchaseOrderTrack": "Track Purchase Order",

                // Purchasing - Goods Receipts
                "GoodsReceiptList": "Goods Receipts",
                "CreateGoodsReceipt": "Create Goods Receipt",
                "PendingReceiptsDashboard": "Receipts Dashboard",
                "GoodsReceiptDetail": "Goods Receipt Details",
                "EditGoodsReceipt": "Edit Goods Receipt",
                "ConfirmGoodsReceipt": "Confirm Goods Receipt",

                // Purchasing - Vendor Invoices
                "VendorInvoiceList": "Vendor Invoices",
                "VendorInvoiceCreate": "Create Vendor Invoice",
                "VendorInvoiceDetail": "Vendor Invoice Details",
                "VendorInvoiceEdit": "Edit Vendor Invoice",
                "VendorInvoiceApproval": "Vendor Invoice Approval",
                "VendorInvoicePayment": "Vendor Invoice Payment",

                // Warehouses
                "Warehouses": "Warehouses",
                "WarehouseDetail": "Warehouse Details",
                "WarehouseZones": "Warehouse Zones",
                "WarehouseLocations": "Warehouse Locations",
                "LocationInventory": "Location Inventory",

                // Material Planning
                "MaterialPlans": "Material Planning",
                "MaterialPlanDetail": "Material Plan Details",
                "MaterialPlanGeneration": "Generate Material Plans",
                "PRGenerationFromMaterialPlan": "Generate PR from Material Plan",

                // Accounting
                "CurrencyRates": "Exchange Rates",
                "CreateCurrencyRate": "Create Exchange Rate",
                "CurrencyRateDetail": "Exchange Rate Details",
                "EditCurrencyRate": "Edit Exchange Rate",
                "CurrencyConverter": "Currency Converter",

                // Quality Management
                "quality-inspections": "Quality Inspections",
                "quality-inspections-create": "Create Quality Inspection",
                "quality-inspection-detail": "Quality Inspection Details",
                "quality-inspection-edit": "Edit Quality Inspection",
                "quality-parameters-create": "Create Quality Parameters",
                "quality-parameters-edit": "Edit Quality Parameters",
                "quality-dashboard": "Quality Dashboard",

                // Administration
                "AdminDashboard": "Admin Dashboard",
                "SystemSettings": "System Settings",
                "UserList": "User Management",

                // Legacy routes yang sudah ada sebelumnya
                "Customers": "Customers",
                "Users": "User Management"
            };

            return titleMap[route.name] || "Dashboard";
        });

        // Methods
        const toggleSidebar = () => {
            sidebarCollapsed.value = !sidebarCollapsed.value;
            localStorage.setItem("sidebarCollapsed", sidebarCollapsed.value);
        };

        const closeSidebar = () => {
            if (isMobile.value) {
                sidebarCollapsed.value = true;
            }
        };

        const toggleSubmenu = (menu) => {
            if (sidebarCollapsed.value) {
                // If sidebar is collapsed, expand it first
                sidebarCollapsed.value = false;
                localStorage.setItem("sidebarCollapsed", false);
            }
            activeSubmenu.value = activeSubmenu.value === menu ? null : menu;
        };

        const handleSearchBlur = () => {
            setTimeout(() => {
                searchFocused.value = false;
            }, 200);
        };

        const toggleUserMenu = () => {
            userMenuOpen.value = !userMenuOpen.value;
        };

        const toggleTheme = () => {
            isDarkMode.value = !isDarkMode.value;
            localStorage.setItem("darkMode", isDarkMode.value);

            // Apply dark mode class to document
            if (isDarkMode.value) {
                document.documentElement.classList.add("dark");
            } else {
                document.documentElement.classList.remove("dark");
            }
        };

        const markAllRead = () => {
            notificationCount.value = 0;
            showNotifications.value = false;
        };

        const logout = async () => {
            isLoading.value = true;
            try {
                await axios.post("/api/auth/logout");
            } catch (error) {
                console.error("Logout error:", error);
            } finally {
                localStorage.removeItem("token");
                localStorage.removeItem("user");
                localStorage.removeItem("sidebarCollapsed");
                localStorage.removeItem("darkMode");
                axios.defaults.headers.common["Authorization"] = "";
                isLoading.value = false;
                router.push("/login");
            }
        };

        // Quick actions
        const quickCreateItem = () => {
            router.push("/items/create");
            showQuickActions.value = false;
        };

        const quickCreateOrder = () => {
            router.push("/sales/orders/create");
            showQuickActions.value = false;
        };

        const quickCreateCustomer = () => {
            router.push("/sales/customers/create");
            showQuickActions.value = false;
        };

        const viewAnalytics = () => {
            router.push("/analytics");
            showQuickActions.value = false;
        };

        const navigateToProfile = () => {
            router.push("/profile");
            userMenuOpen.value = false;
        };

        const navigateToSettings = () => {
            router.push("/settings");
            userMenuOpen.value = false;
        };

        // Handle window resize
        const handleResize = () => {
            const newIsMobile = window.innerWidth < 768;
            if (newIsMobile !== isMobile.value) {
                isMobile.value = newIsMobile;
                if (isMobile.value) {
                    sidebarCollapsed.value = true;
                }
            }
        };

        // Close dropdowns when clicking outside
        const handleClickOutside = (event) => {
            const notifications = document.querySelector(".notifications-dropdown");
            const userDropdown = document.querySelector(".user-dropdown");
            const searchResults = document.querySelector(".search-results");

            if (notifications && !notifications.contains(event.target) &&
                !event.target.closest('.action-btn')) {
                showNotifications.value = false;
            }

            if (userDropdown && !userDropdown.contains(event.target) &&
                !event.target.closest('.user-section')) {
                userMenuOpen.value = false;
            }

            if (searchResults && !searchResults.contains(event.target) &&
                !event.target.closest('.search-container')) {
                searchFocused.value = false;
            }
        };

        // Lifecycle hooks
        onMounted(() => {
            // Add event listeners
            window.addEventListener('resize', handleResize);
            document.addEventListener('click', handleClickOutside);

            // Apply dark mode if saved
            if (isDarkMode.value) {
                document.documentElement.classList.add("dark");
            }

            // Handle initial mobile state
            handleResize();

            // Auto-collapse sidebar on mobile
            if (isMobile.value) {
                sidebarCollapsed.value = true;
            }
        });

        onUnmounted(() => {
            // Remove event listeners
            window.removeEventListener('resize', handleResize);
            document.removeEventListener('click', handleClickOutside);
        });

        return {
            // Refs
            user,
            searchQuery,
            searchFocused,
            showNotifications,
            showQuickActions,
            isDarkMode,
            isLoading,
            userMenuOpen,
            sidebarCollapsed,
            activeSubmenu,
            isMobile,
            notificationCount,
            notifications,
            quickStats,

            // Computed
            pageTitle,
            breadcrumbItems,

            // Methods
            toggleSidebar,
            closeSidebar,
            toggleSubmenu,
            handleSearchBlur,
            toggleUserMenu,
            toggleTheme,
            markAllRead,
            logout,
            quickCreateItem,
            quickCreateOrder,
            quickCreateCustomer,
            viewAnalytics,
            navigateToProfile,
            navigateToSettings,
        };
    },
};
</script>
/* src/layouts/AppLayout.vue - Style Section */
<style scoped>
/* CSS Variables */
:root {
    --primary-gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    --secondary-gradient: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
    --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
    --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    --sidebar-width: 280px;
    --sidebar-collapsed-width: 80px;
    --header-height: 70px;
    --content-padding: 2rem;
    --border-radius: 16px;
    --border-radius-lg: 24px;
    --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    --box-shadow-lg: 0 20px 50px rgba(0, 0, 0, 0.15);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

    /* Light theme colors */
    --bg-primary: #ffffff;
    --bg-secondary: #f8fafc;
    --bg-tertiary: #f1f5f9;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --text-muted: #94a3b8;
    --border-color: #e2e8f0;
    --sidebar-bg: #ffffff;
    --header-bg: #ffffff;
    --card-bg: #ffffff;
}

.dark {
    /* Dark theme colors */
    --bg-primary: #0f172a;
    --bg-secondary: #1e293b;
    --bg-tertiary: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --text-muted: #94a3b8;
    --border-color: #334155;
    --sidebar-bg: #1e293b;
    --header-bg: rgba(15, 23, 42, 0.95);
    --card-bg: #1e293b;
}

/* Global Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
}

/* App Container */
.app-container {
    min-height: 100vh;
    background: var(--bg-secondary);
    position: relative;
    transition: var(--transition);
    display: flex;
}

/* Animated Background */
.background-animation {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #cbd5e1 100%);
    overflow: hidden;
}

.dark .background-animation {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
}

.floating-orbs {
    position: absolute;
    width: 100%;
    height: 100%;
}

.orb {
    position: absolute;
    border-radius: 50%;
    background: rgba(99, 102, 241, 0.05);
    animation: float 20s infinite ease-in-out;
}

.dark .orb {
    background: rgba(99, 102, 241, 0.1);
}

.orb-1 {
    width: 200px;
    height: 200px;
    top: 10%;
    left: 10%;
    animation-delay: 0s;
}

.orb-2 {
    width: 300px;
    height: 300px;
    top: 50%;
    right: 10%;
    animation-delay: 5s;
}

.orb-3 {
    width: 150px;
    height: 150px;
    bottom: 20%;
    left: 60%;
    animation-delay: 10s;
}

.orb-4 {
    width: 250px;
    height: 250px;
    top: 30%;
    left: 70%;
    animation-delay: 15s;
}

.orb-5 {
    width: 180px;
    height: 180px;
    bottom: 10%;
    right: 30%;
    animation-delay: 20s;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px) translateX(0px) rotate(0deg);
        opacity: 0.3;
    }
    25% {
        transform: translateY(-30px) translateX(20px) rotate(90deg);
        opacity: 0.1;
    }
    50% {
        transform: translateY(-20px) translateX(-20px) rotate(180deg);
        opacity: 0.2;
    }
    75% {
        transform: translateY(20px) translateX(30px) rotate(270deg);
        opacity: 0.15;
    }
}

/* Sidebar */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: var(--sidebar-width);
    height: 100vh;
    background: var(--sidebar-bg);
    border-right: 2px solid var(--border-color);
    box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
    transition: var(--transition);
    z-index: 1000;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.dark .sidebar {
    box-shadow: 4px 0 20px rgba(0, 0, 0, 0.3);
}

.sidebar.collapsed {
    width: var(--sidebar-collapsed-width);
}

.sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 1rem;
    border-bottom: 2px solid var(--border-color);
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%);
    min-height: var(--header-height);
}

.dark .sidebar-header {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
}

.brand-logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
}

.logo-icon {
    width: 40px;
    height: 40px;
    background: var(--primary-gradient);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.3);
    animation: pulse 3s ease-in-out infinite;
    flex-shrink: 0;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.brand-text {
    overflow: hidden;
    white-space: nowrap;
}

.brand-text h2 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
    line-height: 1.2;
}

.brand-text p {
    font-size: 0.7rem;
    color: var(--text-muted);
    margin: 0;
    line-height: 1.2;
}

.sidebar-toggle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    color: var(--text-secondary);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
    flex-shrink: 0;
}

.sidebar-toggle:hover {
    background: var(--primary-gradient);
    color: white;
    border-color: transparent;
    transform: scale(1.1);
}

/* Sidebar Navigation */
.sidebar-nav {
    flex: 1;
    overflow-y: auto;
    padding: 1rem 0;
}

.sidebar-nav::-webkit-scrollbar {
    width: 4px;
}

.sidebar-nav::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(99, 102, 241, 0.2);
    border-radius: 4px;
}

.sidebar-nav::-webkit-scrollbar-thumb:hover {
    background: rgba(99, 102, 241, 0.4);
}

.nav-section {
    padding: 0 0.5rem;
}

.nav-item {
    margin-bottom: 0.25rem;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    border-radius: 12px;
    color: var(--text-secondary);
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    transition: var(--transition);
    cursor: pointer;
    position: relative;
    margin: 0.25rem 0;
}

.nav-link:hover {
    background: rgba(99, 102, 241, 0.1);
    color: var(--text-primary);
    transform: translateX(3px);
}

.nav-link.active {
    background: var(--primary-gradient);
    color: white;
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.3);
}

.nav-link i {
    width: 20px;
    font-size: 1rem;
    text-align: center;
    flex-shrink: 0;
}

.nav-link span {
    overflow: hidden;
    white-space: nowrap;
}

.submenu-arrow {
    margin-left: auto;
    font-size: 0.7rem;
    transition: transform 0.3s ease;
}

.submenu-arrow.rotated {
    transform: rotate(180deg);
}

.nav-item.has-submenu.active > .nav-link {
    background: rgba(99, 102, 241, 0.1);
    color: #6366f1;
}

/* Submenu */
.submenu {
    background: rgba(99, 102, 241, 0.02);
    border-radius: 8px;
    margin: 0.5rem 0 0.5rem 1rem;
    padding: 0.5rem 0;
    border-left: 3px solid rgba(99, 102, 241, 0.2);
    animation: slideDown 0.3s ease;
}

.dark .submenu {
    background: rgba(99, 102, 241, 0.05);
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.submenu-section {
    margin-bottom: 1rem;
}

.submenu-section:last-child {
    margin-bottom: 0;
}

.submenu-section h4 {
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin: 0 0 0.5rem 1rem;
}

.submenu-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 1rem;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 0.85rem;
    transition: var(--transition);
    border-radius: 6px;
    margin: 0.125rem 0.5rem;
}

.submenu-link:hover {
    background: rgba(99, 102, 241, 0.1);
    color: #6366f1;
    transform: translateX(3px);
}

.submenu-link.router-link-active {
    background: rgba(99, 102, 241, 0.15);
    color: #6366f1;
    font-weight: 600;
}

.submenu-link i {
    width: 16px;
    font-size: 0.8rem;
    text-align: center;
    flex-shrink: 0;
}

/* Sidebar Footer */
.sidebar-footer {
    padding: 1rem;
    border-top: 2px solid var(--border-color);
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.02) 0%, rgba(139, 92, 246, 0.02) 100%);
}

.dark .sidebar-footer {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%);
}

.footer-stats {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: var(--text-muted);
}

.stat-item i {
    color: #10b981;
    width: 16px;
    text-align: center;
}

/* Main Wrapper */
.main-wrapper {
    flex: 1;
    margin-left: var(--sidebar-width);
    transition: var(--transition);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.sidebar-collapsed .main-wrapper {
    margin-left: var(--sidebar-collapsed-width);
}

/* Top Header */
.top-header {
    position: sticky;
    top: 0;
    z-index: 999;
    background: var(--header-bg);
    backdrop-filter: blur(20px);
    border-bottom: 2px solid var(--border-color);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 2rem;
    padding: 0 2rem;
    height: var(--header-height);
}

.dark .top-header {
    background: var(--header-bg);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.header-left {
    display: none;
}

.mobile-menu-toggle {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    color: var(--text-secondary);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
}

.mobile-menu-toggle:hover {
    background: var(--primary-gradient);
    color: white;
    border-color: transparent;
}

.header-center {
    display: flex;
    justify-content: center;
    max-width: 600px;
    margin: 0 auto;
}

/* Search */
.search-container {
    position: relative;
}

.search-box {
    position: relative;
    transition: var(--transition);
}

.search-box.active {
    transform: scale(1.02);
}

.search-input {
    background: #ffffff;
    border: 2px solid var(--border-color);
    border-radius: 25px;
    padding: 0.75rem 1rem 0.75rem 3rem;
    width: 320px;
    transition: var(--transition);
    color: var(--text-primary);
    font-size: 0.9rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}

.dark .search-input {
    background: var(--card-bg);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

.search-input:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    width: 380px;
}

.search-input::placeholder {
    color: var(--text-muted);
}

.search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 0.9rem;
}

.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #ffffff;
    border-radius: var(--border-radius);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
    margin-top: 0.5rem;
    z-index: 200;
    border: 2px solid var(--border-color);
    max-height: 400px;
    overflow-y: auto;
}

.dark .search-results {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
}

.search-category {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.search-category:last-child {
    border-bottom: none;
}

.search-category h4 {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}

.search-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    cursor: pointer;
    transition: var(--transition);
    color: var(--text-primary);
    margin: 0.25rem 0;
}

.search-item:hover {
    background: rgba(99, 102, 241, 0.1);
    color: #6366f1;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    position: relative;
}

/* Quick Actions */
.quick-actions {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #ffffff;
    border: 2px solid var(--border-color);
    color: var(--text-secondary);
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    font-size: 1rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}

.dark .action-btn {
    background: var(--card-bg);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

.action-btn:hover {
    background: var(--primary-gradient);
    color: white;
    border-color: transparent;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
}

.notification-badge {
    position: absolute;
    top: 6px;
    right: 6px;
    background: var(--secondary-gradient);
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-3px); }
    60% { transform: translateY(-2px); }
}

/* User Section */
.user-section {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 1rem;
    background: #ffffff;
    border: 2px solid var(--border-color);
    border-radius: 25px;
    cursor: pointer;
    transition: var(--transition);
    position: relative;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}

.dark .user-section {
    background: var(--card-bg);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

.user-section:hover {
    border-color: #6366f1;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(99, 102, 241, 0.15);
}

.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--primary-gradient);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 0.9rem;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
    border: 3px solid white;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.dark .user-avatar {
    border: 3px solid var(--bg-secondary);
}

.user-info {
    display: flex;
    flex-direction: column;
    text-align: left;
}

.username {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.85rem;
    line-height: 1.2;
}

.user-role {
    font-size: 0.7rem;
    color: var(--text-muted);
    line-height: 1.2;
    font-weight: 500;
}

.user-arrow {
    color: var(--text-muted);
    font-size: 0.7rem;
    transition: transform 0.3s ease;
}

.user-arrow.rotated {
    transform: rotate(180deg);
}

/* Dropdowns */
.notifications-dropdown,
.user-dropdown {
    position: absolute;
    top: calc(100% + 0.5rem);
    right: 0;
    background: #ffffff;
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
    z-index: 200;
    min-width: 320px;
    max-height: 400px;
    overflow: hidden;
}

.dark .notifications-dropdown,
.dark .user-dropdown {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
}

.notifications-header {
    padding: 1.5rem;
    border-bottom: 2px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%);
}

.dark .notifications-header {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
}

.notifications-header h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.mark-all-btn {
    background: none;
    border: none;
    color: #6366f1;
    font-size: 0.85rem;
    cursor: pointer;
    font-weight: 500;
}

.mark-all-btn:hover {
    text-decoration: underline;
}

.notifications-list {
    max-height: 300px;
    overflow-y: auto;
}

.notification-item {
    display: flex;
    gap: 1rem;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    transition: var(--transition);
    cursor: pointer;
    border-radius: 8px;
    margin: 0.25rem 0.5rem;
}

.notification-item:last-child {
    border-bottom: none;
    margin-bottom: 0.5rem;
}

.notification-item:hover {
    background: rgba(99, 102, 241, 0.05);
    transform: translateX(3px);
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 0.9rem;
}

.notification-icon.success {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.notification-icon.warning {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}

.notification-icon.info {
    background: rgba(99, 102, 241, 0.1);
    color: #6366f1;
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: 500;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.notification-time {
    color: var(--text-muted);
    font-size: 0.8rem;
}

/* User Dropdown */
.user-profile {
    padding: 1.5rem;
    border-bottom: 2px solid var(--border-color);
    display: flex;
    align-items: center;
    gap: 1rem;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%);
}

.dark .user-profile {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
}

.user-avatar-large {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: var(--primary-gradient);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.3rem;
    box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
    border: 4px solid white;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.dark .user-avatar-large {
    border: 4px solid var(--bg-secondary);
}

.user-details h4 {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.25rem 0;
}

.user-details p {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin: 0;
}

.user-menu-items {
    padding: 1rem 0;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1.5rem;
    color: var(--text-primary);
    transition: var(--transition);
    cursor: pointer;
    font-size: 0.9rem;
}

.dropdown-item:not(.logout-item) {
    border-radius: 8px;
    margin: 0.25rem 0.5rem;
}

.dropdown-item:not(.logout-item):hover {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
    color: #6366f1;
    transform: translateX(3px);
}

.dropdown-item:not(.logout-item) i {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(99, 102, 241, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6366f1;
    font-size: 0.85rem;
    transition: all 0.3s ease;
}

.dropdown-item:not(.logout-item):hover i {
    background: rgba(99, 102, 241, 0.2);
    transform: scale(1.1);
}

.dropdown-item.logout-item {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border: 1px solid #fca5a5;
    border-radius: 8px;
    margin: 0.5rem;
    font-weight: 600;
    color: #dc2626;
    position: relative;
    transition: all 0.3s ease;
}

.dark .dropdown-item.logout-item {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #f87171;
}

.dropdown-item.logout-item:hover {
    background: linear-gradient(135deg, #fca5a5 0%, #f87171 100%);
    color: white;
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
}

.dark .dropdown-item.logout-item:hover {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    border-color: #ef4444;
}

.logout-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(220, 38, 38, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #dc2626;
    transition: all 0.3s ease;
}

.dropdown-item.logout-item:hover .logout-icon {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    transform: scale(1.1);
}

.logout-arrow {
    margin-left: auto;
    opacity: 0.6;
    transition: all 0.3s ease;
}

.dropdown-item.logout-item:hover .logout-arrow {
    opacity: 1;
    transform: translateX(5px);
}

.dropdown-divider {
    height: 1px;
    background: var(--border-color);
    margin: 0.5rem 0;
}

/* Main Content */
.main-content {
    flex: 1;
    padding-top: 2rem;
}

/* Breadcrumb */
.breadcrumb-section {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 var(--content-padding);
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-muted);
    font-size: 0.9rem;
    font-weight: 500;
}

.breadcrumb-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-muted);
    text-decoration: none;
    transition: var(--transition);
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
}

.breadcrumb-item:hover {
    color: #6366f1;
    background: rgba(99, 102, 241, 0.1);
}

.breadcrumb-separator {
    font-size: 0.7rem;
    color: var(--text-muted);
    opacity: 0.6;
}

.breadcrumb-current {
    color: var(--text-primary);
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    background: rgba(99, 102, 241, 0.1);
    border-radius: 6px;
}

.page-actions {
    display: flex;
    gap: 0.5rem;
}

.action-button {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    font-weight: 500;
    font-size: 0.85rem;
    transition: var(--transition);
}

.action-button.primary {
    background: var(--primary-gradient);
    color: white;
}

.action-button.primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
}

.action-button.secondary {
    background: var(--card-bg);
    color: var(--text-secondary);
    border: 2px solid var(--border-color);
}

.action-button.secondary:hover {
    border-color: #6366f1;
    color: #6366f1;
}

/* Page Header */
.page-header {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 var(--content-padding);
    margin-bottom: 2rem;
}

.page-title-section {
    margin-bottom: 2rem;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 0.5rem 0;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.page-subtitle {
    color: var(--text-muted);
    font-size: 1.1rem;
    margin: 0;
}

/* Quick Stats */
.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.stat-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--primary-gradient);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--box-shadow-lg);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.stat-content h3 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 0.25rem 0;
}

.stat-content p {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin: 0 0 0.5rem 0;
}

.stat-trend {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8rem;
    font-weight: 500;
}

.stat-trend.up {
    color: #10b981;
}

.stat-trend.down {
    color: #ef4444;
}

/* Content Area */
.content-area {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 var(--content-padding);
    min-height: 400px;
}

/* Floating Actions */
.floating-actions {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    z-index: 100;
}

.fab-main {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: var(--primary-gradient);
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 2;
}

.fab-main:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 15px 35px rgba(99, 102, 241, 0.6);
}

.fab-main i.rotated {
    transform: rotate(45deg);
}

.fab-menu {
    position: absolute;
    bottom: 80px;
    right: 0;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    z-index: 1;
}

.fab-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    color: var(--text-primary);
    cursor: pointer;
    transition: var(--transition);
    box-shadow: var(--box-shadow);
    min-width: 180px;
    text-align: left;
    font-size: 0.9rem;
    font-weight: 500;
}

.fab-item:hover {
    transform: translateX(-8px);
    box-shadow: var(--box-shadow-lg);
    border-color: #6366f1;
    color: #6366f1;
}

.fab-item i {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary-gradient);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.9rem;
    flex-shrink: 0;
}

/* Loading Overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(248, 250, 252, 0.95);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.dark .loading-overlay {
    background: rgba(15, 23, 42, 0.95);
}

.loading-content {
    text-align: center;
}

.loading-spinner {
    width: 60px;
    height: 60px;
    border: 4px solid rgba(99, 102, 241, 0.2);
    border-top-color: #6366f1;
    border-radius: 50%;
    animation: spin 1s ease-in-out infinite;
    margin: 0 auto 1rem;
}

.loading-content p {
    color: var(--text-primary);
    font-weight: 500;
    font-size: 1.1rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Sidebar Overlay */
.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 999;
}

/* Vue Transitions */
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

.slide-down-enter-active, .slide-down-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    transform-origin: top;
}

.slide-down-enter-from, .slide-down-leave-to {
    opacity: 0;
    transform: translateY(-20px);
}

.scale-enter-active, .scale-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.scale-enter-from, .scale-leave-to {
    opacity: 0;
    transform: scale(0.8);
}

.scale-stagger-enter-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.scale-stagger-enter-from {
    opacity: 0;
    transform: scale(0.8) translateY(20px);
}

/* Responsive Design */
@media (max-width: 1200px) {
    .top-header {
        padding: 0 1.5rem;
        gap: 1.5rem;
    }

    .search-input {
        width: 280px;
    }

    .search-input:focus {
        width: 320px;
    }

    .breadcrumb-section {
        padding: 0 1.5rem;
    }

    .page-header,
    .content-area {
        padding: 0 1.5rem;
    }
}

@media (max-width: 1024px) {
    .header-center {
        max-width: 400px;
    }

    .search-input {
        width: 240px;
    }

    .search-input:focus {
        width: 280px;
    }

    .user-info {
        display: none;
    }

    .breadcrumb-section {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }

    .quick-stats {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
}

@media (max-width: 768px) {
    .app-container {
        flex-direction: column;
    }

    .sidebar {
        transform: translateX(-100%);
        z-index: 1001;
    }

    .sidebar.collapsed {
        transform: translateX(-100%);
    }

    .app-container:not(.sidebar-collapsed) .sidebar {
        transform: translateX(0);
    }

    .main-wrapper {
        margin-left: 0;
    }

    .header-left {
        display: block;
    }

    .header-center {
        max-width: 200px;
    }

    .search-input {
        width: 180px;
    }

    .search-input:focus {
        width: 220px;
    }

    .top-header {
        padding: 0 1rem;
        gap: 1rem;
    }

    .page-title {
        font-size: 2rem;
    }

    .quick-stats {
        grid-template-columns: 1fr;
    }

    .breadcrumb-section {
        padding: 0 1rem;
    }

    .page-header,
    .content-area {
        padding: 0 1rem;
    }

    .floating-actions {
        bottom: 1rem;
        right: 1rem;
    }

    .fab-main {
        width: 56px;
        height: 56px;
        font-size: 1.3rem;
    }

    .notifications-dropdown,
    .user-dropdown {
        min-width: 280px;
        right: -50px;
    }
}

@media (max-width: 480px) {
    .top-header {
        padding: 0 0.75rem;
        gap: 0.5rem;
    }

    .search-input {
        width: 140px;
    }

    .search-input:focus {
        width: 180px;
    }

    .user-section {
        padding: 0.375rem 0.75rem;
        gap: 0.5rem;
    }

    .user-avatar {
        width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }

    .action-btn {
        width: 36px;
        height: 36px;
        font-size: 0.85rem;
    }

    .breadcrumb-section {
        padding: 0 0.75rem;
    }

    .page-header,
    .content-area {
        padding: 0 0.75rem;
    }

    .fab-item {
        min-width: 150px;
        padding: 0.75rem 1rem;
    }

    .notifications-dropdown,
    .user-dropdown {
        min-width: 260px;
        right: -80px;
    }

    .sidebar {
        width: 100%;
        max-width: 320px;
    }

    .page-title {
        font-size: 1.75rem;
    }
}

/* Additional Mobile Styles */
@media (max-width: 320px) {
    .search-input {
        width: 120px;
    }

    .search-input:focus {
        width: 160px;
    }

    .page-title {
        font-size: 1.5rem;
    }

    .stat-card {
        padding: 1rem;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
    }

    .stat-content h3 {
        font-size: 1.5rem;
    }
}

/* Print Styles */
@media print {
    .sidebar,
    .top-header,
    .floating-actions,
    .notifications-dropdown,
    .user-dropdown {
        display: none !important;
    }

    .main-wrapper {
        margin-left: 0 !important;
    }

    .main-content {
        padding-top: 0 !important;
    }
}

/* High DPI Displays */
@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
    .logo-icon,
    .user-avatar,
    .user-avatar-large {
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }

    .orb {
        animation: none;
    }
}

/* Focus Styles for Accessibility */
.nav-link:focus,
.submenu-link:focus,
.action-btn:focus,
.sidebar-toggle:focus,
.mobile-menu-toggle:focus,
.search-input:focus,
.user-section:focus {
    outline: 3px solid rgba(99, 102, 241, 0.5);
    outline-offset: 2px;
}

/* Dark Mode Toggle Animation */
.dark * {
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}

/* Scrollbar Styling */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: var(--bg-tertiary);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: var(--primary-gradient);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #5b5bf1 0%, #7c3aed 100%);
}

/* Selection Styles */
::selection {
    background: rgba(99, 102, 241, 0.2);
    color: var(--text-primary);
}

::-moz-selection {
    background: rgba(99, 102, 241, 0.2);
    color: var(--text-primary);
}
</style>
