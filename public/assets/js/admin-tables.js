document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.fn-admin-content .fn-price-table').forEach(table => {
        const headings = [...table.querySelectorAll('thead th')].map(cell => cell.textContent.trim().replace(/\s+/g, ' '));
        table.querySelectorAll('tbody tr').forEach(row => {
            [...row.cells].forEach((cell, index) => {
                if (cell.colSpan > 1) return;
                cell.dataset.label = headings[index] || '';
            });
        });
    });
});
