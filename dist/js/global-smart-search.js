/**
 * Global Smart Search with TAB Selection
 * Provides interactive search dropdown for inputs with class 'smart-search'
 */

$(document).ready(function () {
    let searchTimeout = null;
    let selectedIndex = -1;

    // Handle input in smart-search fields
    $(document).on('input', '.smart-search', function () {
        const _this = $(this);
        const keyword = _this.val().trim();
        const module = _this.data('module');

        clearTimeout(searchTimeout);

        if (keyword.length < 3) {
            closeSearchDropdown();
            return;
        }

        // Debounce search
        searchTimeout = setTimeout(function () {
            performGlobalSearch(_this, module, keyword);
        }, 300);
    });

    // Handle keyboard navigation
    $(document).on('keydown', '.smart-search', function (e) {
        const dropdown = $('#smart-search-dropdown');
        if (!dropdown.is(':visible')) return;

        const items = dropdown.find('.smart-search-item');

        if (e.which === 40) { // Arrow Down
            e.preventDefault();
            selectedIndex = (selectedIndex + 1) % items.length;
            highlightItem(items, selectedIndex);
        } else if (e.which === 38) { // Arrow Up
            e.preventDefault();
            selectedIndex = (selectedIndex - 1 + items.length) % items.length;
            highlightItem(items, selectedIndex);
        } else if (e.which === 13 || e.which === 9) { // Enter or TAB
            if (selectedIndex >= 0 || e.which === 9) {
                // If TAB is pressed, always pick the first result if nothing is highlighted
                const indexToSelect = (selectedIndex >= 0) ? selectedIndex : 0;
                const selectedItem = items.eq(indexToSelect);
                if (selectedItem.length > 0) {
                    e.preventDefault();
                    selectSearchItem(selectedItem);
                }
            }
        } else if (e.which === 27) { // ESC
            closeSearchDropdown();
        }
    });

    // Close dropdown when clicking outside
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.smart-search-wrapper').length) {
            closeSearchDropdown();
        }
    });

    function performGlobalSearch(input, module, keyword) {
        // Ensure wrapper exists
        if (!input.parent().hasClass('smart-search-wrapper')) {
            input.wrap('<div class="smart-search-wrapper"></div>');
        }

        $.ajax({
            url: _base_url_ + 'admin/ajax/global-search.php',
            method: 'GET',
            data: { module: module, keyword: keyword },
            dataType: 'json',
            success: function (resp) {
                renderSearchDropdown(input, resp);
            },
            error: function (err) {
                console.error("Global Search Error:", err);
            }
        });
    }

    function renderSearchDropdown(input, results) {
        closeSearchDropdown();

        const dropdown = $('<div id="smart-search-dropdown" class="smart-search-dropdown"></div>');

        if (results.length === 0) {
            dropdown.append('<div class="smart-search-no-results">No results found</div>');
        } else {
            results.forEach((item, index) => {
                const itemEl = $('<div class="smart-search-item"></div>');
                itemEl.text(item.title);
                itemEl.data('url', item.url);
                itemEl.on('click', function () {
                    selectSearchItem($(this));
                });
                dropdown.append(itemEl);
            });
        }

        input.after(dropdown);
        dropdown.fadeIn(200);

        // Auto-highlight first result
        selectedIndex = -1;
        if (results.length > 0) {
            selectedIndex = 0;
            highlightItem(dropdown.find('.smart-search-item'), 0);
        }
    }

    function highlightItem(items, index) {
        items.removeClass('highlighted');
        const item = items.eq(index);
        item.addClass('highlighted');

        // Ensure highlighted item is visible in scroll
        const dropdown = $('#smart-search-dropdown');
        if (item.length) {
            const dropdownTop = dropdown.scrollTop();
            const dropdownBottom = dropdownTop + dropdown.height();
            const itemTop = item.position().top + dropdownTop;
            const itemBottom = itemTop + item.outerHeight();

            if (itemBottom > dropdownBottom) {
                dropdown.scrollTop(itemBottom - dropdown.height());
            } else if (itemTop < dropdownTop) {
                dropdown.scrollTop(itemTop);
            }
        }
    }

    function selectSearchItem(item) {
        const url = item.data('url');
        const input = item.closest('.smart-search-wrapper').find('.smart-search');
        if (url) {
            if (input.data('module') === 'top_global') {
                window.open(url, '_blank');
                closeSearchDropdown();
                input.val(''); // Clear global search after selection
            } else {
                location.href = url;
            }
        }
    }

    function closeSearchDropdown() {
        $('#smart-search-dropdown').remove();
        selectedIndex = -1;
    }
});
