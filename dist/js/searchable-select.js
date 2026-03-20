/**
 * SearchableSelect
 * A lightweight, reusable searchable dropdown component in Vanilla JS.
 * Author: Antigravity
 */

class SearchableSelect {
    constructor(container, options = {}) {
        this.container = typeof container === 'string' ? document.querySelector(container) : container;
        if (!this.container || this.container.dataset.ssInitialized) return;
        this.container.dataset.ssInitialized = 'true';

        // Default configuration
        this.options = Object.assign({
            url: this.container.dataset.url || '',
            minLength: 3,
            delay: 300,
            maxResults: 15,
            placeholder: this.container.dataset.placeholder || 'Search...',
            name: this.container.dataset.name || 'ss_value',
            onSelect: null,
            initialText: this.container.dataset.initialText || '',
            initialValue: this.container.dataset.initialValue || '',
            showOnFocus: this.container.dataset.showOnFocus === 'true' || options.showOnFocus || false,
            readonly: this.container.dataset.readonly === 'true' || options.readonly || false
        }, options);

        this.selectedIndex = -1;
        this.items = [];
        this.timer = null;
        this.currentQuery = '';

        this._init();
    }

    _init() {
        this.container.classList.add('searchable-select-container');
        
        // Ensure idempotency: Remove any existing component elements
        this.container.querySelectorAll('.ss-input, .ss-dropdown, .ss-selection, .ss-search-container').forEach(el => el.remove());

        // 0. Handle Existing Select (for static mode)
        const existingSelect = this.container.querySelector('select');
        if (existingSelect) {
            this.originalSelect = existingSelect;
            
            // Explicitly destroy Select2 if present
            if (typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.select2) {
                const $sel = jQuery(this.originalSelect);
                if ($sel.hasClass('select2-hidden-accessible')) {
                    $sel.select2('destroy');
                }
            }
            // Remove any leftover select2 containers
            const nextEl = this.originalSelect.nextSibling;
            if (nextEl && nextEl.classList && nextEl.classList.contains('select2-container')) {
                nextEl.remove();
            }

            this.originalSelect.style.display = 'none';
            if (this.items.length === 0 && !this.options.url) {
                this._extractOptions();
            }
            if (!this.options.name) this.options.name = this.originalSelect.name;
            if (!this.options.initialValue) this.options.initialValue = this.originalSelect.value;
            if (!this.options.initialText && this.originalSelect.selectedIndex >= 0) {
                this.options.initialText = this.originalSelect.options[this.originalSelect.selectedIndex].text;
            }
        }

        // 1. Setup Search Input (The visible field)
        this.input = document.createElement('input');
        this.input.type = 'text';
        this.input.classList.add('form-control', 'form-control-sm', 'ss-input');
        this.input.placeholder = this.options.placeholder;
        this.input.value = this.options.initialText || '';
        this.input.autocomplete = 'off';
        if (this.options.readonly) {
            this.input.readOnly = true;
            this.input.classList.add('bg-light');
            this.input.style.cursor = 'default';
        }
        this.container.appendChild(this.input);

        // 2. Setup Hidden Input (if no original select)
        if (!this.originalSelect) {
            this.hiddenInput = document.createElement('input');
            this.hiddenInput.type = 'hidden';
            this.hiddenInput.name = this.options.name;
            this.hiddenInput.value = this.options.initialValue || '';
            this.container.appendChild(this.hiddenInput);
        }

        // 3. Setup Dropdown
        this.dropdown = document.createElement('div');
        this.dropdown.classList.add('ss-dropdown');
        this.dropdown.style.display = 'none';

        // 4. Setup Results Container
        this.resultsContainer = document.createElement('div');
        this.resultsContainer.classList.add('ss-results-container');
        this.dropdown.appendChild(this.resultsContainer);

        this.container.appendChild(this.dropdown);

        this._bindEvents();
    }

    _extractOptions() {
        this.staticItems = [];
        Array.from(this.originalSelect.options).forEach(opt => {
            if (opt.value === "" && !opt.text) return; // Skip empty placeholders if any
            this.staticItems.push({
                id: opt.value,
                name: opt.text,
                text: opt.text
            });
        });
        this.items = this.staticItems;
    }

    _updateSelectionDisplay(text) {
        if (text) {
            this.selectionBox.innerHTML = `<span class="ss-text">${text}</span>`;
        } else {
            this.selectionBox.innerHTML = `<span class="ss-placeholder">${this.options.placeholder}</span>`;
        }
    }

    _bindEvents() {
        if (this.options.readonly) return;

        // Input handling
        this.input.addEventListener('input', () => this._handleInput());
        this.input.addEventListener('keydown', (e) => this._handleKeydown(e));
        
        // Focus handling
        this.input.addEventListener('focus', () => {
            const query = this.input.value.trim();
            if (this.options.showOnFocus || query.length >= this.options.minLength) {
                if (this.options.url && (this.items.length === 0 || query !== this.currentQuery)) {
                    this._fetchResults(query);
                } else {
                    this.showDropdown();
                }
            }
        });

        // Click outside handling
        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target)) {
                this.hideDropdown();
            }
        });
    }

    _handleInput() {
        clearTimeout(this.timer);
        const query = this.input.value.trim();
        this.currentQuery = query;

        // Reset ID on typing (optional, but consistent with "re-searching")
        if (this.originalSelect) this.originalSelect.value = '';
        else this.hiddenInput.value = '';

        if (this.options.url) {
            if (query.length < this.options.minLength && !this.options.showOnFocus) {
                this.hideDropdown();
                return;
            }
            this.timer = setTimeout(() => this._fetchResults(query), this.options.delay);
        } else {
            this._filterStaticItems(query);
        }
    }

    _filterStaticItems(query) {
        if (!query) {
            this.items = this.staticItems;
        } else {
            const regex = new RegExp(query, 'gi');
            this.items = this.staticItems.filter(item => (item.text || item.name).match(regex));
        }
        this._renderResults(query);
    }

    async _fetchResults(query) {
        if (query !== this.currentQuery) return;

        this.resultsContainer.innerHTML = '<div class="ss-loading">Searching...</div>';
        this.showDropdown();

        try {
            const baseUrl = this.options.url;
            const connector = baseUrl.includes('?') ? '&' : '?';
            const url = `${baseUrl}${connector}q=${encodeURIComponent(query)}`;
            
            const response = await fetch(url);
            const data = await response.json();
            
            const rawItems = data.results || data;
            this.items = Array.isArray(rawItems) ? rawItems.slice(0, this.options.maxResults) : [];
            
            this._renderResults(query);
        } catch (error) {
            console.error('SearchableSelect Error:', error);
            this.resultsContainer.innerHTML = '<div class="ss-no-results text-danger">Search failed</div>';
        }
    }

    _renderResults(query) {
        if (this.items.length === 0) {
            this.resultsContainer.innerHTML = '<div class="ss-no-results">No results found</div>';
            return;
        }

        const ul = document.createElement('ul');
        ul.classList.add('ss-list');

        this.items.forEach((item, index) => {
            const li = document.createElement('li');
            li.classList.add('ss-item');
            if (index === this.selectedIndex) li.classList.add('highlighted');

            // Implementation of match highlighting
            const text = item.text || item.name || '';
            const regex = new RegExp(`(${query})`, 'gi');
            li.innerHTML = text.replace(regex, '<b>$1</b>');
            
            // Support for sub-text (e.g., category or price)
            if (item.subtext) {
                li.innerHTML += `<br><small class="text-muted">${item.subtext}</small>`;
            }

            li.addEventListener('click', () => this.selectItem(item));
            ul.appendChild(li);
        });

        this.resultsContainer.innerHTML = '';
        this.resultsContainer.appendChild(ul);
        this.selectedIndex = -1; 
    }

    _handleKeydown(e) {
        const listItems = this.dropdown.querySelectorAll('.ss-item');
        
        switch (e.key) {
            case 'ArrowDown':
                if (this.dropdown.style.display === 'none') {
                    this.showDropdown();
                } else {
                    e.preventDefault();
                    this.selectedIndex = Math.min(this.selectedIndex + 1, listItems.length - 1);
                    this._updateHighlight(listItems);
                }
                break;
            case 'ArrowUp':
                if (this.dropdown.style.display !== 'none') {
                    e.preventDefault();
                    this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
                    this._updateHighlight(listItems);
                }
                break;
            case 'Enter':
                if (this.dropdown.style.display !== 'none') {
                    e.preventDefault();
                    const targetIdx = this.selectedIndex >= 0 ? this.selectedIndex : 0;
                    if (this.items[targetIdx]) this.selectItem(this.items[targetIdx]);
                }
                break;
            case 'Tab':
                // TAB SELECTION: If dropdown is open, select highlighted or first
                if (this.dropdown.style.display !== 'none' && this.items.length > 0) {
                    const targetIdx = this.selectedIndex >= 0 ? this.selectedIndex : 0;
                    this.selectItem(this.items[targetIdx]);
                    // Don't prevent default, allow Tab to focus next field
                }
                break;
            case 'Escape':
                this.hideDropdown();
                break;
        }
    }

    _updateHighlight(listItems) {
        listItems.forEach((li, index) => {
            li.classList.toggle('highlighted', index === this.selectedIndex);
        });
        if (this.selectedIndex >= 0) {
            listItems[this.selectedIndex].scrollIntoView({ block: 'nearest' });
        }
    }

    selectItem(item) {
        const text = item.text || item.name || '';
        this.input.value = text;
        this.currentQuery = text;
        
        if (this.originalSelect) {
            this.originalSelect.value = item.id;
            this.originalSelect.dispatchEvent(new Event('change', { bubbles: true }));
        } else {
            this.hiddenInput.value = item.id;
            this.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        }

        this.hideDropdown();
        
        if (typeof this.options.onSelect === 'function') {
            this.options.onSelect(item);
        }
        
        this.input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    showDropdown() {
        this.dropdown.style.display = 'block';
        if (!this.options.url) {
            this._renderResults(this.input.value.trim());
        }
    }

    hideDropdown() {
        this.dropdown.style.display = 'none';
        this.selectedIndex = -1;
    }

    /**
     * Update options and reset state
     */
    updateOptions(newOptions = {}) {
        this.options = Object.assign(this.options, newOptions);
        if (newOptions.initialText !== undefined) this.input.value = newOptions.initialText || '';
        if (newOptions.initialValue !== undefined) {
            if (this.originalSelect) this.originalSelect.value = newOptions.initialValue || '';
            else this.hiddenInput.value = newOptions.initialValue || '';
        }
        
        if (newOptions.url || newOptions.initialValue !== undefined) {
             this.hideDropdown();
             this.items = [];
        }
    }

    /**
     * Static helper to auto-initialize all .searchable-select elements
     */
    static initAll() {
        document.querySelectorAll('.searchable-select').forEach(el => {
            new SearchableSelect(el);
        });
    }
}

// Auto-init on DOM content load
document.addEventListener('DOMContentLoaded', () => SearchableSelect.initAll());
