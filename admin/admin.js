document.addEventListener('DOMContentLoaded', function() {
    // Initialize all admin functionality
    initAdminFeatures();
    
    // Confirm before performing destructive actions
    document.querySelectorAll('.confirm-action').forEach(button => {
        button.addEventListener('click', function(e) {
            const action = this.dataset.action || 'this action';
            if (!confirm(`Are you sure you want to ${action}?`)) {
                e.preventDefault();
            }
        });
    });

    // Initialize game management functionality
    if (document.querySelector('.admin-games')) {
        initGameManagement();
    }

    // Initialize form validations
    if (document.querySelector('form')) {
        initFormValidations();
    }
});

/**
 * Initialize admin features
 */
function initAdminFeatures() {
    console.log('Admin panel initialized');
    
    // Add any admin-specific initialization here
    initSortableTables();
    initDynamicFilters();
}

/**
 * Initialize game management functionality
 */
function initGameManagement() {
    // Image preview for game cards
    const gameCards = document.querySelectorAll('.game-card');
    gameCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });

    // Toggle game active status
    document.querySelectorAll('.toggle-game-active').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const gameId = this.dataset.gameId;
            const isActive = this.checked ? 1 : 0;
            
            fetch('admin_api.php?action=toggle_game_active', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    game_id: gameId,
                    is_active: isActive
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    this.checked = !this.checked;
                    alert('Error updating game status');
                }
            });
        });
    });

    // Game image preview
    const imageInput = document.getElementById('game_image');
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            const preview = document.getElementById('image-preview');
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
}

/**
 * Initialize form validations
 */
function initFormValidations() {
    // URL validation for download link
    const downloadLinkInput = document.getElementById('download_link');
    if (downloadLinkInput) {
        downloadLinkInput.addEventListener('input', function() {
            const isValid = /^https?:\/\/.+\..+/.test(this.value);
            this.style.borderColor = isValid ? '#00b894' : '#fd79a8';
        });
    }

    // Game form validation
    const gameForm = document.getElementById('game-form');
    if (gameForm) {
        gameForm.addEventListener('submit', function(e) {
            const nameInput = this.querySelector('#game_name');
            const imageInput = this.querySelector('#game_image');
            
            if (nameInput.value.trim().length < 2) {
                e.preventDefault();
                nameInput.style.borderColor = '#fd79a8';
                alert('Game name must be at least 2 characters');
            }
            
            if (imageInput.value && !/\.(png|webp|jpg|jpeg)$/i.test(imageInput.value)) {
                e.preventDefault();
                imageInput.style.borderColor = '#fd79a8';
                alert('Please select a valid image file (PNG, WEBP, JPG)');
            }
        });
    }
}

/**
 * Make tables sortable
 */
function initSortableTables() {
    document.querySelectorAll('table').forEach(table => {
        const headers = table.querySelectorAll('th[data-sort]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                const column = header.dataset.sort;
                const direction = header.dataset.sortDirection === 'asc' ? 'desc' : 'asc';
                
                // Update all headers
                headers.forEach(h => {
                    h.dataset.sortDirection = '';
                    h.querySelector('.sort-icon')?.remove();
                });
                
                // Set current direction
                header.dataset.sortDirection = direction;
                
                // Add sort icon
                const icon = document.createElement('i');
                icon.className = `fas fa-sort-${direction === 'asc' ? 'up' : 'down'} sort-icon`;
                header.appendChild(icon);
                
                // Sort table
                sortTable(table, column, direction);
            });
        });
    });
}

/**
 * Sort table by column
 */
function sortTable(table, column, direction) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aValue = a.querySelector(`td[data-column="${column}"]`)?.textContent || '';
        const bValue = b.querySelector(`td[data-column="${column}"]`)?.textContent || '';
        
        if (direction === 'asc') {
            return aValue.localeCompare(bValue);
        } else {
            return bValue.localeCompare(aValue);
        }
    });
    
    // Re-add rows in sorted order
    rows.forEach(row => tbody.appendChild(row));
}

/**
 * Initialize dynamic filters
 */
function initDynamicFilters() {
    const searchInputs = document.querySelectorAll('.table-filter');
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            const tableId = this.dataset.table;
            const column = this.dataset.column;
            const searchValue = this.value.toLowerCase();
            
            document.querySelectorAll(`#${tableId} tbody tr`).forEach(row => {
                const cellValue = row.querySelector(`td[data-column="${column}"]`)?.textContent.toLowerCase() || '';
                row.style.display = cellValue.includes(searchValue) ? '' : 'none';
            });
        });
    });
}