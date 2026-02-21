// ============================================
// STOCKSATHI - MAIN APPLICATION JAVASCRIPT
// ============================================

document.addEventListener('DOMContentLoaded', function () {
    // ============================================
    // MOBILE MENU TOGGLE
    // ============================================
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function () {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        });
    }

    // Auto-close sidebar when clicking navigation links (but not dropdown toggles)
    const navItems = document.querySelectorAll('.sidebar .nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function (e) {
            // Don't close sidebar if this is a dropdown toggle or clicking inside a dropdown
            const isDropdownToggle = this.closest('.nav-dropdown-toggle');
            const isSubItem = this.classList.contains('nav-sub-item');

            // Only close sidebar for actual navigation links (with href), not dropdown toggles
            if (!isDropdownToggle && this.hasAttribute('href') && sidebar && sidebarOverlay) {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            }
        });
    });

    // ============================================
    // NAVIGATION DROPDOWN TOGGLE WITH PERSISTENCE
    // ============================================

    // Get dropdown ID from parent section
    function getDropdownId(parent) {
        const title = parent.querySelector('.nav-section-title');
        return title ? title.textContent.trim().replace(/\s+/g, '-').toLowerCase() : null;
    }

    // Save dropdown state to localStorage
    function saveDropdownState(dropdownId, isOpen) {
        const state = JSON.parse(localStorage.getItem('dropdownState') || '{}');
        state[dropdownId] = isOpen;
        localStorage.setItem('dropdownState', JSON.stringify(state));
    }

    // Get dropdown state from localStorage
    function getDropdownState(dropdownId) {
        const state = JSON.parse(localStorage.getItem('dropdownState') || '{}');
        return state[dropdownId] || false;
    }

    window.toggleNavDropdown = function (element, event) {
        // Prevent event propagation to stop dropdown from closing immediately
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }

        const parent = element.closest('.nav-section');
        const content = parent.querySelector('.nav-dropdown-content');
        const icon = element.querySelector('.nav-dropdown-icon');
        const dropdownId = getDropdownId(parent);

        if (content && dropdownId) {
            // Toggle current dropdown
            const isOpen = content.classList.toggle('show');

            // Update icon
            if (icon) {
                icon.textContent = isOpen ? '▲' : '▼';
            }

            // Save state to localStorage
            saveDropdownState(dropdownId, isOpen);
        }
    };

    // Restore dropdown states on page load and auto-open if active item is inside
    function restoreDropdownStates() {
        const currentPath = window.location.pathname;
        const currentHref = window.location.href;

        document.querySelectorAll('.nav-section').forEach(section => {
            const dropdownId = getDropdownId(section);
            const content = section.querySelector('.nav-dropdown-content');
            const toggle = section.querySelector('.nav-dropdown-toggle');
            const icon = toggle?.querySelector('.nav-dropdown-icon');

            if (dropdownId && content) {
                // Check if current page is a submenu item in this dropdown
                const subItems = content.querySelectorAll('.nav-sub-item');
                let isCurrentPageInDropdown = false;

                subItems.forEach(subItem => {
                    const href = subItem.getAttribute('href');
                    if (href) {
                        const normalizedHref = href.replace('../', '').replace('./', '');
                        if (currentPath.includes(normalizedHref) || currentHref.includes(normalizedHref)) {
                            isCurrentPageInDropdown = true;
                            subItem.classList.add('active');
                        }
                    }
                });

                // Auto-open dropdown if current page is inside it OR if it was previously open
                const wasOpen = getDropdownState(dropdownId);
                if (isCurrentPageInDropdown || wasOpen) {
                    content.classList.add('show');
                    if (icon) {
                        icon.textContent = '▲';
                    }
                    // Save state if auto-opened
                    if (isCurrentPageInDropdown && !wasOpen) {
                        saveDropdownState(dropdownId, true);
                    }
                }
            }
        });
    }

    // Highlight active menu item based on current page
    function setActiveMenuItem() {
        const currentPath = window.location.pathname;
        const currentHref = window.location.href;

        document.querySelectorAll('.nav-item').forEach(item => {
            const href = item.getAttribute('href');
            if (href) {
                const normalizedHref = href.replace('../', '').replace('./', '');
                if (currentPath.includes(normalizedHref) || currentHref.includes(normalizedHref)) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            }
        });
    }

    // Initialize dropdown state on page load
    function initializeNavigation() {
        restoreDropdownStates();
        setActiveMenuItem();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeNavigation);
    } else {
        initializeNavigation();
    }

    // ============================================
    // SIDEBAR LOGOUT FUNCTIONALITY
    // ============================================
    const sidebarLogoutBtn = document.getElementById('sidebarLogoutBtn');
    if (sidebarLogoutBtn && window.authModule) {
        sidebarLogoutBtn.addEventListener('click', async function (e) {
            e.preventDefault();
            await window.authModule.logout();
        });
    }

    // ============================================
    // LOGOUT FUNCTIONALITY (Header)
    // ============================================
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn && window.authModule) {
        logoutBtn.addEventListener('click', async function (e) {
            e.preventDefault();
            await window.authModule.logout();
        });
    }


    // ============================================
    // DROPDOWN MENUS (Header User Dropdown)
    // ============================================
    const dropdowns = document.querySelectorAll('.dropdown');

    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const menu = this.querySelector('.dropdown-menu');
            if (menu) {
                menu.classList.toggle('show');
            }
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function (e) {
        // Don't close if clicking inside a dropdown
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });

    // Prevent default on header icon buttons to avoid page refresh
    document.querySelectorAll('.header-icon-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            // Add your notification/message logic here
            console.log('Header button clicked:', this.title);
        });
    });

    // ============================================
    // TABS FUNCTIONALITY
    // ============================================
    const tabs = document.querySelectorAll('.tab');

    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            const tabGroup = this.closest('.tabs');
            const targetId = this.dataset.tab;

            // Remove active class from all tabs in group
            tabGroup.querySelectorAll('.tab').forEach(t => {
                t.classList.remove('active');
            });

            // Add active class to clicked tab
            this.classList.add('active');

            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.add('hidden');
            });

            // Show target tab content
            const targetContent = document.getElementById(targetId);
            if (targetContent) {
                targetContent.classList.remove('hidden');
            }
        });
    });

    // ============================================
    // MODAL FUNCTIONALITY
    // ============================================
    window.openModal = function (modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModal = function (modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    };

    // Close modal when clicking backdrop
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    });

    // Close modal buttons
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', function () {
            const modal = this.closest('.modal-backdrop');
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    });

    // ============================================
    // FORM VALIDATION HELPER
    // ============================================
    window.validateForm = function (formId) {
        const form = document.getElementById(formId);
        if (!form) return false;

        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.style.borderColor = 'var(--color-danger)';
            } else {
                field.style.borderColor = '';
            }
        });

        return isValid;
    };

    // ============================================
    // TABLE SORTING (Simple)
    // ============================================
    document.querySelectorAll('.sortable th').forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function () {
            const table = this.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const cellIndex = Array.from(this.parentElement.children).indexOf(this);

            rows.sort((a, b) => {
                const aText = a.children[cellIndex].textContent.trim();
                const bText = b.children[cellIndex].textContent.trim();
                return aText.localeCompare(bText);
            });

            rows.forEach(row => tbody.appendChild(row));
        });
    });

    // ============================================
    // SEARCH FILTER FUNCTIONALITY
    // ============================================
    window.filterTable = function (inputId, tableId) {
        const input = document.getElementById(inputId);
        const table = document.getElementById(tableId);

        if (!input || !table) return;

        const filter = input.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    };

    // ============================================
    // TOAST NOTIFICATIONS
    // ============================================
    window.showToast = function (message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type}`;
        toast.textContent = message;
        toast.style.position = 'fixed';
        toast.style.top = '20px';
        toast.style.right = '20px';
        toast.style.zIndex = '9999';
        toast.style.minWidth = '300px';
        toast.style.animation = 'slideIn 0.3s ease-out';

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };

    // ============================================
    // SAMPLE DATA GENERATORS
    // ============================================
    window.generateBarcode = function () {
        return 'BRC' + Math.random().toString(36).substring(2, 11).toUpperCase();
    };

    window.generateInvoiceNumber = function () {
        const date = new Date();
        const year = date.getFullYear();
        const num = Math.floor(Math.random() * 9999) + 1;
        return `INV-${year}-${num.toString().padStart(4, '0')}`;
    };

    window.formatCurrency = function (amount) {
        return '₹' + amount.toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

    window.formatDate = function (date) {
        return new Date(date).toLocaleDateString('en-IN', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    // ============================================
    // ACTIVE NAVIGATION HIGHLIGHTING
    // ============================================
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-item').forEach(item => {
        const href = item.getAttribute('href');
        if (currentPath.includes(href) || (currentPath.endsWith('/') && href === 'index.html')) {
            item.classList.add('active');
        }
    });

    // ============================================
    // PRINT FUNCTIONALITY
    // ============================================
    window.printInvoice = function (invoiceId) {
        window.print();
    };

    // ============================================
    // EXPORT FUNCTIONALITY (Placeholder)
    // ============================================
    window.exportToExcel = function (tableId) {
        showToast('Exporting to Excel...', 'info');
        setTimeout(() => {
            showToast('Export completed successfully!', 'success');
        }, 1500);
    };

    window.exportToPDF = function (tableId) {
        showToast('Exporting to PDF...', 'info');
        setTimeout(() => {
            showToast('Export completed successfully!', 'success');
        }, 1500);
    };
});

// ============================================
// ANIMATION STYLES
// ============================================
if (!document.getElementById('app-animations')) {
    const style = document.createElement('style');
    style.id = 'app-animations';
    style.textContent = `
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideOut {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100px);
        }
    }
`;
    document.head.appendChild(style);
}
