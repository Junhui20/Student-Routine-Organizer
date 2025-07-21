<?php
/**
 * Search and Filter Component for Diary Entries
 * Provides comprehensive filtering capabilities
 */
?>

<div class="search-filter-container">
    <!-- Search Bar -->
    <div class="search-section">
        <div class="search-input-group">
            <div class="search-icon">
                <i class="fas fa-search"></i>
            </div>
            <input type="text" id="search-input" placeholder="Search your diary entries..." class="search-input">
            <button type="button" id="clear-search" class="clear-search-btn" style="display: none;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="search-suggestions" id="search-suggestions"></div>
    </div>

    <!-- Filter Toggle -->
    <div class="filter-toggle-section">
        <button type="button" id="filter-toggle" class="filter-toggle-btn">
            <i class="fas fa-filter"></i>
            <span>Advanced Filters</span>
            <i class="fas fa-chevron-down toggle-icon"></i>
        </button>
        <div class="active-filters" id="active-filters"></div>
    </div>

    <!-- Advanced Filters Panel -->
    <div class="filters-panel" id="filters-panel">
        <div class="filters-grid">
            <!-- Date Range Filter -->
            <div class="filter-group">
                <label class="filter-label">
                    <i class="fas fa-calendar-alt"></i>
                    Date Range
                </label>
                <div class="date-range-inputs">
                    <input type="date" id="date-from" class="filter-input" placeholder="From">
                    <span class="date-separator">to</span>
                    <input type="date" id="date-to" class="filter-input" placeholder="To">
                </div>
                <div class="date-presets">
                    <button type="button" class="preset-btn" data-preset="today">Today</button>
                    <button type="button" class="preset-btn" data-preset="week">This Week</button>
                    <button type="button" class="preset-btn" data-preset="month">This Month</button>
                    <button type="button" class="preset-btn" data-preset="year">This Year</button>
                </div>
            </div>

            <!-- Mood Filter -->
            <div class="filter-group">
                <label class="filter-label">
                    <i class="fas fa-heart"></i>
                    Mood
                </label>
                <div class="mood-filter-grid">
                    <label class="mood-filter-item">
                        <input type="checkbox" value="Happy" class="mood-filter">
                        <span class="mood-label">😊 Happy</span>
                    </label>
                    <label class="mood-filter-item">
                        <input type="checkbox" value="Sad" class="mood-filter">
                        <span class="mood-label">😢 Sad</span>
                    </label>
                    <label class="mood-filter-item">
                        <input type="checkbox" value="Excited" class="mood-filter">
                        <span class="mood-label">🎉 Excited</span>
                    </label>
                    <label class="mood-filter-item">
                        <input type="checkbox" value="Stressed" class="mood-filter">
                        <span class="mood-label">😰 Stressed</span>
                    </label>
                    <label class="mood-filter-item">
                        <input type="checkbox" value="Calm" class="mood-filter">
                        <span class="mood-label">😌 Calm</span>
                    </label>
                    <label class="mood-filter-item">
                        <input type="checkbox" value="Angry" class="mood-filter">
                        <span class="mood-label">😠 Angry</span>
                    </label>
                    <label class="mood-filter-item">
                        <input type="checkbox" value="Grateful" class="mood-filter">
                        <span class="mood-label">🙏 Grateful</span>
                    </label>
                    <label class="mood-filter-item">
                        <input type="checkbox" value="Tired" class="mood-filter">
                        <span class="mood-label">😴 Tired</span>
                    </label>
                </div>
            </div>

            <!-- Content Type Filter -->
            <div class="filter-group">
                <label class="filter-label">
                    <i class="fas fa-file-alt"></i>
                    Content Type
                </label>
                <div class="content-type-filters">
                    <label class="filter-checkbox">
                        <input type="checkbox" id="long-entries" class="content-filter">
                        <span class="checkmark"></span>
                        <span>Long Entries (500+ words)</span>
                    </label>
                    <label class="filter-checkbox">
                        <input type="checkbox" id="short-entries" class="content-filter">
                        <span class="checkmark"></span>
                        <span>Short Entries (< 100 words)</span>
                    </label>
                </div>
            </div>

            <!-- Sort Options -->
            <div class="filter-group">
                <label class="filter-label">
                    <i class="fas fa-sort"></i>
                    Sort By
                </label>
                <select id="sort-by" class="filter-select">
                    <option value="date_desc">Newest First</option>
                    <option value="date_asc">Oldest First</option>
                    <option value="title_asc">Title A-Z</option>
                    <option value="title_desc">Title Z-A</option>
                    <option value="mood">Mood</option>
                    <option value="length_desc">Longest First</option>
                    <option value="length_asc">Shortest First</option>
                </select>
            </div>
        </div>

        <!-- Filter Actions -->
        <div class="filter-actions">
            <button type="button" id="clear-all-filters" class="btn-secondary">
                <i class="fas fa-times"></i>
                Clear All Filters
            </button>
            <button type="button" id="apply-filters" class="btn-primary">
                <i class="fas fa-check"></i>
                Apply Filters
            </button>
        </div>
    </div>

    <!-- Search Results Summary -->
    <div class="search-results-summary" id="search-results-summary" style="display: none;">
        <div class="results-info">
            <span id="results-count">0</span> entries found
            <span id="search-time"></span>
        </div>
        <div class="results-actions">
            <button type="button" id="export-results" class="btn-small btn-secondary">
                <i class="fas fa-download"></i>
                Export Results
            </button>
        </div>
    </div>
</div>

<style>
.search-filter-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    overflow: hidden;
}

/* Search Section */
.search-section {
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
    position: relative;
}

.search-input-group {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 15px;
    color: #6c757d;
    z-index: 2;
}

.search-input {
    width: 100%;
    padding: 12px 45px 12px 45px;
    border: 2px solid #e1e5e9;
    border-radius: 25px;
    font-size: 16px;
    transition: border-color 0.3s, box-shadow 0.3s;
    background: #f8f9fa;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    background: white;
}

.clear-search-btn {
    position: absolute;
    right: 12px;
    background: #6c757d;
    color: white;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
}

.clear-search-btn:hover {
    background: #495057;
}

.search-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #dee2e6;
    border-top: none;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    z-index: 1000;
    max-height: 200px;
    overflow-y: auto;
    display: none;
}

.suggestion-item {
    padding: 10px 15px;
    cursor: pointer;
    border-bottom: 1px solid #f8f9fa;
    transition: background-color 0.2s;
}

.suggestion-item:hover {
    background-color: #f8f9fa;
}

.suggestion-item:last-child {
    border-bottom: none;
}

/* Filter Toggle */
.filter-toggle-section {
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.filter-toggle-btn {
    background: none;
    border: none;
    color: #495057;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: color 0.2s;
}

.filter-toggle-btn:hover {
    color: #667eea;
}

.filter-toggle-btn.active {
    color: #667eea;
}

.toggle-icon {
    transition: transform 0.3s;
}

.filter-toggle-btn.active .toggle-icon {
    transform: rotate(180deg);
}

.active-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.active-filter-tag {
    background: #667eea;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.active-filter-tag .remove-filter {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 10px;
}

/* Filters Panel */
.filters-panel {
    padding: 1.5rem;
    background: white;
    display: none;
    border-bottom: 1px solid #e9ecef;
}

.filters-panel.show {
    display: block;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.filter-group {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 10px;
    border: 1px solid #e9ecef;
}

.filter-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 1rem;
    font-size: 14px;
}

/* Date Range */
.date-range-inputs {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 1rem;
}

.date-separator {
    color: #6c757d;
    font-size: 14px;
}

.filter-input {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 14px;
}

.filter-input:focus {
    outline: none;
    border-color: #667eea;
}

.date-presets {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.preset-btn {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 15px;
    padding: 4px 12px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.preset-btn:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.preset-btn.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

/* Mood Filter */
.mood-filter-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
}

.mood-filter-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.mood-filter-item:hover {
    background: rgba(102, 126, 234, 0.1);
}

.mood-filter {
    margin: 0;
}

.mood-label {
    font-size: 14px;
    user-select: none;
}

/* Content Type Filters */
.content-type-filters {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.filter-checkbox {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    position: relative;
    padding-left: 25px;
}

.filter-checkbox input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}

.checkmark {
    position: absolute;
    left: 0;
    height: 18px;
    width: 18px;
    background-color: white;
    border: 2px solid #dee2e6;
    border-radius: 4px;
    transition: all 0.2s;
}

.filter-checkbox:hover .checkmark {
    border-color: #667eea;
}

.filter-checkbox input:checked ~ .checkmark {
    background-color: #667eea;
    border-color: #667eea;
}

.checkmark:after {
    content: "";
    position: absolute;
    display: none;
    left: 5px;
    top: 2px;
    width: 4px;
    height: 8px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.filter-checkbox input:checked ~ .checkmark:after {
    display: block;
}

/* Sort Select */
.filter-select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 14px;
    background: white;
}

.filter-select:focus {
    outline: none;
    border-color: #667eea;
}

/* Filter Actions */
.filter-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
}

.btn-primary, .btn-secondary, .btn-small {
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
}

.btn-primary {
    background: #667eea;
    color: white;
    border: 1px solid #667eea;
}

.btn-primary:hover {
    background: #5a6fd8;
    transform: translateY(-1px);
}

.btn-secondary {
    background: white;
    color: #6c757d;
    border: 1px solid #dee2e6;
}

.btn-secondary:hover {
    background: #f8f9fa;
    transform: translateY(-1px);
}

.btn-small {
    padding: 6px 12px;
    font-size: 12px;
}

/* Search Results Summary */
.search-results-summary {
    padding: 1rem 1.5rem;
    background: #e3f2fd;
    border-top: 1px solid #bbdefb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.results-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: #1976d2;
    font-weight: 500;
}

.results-actions {
    display: flex;
    gap: 0.5rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .search-filter-container {
        margin-left: -1rem;
        margin-right: -1rem;
        border-radius: 0;
    }
    
    .filters-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .filter-group {
        padding: 1rem;
    }
    
    .mood-filter-grid {
        grid-template-columns: 1fr;
    }
    
    .date-range-inputs {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-actions {
        flex-direction: column;
    }
    
    .search-results-summary {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
}
</style>

<script>
class DiarySearchFilter {
    constructor() {
        this.searchInput = document.getElementById('search-input');
        this.clearSearchBtn = document.getElementById('clear-search');
        this.filterToggle = document.getElementById('filter-toggle');
        this.filtersPanel = document.getElementById('filters-panel');
        this.activeFiltersContainer = document.getElementById('active-filters');
        this.resultsContainer = document.getElementById('search-results-summary');
        this.resultsCount = document.getElementById('results-count');
        
        this.currentFilters = {
            search: '',
            dateFrom: '',
            dateTo: '',
            moods: [],
            contentTypes: [],
            sortBy: 'date_desc'
        };
        
        this.init();
    }
    
    init() {
        this.initSearchInput();
        this.initFilterToggle();
        this.initDatePresets();
        this.initMoodFilters();
        this.initContentFilters();
        this.initSortFilter();
        this.initFilterActions();
        
        // Load initial results
        this.applyFilters();
    }
    
    initSearchInput() {
        let searchTimeout;
        
        this.searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            
            // Show/hide clear button
            this.clearSearchBtn.style.display = query ? 'flex' : 'none';
            
            // Debounced search
            searchTimeout = setTimeout(() => {
                this.currentFilters.search = query;
                this.applyFilters();
            }, 300);
        });
        
        this.clearSearchBtn.addEventListener('click', () => {
            this.searchInput.value = '';
            this.clearSearchBtn.style.display = 'none';
            this.currentFilters.search = '';
            this.applyFilters();
        });
    }
    
    initFilterToggle() {
        this.filterToggle.addEventListener('click', () => {
            this.filterToggle.classList.toggle('active');
            this.filtersPanel.classList.toggle('show');
        });
    }
    
    initDatePresets() {
        const presetBtns = document.querySelectorAll('.preset-btn');
        const dateFrom = document.getElementById('date-from');
        const dateTo = document.getElementById('date-to');
        
        presetBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Remove active class from all presets
                presetBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                const preset = btn.dataset.preset;
                const today = new Date();
                let startDate, endDate = today;
                
                switch (preset) {
                    case 'today':
                        startDate = today;
                        break;
                    case 'week':
                        startDate = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
                        break;
                    case 'month':
                        startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                        break;
                    case 'year':
                        startDate = new Date(today.getFullYear(), 0, 1);
                        break;
                }
                
                dateFrom.value = this.formatDate(startDate);
                dateTo.value = this.formatDate(endDate);
                
                this.currentFilters.dateFrom = dateFrom.value;
                this.currentFilters.dateTo = dateTo.value;
                this.updateActiveFilters();
            });
        });
        
        // Manual date input
        dateFrom.addEventListener('change', () => {
            this.currentFilters.dateFrom = dateFrom.value;
            this.clearPresetSelection();
            this.updateActiveFilters();
        });
        
        dateTo.addEventListener('change', () => {
            this.currentFilters.dateTo = dateTo.value;
            this.clearPresetSelection();
            this.updateActiveFilters();
        });
    }
    
    initMoodFilters() {
        const moodFilters = document.querySelectorAll('.mood-filter');
        
        moodFilters.forEach(filter => {
            filter.addEventListener('change', () => {
                if (filter.checked) {
                    this.currentFilters.moods.push(filter.value);
                } else {
                    this.currentFilters.moods = this.currentFilters.moods.filter(m => m !== filter.value);
                }
                this.updateActiveFilters();
            });
        });
    }
    
    initContentFilters() {
        const contentFilters = document.querySelectorAll('.content-filter');
        
        contentFilters.forEach(filter => {
            filter.addEventListener('change', () => {
                if (filter.checked) {
                    this.currentFilters.contentTypes.push(filter.id);
                } else {
                    this.currentFilters.contentTypes = this.currentFilters.contentTypes.filter(c => c !== filter.id);
                }
                this.updateActiveFilters();
            });
        });
    }
    
    initSortFilter() {
        const sortSelect = document.getElementById('sort-by');
        
        sortSelect.addEventListener('change', () => {
            this.currentFilters.sortBy = sortSelect.value;
            this.applyFilters();
        });
    }
    
    initFilterActions() {
        document.getElementById('clear-all-filters').addEventListener('click', () => {
            this.clearAllFilters();
        });
        
        document.getElementById('apply-filters').addEventListener('click', () => {
            this.applyFilters();
        });
    }
    
    formatDate(date) {
        return date.toISOString().split('T')[0];
    }
    
    clearPresetSelection() {
        document.querySelectorAll('.preset-btn').forEach(btn => {
            btn.classList.remove('active');
        });
    }
    
    updateActiveFilters() {
        const activeFilters = [];
        
        // Search filter
        if (this.currentFilters.search) {
            activeFilters.push({
                type: 'search',
                label: `Search: "${this.currentFilters.search}"`,
                value: this.currentFilters.search
            });
        }
        
        // Date filters
        if (this.currentFilters.dateFrom || this.currentFilters.dateTo) {
            const fromDate = this.currentFilters.dateFrom || 'Beginning';
            const toDate = this.currentFilters.dateTo || 'Now';
            activeFilters.push({
                type: 'date',
                label: `Date: ${fromDate} to ${toDate}`,
                value: 'date'
            });
        }
        
        // Mood filters
        this.currentFilters.moods.forEach(mood => {
            activeFilters.push({
                type: 'mood',
                label: `Mood: ${mood}`,
                value: mood
            });
        });
        
        // Content type filters
        this.currentFilters.contentTypes.forEach(type => {
            const labels = {
                'long-entries': 'Long Entries',
                'short-entries': 'Short Entries'
            };
            activeFilters.push({
                type: 'content',
                label: labels[type],
                value: type
            });
        });
        
        // Render active filters
        this.activeFiltersContainer.innerHTML = '';
        activeFilters.forEach(filter => {
            const tag = document.createElement('div');
            tag.className = 'active-filter-tag';
            tag.innerHTML = `
                ${filter.label}
                <button class="remove-filter" onclick="diarySearchFilter.removeFilter('${filter.type}', '${filter.value}')">
                    <i class="fas fa-times"></i>
                </button>
            `;
            this.activeFiltersContainer.appendChild(tag);
        });
    }
    
    removeFilter(type, value) {
        switch (type) {
            case 'search':
                this.searchInput.value = '';
                this.clearSearchBtn.style.display = 'none';
                this.currentFilters.search = '';
                break;
            case 'date':
                document.getElementById('date-from').value = '';
                document.getElementById('date-to').value = '';
                this.currentFilters.dateFrom = '';
                this.currentFilters.dateTo = '';
                this.clearPresetSelection();
                break;
            case 'mood':
                this.currentFilters.moods = this.currentFilters.moods.filter(m => m !== value);
                document.querySelector(`input[value="${value}"].mood-filter`).checked = false;
                break;
            case 'content':
                this.currentFilters.contentTypes = this.currentFilters.contentTypes.filter(c => c !== value);
                document.getElementById(value).checked = false;
                break;
        }
        
        this.updateActiveFilters();
        this.applyFilters();
    }
    
    clearAllFilters() {
        // Clear search
        this.searchInput.value = '';
        this.clearSearchBtn.style.display = 'none';
        
        // Clear dates
        document.getElementById('date-from').value = '';
        document.getElementById('date-to').value = '';
        this.clearPresetSelection();
        
        // Clear moods
        document.querySelectorAll('.mood-filter').forEach(filter => {
            filter.checked = false;
        });
        
        // Clear content types
        document.querySelectorAll('.content-filter').forEach(filter => {
            filter.checked = false;
        });
        
        // Reset sort
        document.getElementById('sort-by').value = 'date_desc';
        
        // Reset filters object
        this.currentFilters = {
            search: '',
            dateFrom: '',
            dateTo: '',
            moods: [],
            contentTypes: [],
            sortBy: 'date_desc'
        };
        
        this.updateActiveFilters();
        this.applyFilters();
    }
    
    applyFilters() {
        const startTime = performance.now();
        
        // Send AJAX request to filter entries
        const formData = new FormData();
        formData.append('action', 'filter_entries');
        formData.append('filters', JSON.stringify(this.currentFilters));
        
        fetch('search_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const endTime = performance.now();
            const searchTime = Math.round(endTime - startTime);
            
            this.updateResults(data, searchTime);
        })
        .catch(error => {
            console.error('Search error:', error);
        });
    }
    
    updateResults(data, searchTime) {
        // Update results count
        this.resultsCount.textContent = data.count;
        document.getElementById('search-time').textContent = `(${searchTime}ms)`;
        
        // Show/hide results summary
        if (this.hasActiveFilters()) {
            this.resultsContainer.style.display = 'flex';
        } else {
            this.resultsContainer.style.display = 'none';
        }
        
        // Update entries display
        this.updateEntriesDisplay(data.entries);
    }
    
    updateEntriesDisplay(entries) {
        const entriesContainer = document.querySelector('.entries-container') || document.querySelector('.container');
        
        // Find existing entries and replace them
        const existingEntries = entriesContainer.querySelectorAll('.entry-card');
        existingEntries.forEach(entry => entry.remove());
        
        if (entries.length === 0) {
            const noResults = document.createElement('div');
            noResults.className = 'no-results';
            noResults.innerHTML = `
                <div class="text-center" style="padding: 3rem;">
                    <div class="feature-icon" style="margin: 2rem 0;">
                        <i class="fas fa-search" style="font-size: 4rem; color: #ccc;"></i>
                    </div>
                    <h3 style="color: #999;">No entries found</h3>
                    <p style="color: #999; margin-bottom: 2rem;">Try adjusting your search terms or filters.</p>
                    <button onclick="diarySearchFilter.clearAllFilters()" class="btn-primary">
                        <i class="fas fa-times"></i> Clear All Filters
                    </button>
                </div>
            `;
            entriesContainer.appendChild(noResults);
        } else {
            entries.forEach(entry => {
                const entryElement = this.createEntryCard(entry);
                entriesContainer.appendChild(entryElement);
            });
        }
    }
    
    createEntryCard(entry) {
        const div = document.createElement('div');
        div.className = 'entry-card';
        
        const moodClass = 'mood-' + entry.mood.toLowerCase();
        const contentPreview = this.stripHtml(entry.content).substring(0, 200);
        const shortContent = contentPreview.length < entry.content.length ? contentPreview + '...' : contentPreview;
        
        div.innerHTML = `
            <div class="entry-header">
                <h3 class="entry-title">${this.escapeHtml(entry.title)}</h3>
                <div class="entry-meta">
                    <span><i class="fas fa-calendar"></i> ${this.formatDisplayDate(entry.entry_date)}</span>
                    <span class="mood-badge ${moodClass}">${this.escapeHtml(entry.mood)}</span>
                </div>
            </div>
            <div class="entry-content">
                ${shortContent}
            </div>
            <div class="entry-actions">
                <a href="view_entry.php?id=${entry.entry_id}" class="btn btn-primary btn-small">
                    <i class="fas fa-eye"></i> Read More
                </a>
                <a href="edit_entry.php?id=${entry.entry_id}" class="btn btn-warning btn-small">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="delete_entry.php?id=${entry.entry_id}" class="btn btn-danger btn-small" onclick="return confirm('Are you sure you want to delete this entry?')">
                    <i class="fas fa-trash"></i> Delete
                </a>
            </div>
        `;
        
        return div;
    }
    
    hasActiveFilters() {
        return this.currentFilters.search || 
               this.currentFilters.dateFrom || 
               this.currentFilters.dateTo || 
               this.currentFilters.moods.length > 0 || 
               this.currentFilters.contentTypes.length > 0 ||
               this.currentFilters.sortBy !== 'date_desc';
    }
    
    stripHtml(html) {
        const tmp = document.createElement('div');
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || '';
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    formatDisplayDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    }
}

// Initialize search and filter system
let diarySearchFilter;
document.addEventListener('DOMContentLoaded', function() {
    diarySearchFilter = new DiarySearchFilter();
});
</script> 