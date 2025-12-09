function setupTableSearch(inputId, tableId, filterId = null) {
    const input = document.getElementById(inputId);
    const filterSelect = filterId ? document.getElementById(filterId) : null;

    // Function to execute filter
    function applyFilters() {
        const searchText = input.value.toLowerCase();
        const filterValue = filterSelect ? filterSelect.value.toLowerCase() : '';
        const rows = document.querySelectorAll('table tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            // Boolean logic: Must match Search Text AND (if filter selected, must match filter)
            const matchesSearch = text.includes(searchText);
            const matchesFilter = filterValue === '' || text.includes(filterValue);

            row.style.display = (matchesSearch && matchesFilter) ? '' : 'none';
        });
    }

    if (input) {
        input.addEventListener('keyup', applyFilters);
    }

    if (filterSelect) {
        filterSelect.addEventListener('change', applyFilters);
    }
}

// Auto-init
document.addEventListener('DOMContentLoaded', function () {
    setupTableSearch('globalSearch', null, 'documentFilter');
});
