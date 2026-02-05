// ========================================
// Virtual Scroll Manager
// ========================================
class VirtualScrollManager {
    constructor() {
        this.allLeads = [];
        this.visibleLeads = new Map(); // Map of id -> lead data
        this.offset = 0;
        this.limit = 50;
        this.total = 0;
        this.isLoading = false;
        this.hasMore = true;
        this.observer = null;
        this.filters = {
            search: '',
            status: '',
            team: '',
            source: '',
            dateFrom: '',
            dateTo: ''
        };
    }

    async init() {
        this.setupIntersectionObserver();
        await this.loadInitialData();
    }

    setupIntersectionObserver() {
        const options = {
            root: null,
            rootMargin: '200px',
            threshold: 0
        };

        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !this.isLoading) {
                    if (entry.target.id === 'sentinelBottom' && this.hasMore) {
                        this.loadMore();
                    }
                }
            });
        }, options);

        const sentinelBottom = document.getElementById('sentinelBottom');
        if (sentinelBottom) {
            this.observer.observe(sentinelBottom);
        }
    }

    async loadInitialData() {
        this.showLoading();
        this.offset = 0;
        this.visibleLeads.clear();

        try {
            const data = await this.fetchLeads(0, this.limit);
            if (data.success) {
                this.total = data.total;
                this.hasMore = data.hasMore;

                data.data.forEach(lead => {
                    this.visibleLeads.set(lead.id, lead);
                });

                this.renderAllCards();
                this.updateStats();
                this.hideLoading();

                if (data.data.length === 0) {
                    this.showEmptyState();
                }
            }
        } catch (error) {
            console.error('Error loading initial data:', error);
            this.hideLoading();
            this.showError('Failed to load leads');
        }
    }

    async loadMore() {
        if (this.isLoading || !this.hasMore) return;

        this.isLoading = true;
        const nextOffset = this.offset + this.limit;

        try {
            const data = await this.fetchLeads(nextOffset, this.limit);
            if (data.success && data.data.length > 0) {
                this.offset = nextOffset;
                this.hasMore = data.hasMore;

                // Add new leads
                data.data.forEach(lead => {
                    this.visibleLeads.set(lead.id, lead);
                });

                // Cleanup old cards if we have more than 50
                this.cleanupOffscreenCards();

                // Render new cards
                this.renderNewCards(data.data);
                this.updateStats();
            }
        } catch (error) {
            console.error('Error loading more data:', error);
        } finally {
            this.isLoading = false;
        }
    }

    async fetchLeads(offset, limit) {
        const params = new URLSearchParams({
            offset: offset,
            limit: limit,
            ...this.filters
        });

        // Remove empty filters
        for (let [key, value] of params.entries()) {
            if (!value) params.delete(key);
        }

        const response = await fetch(`api.php?${params}`);
        return await response.json();
    }

    cleanupOffscreenCards() {
        if (this.visibleLeads.size <= this.limit) return;

        const container = document.getElementById('leadsContainer');
        const cards = container.querySelectorAll('.lead-card');

        // Remove cards that are far off-screen
        cards.forEach((card, index) => {
            const rect = card.getBoundingClientRect();
            const isOffscreen = rect.bottom < -1000 || rect.top > window.innerHeight + 1000;

            if (isOffscreen && this.visibleLeads.size > 25) {
                const leadId = parseInt(card.dataset.leadId);
                this.visibleLeads.delete(leadId);
                card.remove();
            }
        });
    }

    renderAllCards() {
        const container = document.getElementById('leadsContainer');
        container.innerHTML = '';

        this.visibleLeads.forEach(lead => {
            container.appendChild(this.createCard(lead));
        });
    }

    renderNewCards(leads) {
        const container = document.getElementById('leadsContainer');
        leads.forEach(lead => {
            container.appendChild(this.createCard(lead));
        });
    }

    createCard(lead) {
        const card = document.createElement('div');
        card.className = 'lead-card';
        card.dataset.leadId = lead.id;

        const statusClass = lead.status.toLowerCase().replace('-', '-');

        card.innerHTML = `
            <div class="card-header">
                <div>
                    <h3 class="lead-name">${this.escapeHtml(lead.name)}</h3>
                    <div class="lead-id">${lead.display_id}</div>
                </div>
                <span class="status-badge status-${statusClass}">${lead.status}</span>
            </div>
            
            <div class="card-body">
                <div class="info-row">
                    <span class="icon">📞</span>
                    <a href="tel:${lead.phone}">${this.formatPhone(lead.phone)}</a>
                </div>
                
                ${lead.area ? `
                <div class="info-row">
                    <span class="icon">📍</span>
                    <span>${this.escapeHtml(lead.area)}</span>
                </div>
                ` : ''}
                
                <div class="info-row">
                    <span class="icon">📹</span>
                    <span>${lead.cameras} Camera${lead.cameras !== 1 ? 's' : ''} • ${lead.resolution || 'N/A'}</span>
                </div>
                
                ${lead.budget ? `
                <div class="info-row">
                    <span class="icon">💰</span>
                    <span>₹${this.formatNumber(lead.budget)}</span>
                </div>
                ` : ''}
                
                ${lead.source ? `
                <div class="info-row">
                    <span class="icon">🏷️</span>
                    <span>${this.escapeHtml(lead.source)}</span>
                </div>
                ` : ''}
                
                ${lead.assign ? `
                <div class="info-row">
                    <span class="icon">👤</span>
                    <span class="label">${this.escapeHtml(lead.assign)}</span>
                </div>
                ` : ''}
            </div>
            
            <div class="card-footer">
                <button class="btn btn-primary" onclick="app.viewDetails(${lead.id})">
                    View Details
                </button>
                <button class="btn btn-secondary" onclick="app.sendQuote(${lead.id})">
                    Send Quote
                </button>
                <button class="btn btn-ghost" onclick="app.callLead('${lead.phone}', '${this.escapeHtml(lead.name)}')">
                    📞 Call
                </button>
            </div>
        `;

        return card;
    }

    updateStats() {
        document.getElementById('totalLeads').textContent = this.total;
        document.getElementById('showingCount').textContent = this.visibleLeads.size;
    }

    showLoading() {
        document.getElementById('loadingIndicator').style.display = 'block';
        document.getElementById('emptyState').style.display = 'none';
    }

    hideLoading() {
        document.getElementById('loadingIndicator').style.display = 'none';
    }

    showEmptyState() {
        document.getElementById('emptyState').style.display = 'block';
        document.getElementById('loadingIndicator').style.display = 'none';
    }

    showError(message) {
        alert(message); // TODO: Replace with better error UI
    }

    // Utility functions
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatPhone(phone) {
        // Format: +91 98765 43210
        if (phone.startsWith('+91')) {
            return phone.replace(/(\+91)(\d{5})(\d{5})/, '$1 $2 $3');
        }
        return phone;
    }

    formatNumber(num) {
        return new Intl.NumberFormat('en-IN').format(num);
    }
}

// ========================================
// Filter Manager
// ========================================
class FilterManager {
    constructor(virtualScroll) {
        this.virtualScroll = virtualScroll;
        this.searchDebounce = null;
        this.activeFilters = 0;
    }

    init() {
        this.setupSearchListener();
        this.setupFilterListeners();
        this.setupFilterToggle();
    }

    setupSearchListener() {
        const searchInput = document.getElementById('searchInput');
        const clearSearch = document.getElementById('clearSearch');

        searchInput.addEventListener('input', (e) => {
            const value = e.target.value.trim();

            // Show/hide clear button
            clearSearch.style.display = value ? 'flex' : 'none';

            // Debounce search
            clearTimeout(this.searchDebounce);
            this.searchDebounce = setTimeout(() => {
                this.virtualScroll.filters.search = value;
                this.virtualScroll.loadInitialData();
            }, 300);
        });

        clearSearch.addEventListener('click', () => {
            searchInput.value = '';
            clearSearch.style.display = 'none';
            this.virtualScroll.filters.search = '';
            this.virtualScroll.loadInitialData();
        });
    }

    setupFilterListeners() {
        const filterIds = ['statusFilter', 'teamFilter', 'sourceFilter', 'dateFromFilter', 'dateToFilter'];
        const filterKeys = ['status', 'team', 'source', 'dateFrom', 'dateTo'];

        filterIds.forEach((id, index) => {
            const element = document.getElementById(id);
            element.addEventListener('change', () => {
                this.virtualScroll.filters[filterKeys[index]] = element.value;
                this.updateFilterCount();
                this.virtualScroll.loadInitialData();
            });
        });

        // Clear filters button
        document.getElementById('clearFilters').addEventListener('click', () => {
            this.clearAllFilters();
        });
    }

    setupFilterToggle() {
        const toggle = document.getElementById('filterToggle');
        const panel = document.getElementById('filtersPanel');

        toggle.addEventListener('click', () => {
            const isVisible = panel.style.display === 'block';
            panel.style.display = isVisible ? 'none' : 'block';
            toggle.classList.toggle('active', !isVisible);
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!toggle.contains(e.target) && !panel.contains(e.target)) {
                panel.style.display = 'none';
                toggle.classList.remove('active');
            }
        });
    }

    updateFilterCount() {
        const filters = this.virtualScroll.filters;
        let count = 0;

        Object.entries(filters).forEach(([key, value]) => {
            if (value && key !== 'search') count++;
        });

        this.activeFilters = count;
        const countBadge = document.getElementById('filterCount');

        if (count > 0) {
            countBadge.textContent = count;
            countBadge.style.display = 'inline-block';
        } else {
            countBadge.style.display = 'none';
        }
    }

    clearAllFilters() {
        document.getElementById('statusFilter').value = '';
        document.getElementById('teamFilter').value = '';
        document.getElementById('sourceFilter').value = '';
        document.getElementById('dateFromFilter').value = '';
        document.getElementById('dateToFilter').value = '';

        this.virtualScroll.filters.status = '';
        this.virtualScroll.filters.team = '';
        this.virtualScroll.filters.source = '';
        this.virtualScroll.filters.dateFrom = '';
        this.virtualScroll.filters.dateTo = '';

        this.updateFilterCount();
        this.virtualScroll.loadInitialData();
    }
}

// ========================================
// Modal Manager
// ========================================
class ModalManager {
    constructor() {
        this.container = document.getElementById('modalContainer');
        this.backdrop = document.getElementById('modalBackdrop');
        this.content = document.getElementById('modalContent');
    }

    open(title, bodyHtml) {
        this.content.innerHTML = `
            <div style="padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h2 style="font-size: 1.5rem; font-weight: 600;">${title}</h2>
                    <button onclick="app.modal.close()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; padding: 8px;">✕</button>
                </div>
                <div>${bodyHtml}</div>
            </div>
        `;

        this.container.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        // Close on backdrop click
        this.backdrop.onclick = () => this.close();

        // Close on ESC key
        this.escHandler = (e) => {
            if (e.key === 'Escape') this.close();
        };
        document.addEventListener('keydown', this.escHandler);
    }

    close() {
        this.container.style.display = 'none';
        document.body.style.overflow = '';
        document.removeEventListener('keydown', this.escHandler);
    }
}

// ========================================
// Main Application
// ========================================
class LeadManagementApp {
    constructor() {
        this.virtualScroll = new VirtualScrollManager();
        this.filterManager = new FilterManager(this.virtualScroll);
        this.modal = new ModalManager();
    }

    async init() {
        await this.virtualScroll.init();
        this.filterManager.init();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Add new lead button
        document.getElementById('addLeadBtn').addEventListener('click', () => {
            this.showAddLeadForm();
        });
    }

    viewDetails(leadId) {
        const lead = this.virtualScroll.visibleLeads.get(leadId);
        if (!lead) return;

        const bodyHtml = `
            <div style="display: grid; gap: 16px;">
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 4px;">Name</label>
                    <input type="text" id="editName" value="${this.escapeHtml(lead.name)}" style="width: 100%; padding: 8px; border: 1px solid #D1D5DB; border-radius: 8px;">
                </div>
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 4px;">Phone</label>
                    <input type="text" id="editPhone" value="${lead.phone}" style="width: 100%; padding: 8px; border: 1px solid #D1D5DB; border-radius: 8px;">
                </div>
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 4px;">Area</label>
                    <input type="text" id="editArea" value="${this.escapeHtml(lead.area || '')}" style="width: 100%; padding: 8px; border: 1px solid #D1D5DB; border-radius: 8px;">
                </div>
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 4px;">Status</label>
                    <select id="editStatus" style="width: 100%; padding: 8px; border: 1px solid #D1D5DB; border-radius: 8px;">
                        <option value="New" ${lead.status === 'New' ? 'selected' : ''}>New</option>
                        <option value="Contacted" ${lead.status === 'Contacted' ? 'selected' : ''}>Contacted</option>
                        <option value="Quoted" ${lead.status === 'Quoted' ? 'selected' : ''}>Quoted</option>
                        <option value="Won" ${lead.status === 'Won' ? 'selected' : ''}>Won</option>
                        <option value="Lost" ${lead.status === 'Lost' ? 'selected' : ''}>Lost</option>
                        <option value="Follow-up" ${lead.status === 'Follow-up' ? 'selected' : ''}>Follow-up</option>
                    </select>
                </div>
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 4px;">Cameras</label>
                    <input type="number" id="editCameras" value="${lead.cameras}" style="width: 100%; padding: 8px; border: 1px solid #D1D5DB; border-radius: 8px;">
                </div>
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 4px;">Resolution</label>
                    <input type="text" id="editResolution" value="${this.escapeHtml(lead.resolution || '')}" style="width: 100%; padding: 8px; border: 1px solid #D1D5DB; border-radius: 8px;">
                </div>
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 4px;">Budget</label>
                    <input type="number" id="editBudget" value="${lead.budget || ''}" style="width: 100%; padding: 8px; border: 1px solid #D1D5DB; border-radius: 8px;">
                </div>
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 4px;">Comments</label>
                    <textarea id="editComments" rows="3" style="width: 100%; padding: 8px; border: 1px solid #D1D5DB; border-radius: 8px;">${this.escapeHtml(lead.comments || '')}</textarea>
                </div>
                <button onclick="app.saveLead(${leadId})" class="btn btn-primary" style="width: 100%;">Save Changes</button>
            </div>
        `;

        this.modal.open(`Edit Lead - ${lead.display_id}`, bodyHtml);
    }

    async saveLead(leadId) {
        const data = {
            id: leadId,
            Name: document.getElementById('editName').value,
            whatsapp_number: document.getElementById('editPhone').value,
            Area: document.getElementById('editArea').value,
            status: document.getElementById('editStatus').value,
            cameras: parseInt(document.getElementById('editCameras').value),
            resolution: document.getElementById('editResolution').value,
            budget: parseFloat(document.getElementById('editBudget').value) || null,
            comments: document.getElementById('editComments').value
        };

        try {
            const response = await fetch('update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                this.modal.close();
                this.virtualScroll.loadInitialData();
                alert('Lead updated successfully!');
            } else {
                alert('Error: ' + result.error);
            }
        } catch (error) {
            console.error('Error saving lead:', error);
            alert('Failed to save lead');
        }
    }

    sendQuote(leadId) {
        const lead = this.virtualScroll.visibleLeads.get(leadId);
        if (!lead) return;

        // TODO: Integrate with existing quotation system
        alert(`Send quotation to ${lead.name}\nPhone: ${lead.phone}\n\nThis will be integrated with your existing quotation system.`);
    }

    callLead(phone, name) {
        if (window.confirm(`Call ${name} at ${phone}?`)) {
            window.location.href = `tel:${phone}`;
        }
    }

    showAddLeadForm() {
        const bodyHtml = `
            <div style="display: grid; gap: 16px;">
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 4px;">Name *</label>
                    <input type="text" id="newName" style="width: 100%; padding: 8px; border: 1px solid #D1D5DB; border-radius: 8px;" required>
                </div>
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 4px;">Phone *</label>
                    <input type="text" id="newPhone" style="width: 100%; padding: 8px; border: 1px solid #D1D5DB; border-radius: 8px;" required>
                </div>
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 4px;">Area</label>
                    <input type="text" id="newArea" style="width: 100%; padding: 8px; border: 1px solid #D1D5DB; border-radius: 8px;">
                </div>
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 4px;">Team</label>
                    <select id="newTeam" style="width: 100%; padding: 8px; border: 1px solid #D1D5DB; border-radius: 8px;">
                        <option value="">Select Team</option>
                        <option value="Sales Team A">Sales Team A</option>
                        <option value="Sales Team B">Sales Team B</option>
                        <option value="Sales Team C">Sales Team C</option>
                    </select>
                </div>
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 4px;">Cameras</label>
                    <input type="number" id="newCameras" value="0" style="width: 100%; padding: 8px; border: 1px solid #D1D5DB; border-radius: 8px;">
                </div>
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 4px;">Resolution</label>
                    <select id="newResolution" style="width: 100%; padding: 8px; border: 1px solid #D1D5DB; border-radius: 8px;">
                        <option value="">Select Resolution</option>
                        <option value="2MP">2MP</option>
                        <option value="4MP">4MP</option>
                        <option value="5MP">5MP</option>
                        <option value="8MP">8MP</option>
                    </select>
                </div>
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 4px;">Source</label>
                    <select id="newSource" style="width: 100%; padding: 8px; border: 1px solid #D1D5DB; border-radius: 8px;">
                        <option value="">Select Source</option>
                        <option value="Website">Website</option>
                        <option value="Google Ads">Google Ads</option>
                        <option value="Facebook">Facebook</option>
                        <option value="Referral">Referral</option>
                        <option value="Walk-in">Walk-in</option>
                    </select>
                </div>
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 4px;">Budget</label>
                    <input type="number" id="newBudget" style="width: 100%; padding: 8px; border: 1px solid #D1D5DB; border-radius: 8px;">
                </div>
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 4px;">Comments</label>
                    <textarea id="newComments" rows="3" style="width: 100%; padding: 8px; border: 1px solid #D1D5DB; border-radius: 8px;"></textarea>
                </div>
                <button onclick="app.createNewLead()" class="btn btn-primary" style="width: 100%;">Create Lead</button>
            </div>
        `;

        this.modal.open('Add New Lead', bodyHtml);
    }

    async createNewLead() {
        const data = {
            Name: document.getElementById('newName').value,
            whatsapp_number: document.getElementById('newPhone').value,
            Area: document.getElementById('newArea').value,
            Assign: document.getElementById('newTeam').value,
            cameras: parseInt(document.getElementById('newCameras').value) || 0,
            resolution: document.getElementById('newResolution').value,
            source: document.getElementById('newSource').value,
            budget: parseFloat(document.getElementById('newBudget').value) || null,
            comments: document.getElementById('newComments').value
        };

        if (!data.Name || !data.whatsapp_number) {
            alert('Name and Phone are required!');
            return;
        }

        try {
            const response = await fetch('create.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                this.modal.close();
                this.virtualScroll.loadInitialData();
                alert('Lead created successfully!');
            } else {
                alert('Error: ' + result.error);
            }
        } catch (error) {
            console.error('Error creating lead:', error);
            alert('Failed to create lead');
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// ========================================
// Initialize Application
// ========================================
const app = new LeadManagementApp();

// Wait for DOM to be ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => app.init());
} else {
    app.init();
}
