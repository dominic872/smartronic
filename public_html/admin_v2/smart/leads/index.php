<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SM Leads | Manage</title>
  <link rel="icon" type="image/png" sizes="32x32" href="/content/uploads/2025/01/cropped-Site-Icon-32x32.png">
  <link rel="apple-touch-icon" href="/content/uploads/2025/01/cropped-Site-Icon-180x180.png">
    <meta name="description" content="Modern lead management system for tracking and managing sales leads">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Stylesheet -->
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Header -->
    <header class="app-header">
        <div class="header-container">
            <div class="header-top">
                <h1 class="app-title">
                    <span class="icon">📊</span>
                    Lead Management
                </h1>
                <button class="btn btn-primary" id="addLeadBtn">
                    <span class="icon">➕</span>
                    Add New Lead
                </button>
            </div>
            
            <div class="header-controls">
                <!-- Search Bar -->
                <div class="search-container">
                    <span class="search-icon">🔍</span>
                    <input 
                        type="text" 
                        id="searchInput" 
                        class="search-input" 
                        placeholder="Search by name, phone, area, or comments..."
                        autocomplete="off"
                    >
                    <button class="clear-search" id="clearSearch" style="display: none;">✕</button>
                </div>
                
                <!-- Filters -->
                <div class="filters-container">
                    <button class="filter-toggle" id="filterToggle">
                        <span class="icon">🔽</span>
                        Filters
                        <span class="filter-count" id="filterCount" style="display: none;"></span>
                    </button>
                    
                    <div class="filters-panel" id="filtersPanel" style="display: none;">
                        <div class="filter-group">
                            <label for="statusFilter">Status</label>
                            <select id="statusFilter" class="filter-select">
                                <option value="">All Statuses</option>
                                <option value="New">New</option>
                                <option value="Contacted">Contacted</option>
                                <option value="Quoted">Quoted</option>
                                <option value="Won">Won</option>
                                <option value="Lost">Lost</option>
                                <option value="Follow-up">Follow-up</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="teamFilter">Team</label>
                            <select id="teamFilter" class="filter-select">
                                <option value="">All Teams</option>
                                <option value="Sales Team A">Sales Team A</option>
                                <option value="Sales Team B">Sales Team B</option>
                                <option value="Sales Team C">Sales Team C</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="sourceFilter">Source</label>
                            <select id="sourceFilter" class="filter-select">
                                <option value="">All Sources</option>
                                <option value="Website">Website</option>
                                <option value="Google Ads">Google Ads</option>
                                <option value="Facebook">Facebook</option>
                                <option value="Referral">Referral</option>
                                <option value="Walk-in">Walk-in</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="dateFromFilter">Date From</label>
                            <input type="date" id="dateFromFilter" class="filter-input">
                        </div>
                        
                        <div class="filter-group">
                            <label for="dateToFilter">Date To</label>
                            <input type="date" id="dateToFilter" class="filter-input">
                        </div>
                        
                        <button class="btn btn-ghost btn-sm" id="clearFilters">Clear All</button>
                    </div>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="stats-bar">
                <div class="stat-item">
                    <span class="stat-label">Total Leads:</span>
                    <span class="stat-value" id="totalLeads">0</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Showing:</span>
                    <span class="stat-value" id="showingCount">0</span>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Scroll Sentinel Top (for loading previous items) -->
        <div id="sentinelTop" class="scroll-sentinel"></div>
        
        <!-- Leads Container -->
        <div id="leadsContainer" class="leads-grid">
            <!-- Cards will be dynamically inserted here -->
        </div>
        
        <!-- Loading Indicator -->
        <div id="loadingIndicator" class="loading-indicator">
            <div class="spinner"></div>
            <p>Loading leads...</p>
        </div>
        
        <!-- Empty State -->
        <div id="emptyState" class="empty-state" style="display: none;">
            <div class="empty-icon">📭</div>
            <h3>No leads found</h3>
            <p>Try adjusting your search or filters</p>
        </div>
        
        <!-- Scroll Sentinel Bottom (for loading more items) -->
        <div id="sentinelBottom" class="scroll-sentinel"></div>
    </main>
    
    <!-- Modal Container -->
    <div id="modalContainer" class="modal-container" style="display: none;">
        <div class="modal-backdrop" id="modalBackdrop"></div>
        <div class="modal-content" id="modalContent">
            <!-- Modal content will be dynamically inserted -->
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="script.js"></script>
</body>
</html>
