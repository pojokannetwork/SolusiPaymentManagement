</div> <!-- closes container-fluid from header -->
</div> <!-- closes main-content from header -->

<!-- Common Admin JS -->
<!-- jQuery and Bootstrap JS are now loaded in admin_header.php -->
<!-- Local Bootstrap bundle is not full; JS fallback below handles modal/offcanvas if CDN is blocked -->
<script src="/assets/vendor/bootstrap/local-bootstrap.js" defer></script>
<script>
// jQuery minimal fallback (if CDN blocked)
(function(){
    if (typeof window.jQuery === 'undefined' && typeof window.$ === 'undefined') {
        const ajaxConfig = { headers: {} };
        function ajaxSetup(opts){ if (opts && opts.headers){ ajaxConfig.headers = Object.assign({}, ajaxConfig.headers, opts.headers); } }
        function post(url, data){
            const headers = Object.assign({ 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, ajaxConfig.headers);
            const body = (typeof data === 'string') ? data : new URLSearchParams(data || {}).toString();
            return fetch(url, { method: 'POST', headers, body });
        }
        window.$ = { ajaxSetup, post };
    }
})();
</script>
<script>
// Lightweight Bootstrap fallback for offline/restricted environments
// Provides minimal Modal, Offcanvas, and Alert APIs used by pages
(function(){
    if (typeof window.bootstrap !== 'undefined') return;

    function addBackdrop() {
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        document.body.appendChild(backdrop);
        document.body.classList.add('modal-open');
        return backdrop;
    }
    function removeBackdrop() {
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();
        document.body.classList.remove('modal-open');
    }

    class ModalFallback {
        constructor(element) {
            this.el = typeof element === 'string' ? document.querySelector(element) : element;
        }
        show() {
            if (!this.el) return;
            this.el.style.display = 'block';
            this.el.removeAttribute('aria-hidden');
            this.el.setAttribute('aria-modal', 'true');
            this.el.classList.add('show');
            addBackdrop();
        }
        hide() {
            if (!this.el) return;
            this.el.classList.remove('show');
            this.el.style.display = 'none';
            this.el.setAttribute('aria-hidden', 'true');
            removeBackdrop();
        }
        static getOrCreateInstance(element) { return new ModalFallback(element); }
    }

    class OffcanvasFallback {
        constructor(element) {
            this.el = typeof element === 'string' ? document.querySelector(element) : element;
        }
        show() {
            if (!this.el) return;
            this.el.classList.add('show');
            this.el.style.visibility = 'visible';
        }
        hide() {
            if (!this.el) return;
            this.el.classList.remove('show');
            this.el.style.visibility = '';
        }
        static getOrCreateInstance(element) { return new OffcanvasFallback(element); }
    }

    class AlertFallback {
        constructor(element) { this.el = element; }
        close() { if (this.el && this.el.remove) this.el.remove(); }
        static getOrCreateInstance(element) { return new AlertFallback(element); }
    }

    // Expose minimal bootstrap API
    window.bootstrap = {
        Modal: ModalFallback,
        Offcanvas: OffcanvasFallback,
        Alert: AlertFallback
    };

    // Handle [data-bs-dismiss] in fallback mode
    document.addEventListener('click', function(e){
        const dismissModalBtn = e.target.closest('[data-bs-dismiss="modal"]');
        if (dismissModalBtn) {
            e.preventDefault();
            const modalEl = dismissModalBtn.closest('.modal');
            if (modalEl) new ModalFallback(modalEl).hide();
            return;
        }
        const dismissOffcanvasBtn = e.target.closest('[data-bs-dismiss="offcanvas"]');
        if (dismissOffcanvasBtn) {
            e.preventDefault();
            const offEl = dismissOffcanvasBtn.closest('.offcanvas');
            if (offEl) new OffcanvasFallback(offEl).hide();
        }
    });
})();
</script>

<!-- Common script logic -->
<script>
    // CSRF token for all AJAX requests
    const csrfToken = '<?php echo getCsrfToken(); ?>';
    $.ajaxSetup({
        headers: {
            'X-CSRF-Token': csrfToken
        }
    });

    // Enhanced Sidebar toggle for mobile (disabled to prevent overlay)
    function toggleSidebar(open) {
        const sidebar = document.getElementById('sidebar');
        const body = document.body;
        // Force static behavior: always visible, no overlay/backdrop
        body.classList.remove('sidebar-open');
        if (sidebar) sidebar.classList.add('show');
    }

    // Theme toggle functionality
    function toggleTheme() {
        const html = document.documentElement;
        const currentTheme = html.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        // Update icon
        const icon = document.querySelector('.fa-moon, .fa-sun');
        if (icon) {
            icon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }

    // Enhanced responsive functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
        
        // Update theme icon
        const icon = document.querySelector('.fa-moon, .fa-sun');
        if (icon) {
            icon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
        
        // Make tables responsive
        document.querySelectorAll('table').forEach(table => {
            if (!table.closest('.table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'table-responsive';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
            table.classList.add('table-mobile');
            
            // Add data-label attributes for mobile view
            const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
            table.querySelectorAll('tbody tr').forEach(row => {
                row.querySelectorAll('td').forEach((cell, index) => {
                    if (headers[index] && !cell.hasAttribute('data-label')) {
                        cell.setAttribute('data-label', headers[index]);
                    }
                });
            });
        });
        
        // Add responsive classes to elements
        document.querySelectorAll('.btn').forEach(btn => {
            if (!btn.classList.contains('btn-responsive')) {
                btn.classList.add('btn-responsive');
            }
        });
        
        document.querySelectorAll('.card').forEach(card => {
            if (!card.classList.contains('card-responsive')) {
                card.classList.add('card-responsive');
            }
        });
        
        // Add fade-in animation to cards
        document.querySelectorAll('.card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease-out';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });

    // Close sidebar on link click (mobile) and Esc key
    document.querySelectorAll('.sidebar .nav-link').forEach(function(link){
        link.addEventListener('click', function(e){
            // If this is a submenu toggle, do not close sidebar
            if (link.closest('.nav-item.has-submenu')) return;
            if (window.innerWidth < 992) {
                setTimeout(() => toggleSidebar(false), 100);
            }
        });
    });
    
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape') {
            toggleSidebar(false);
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        const sidebar = document.getElementById('sidebar');
        
        if (window.innerWidth >= 992) {
            document.body.classList.remove('sidebar-open');
            if (sidebar) sidebar.classList.remove('show');
        }
    });

    // Touch/swipe gestures for mobile
    let startX = 0;
    let startY = 0;
    
    document.addEventListener('touchstart', function(e) {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
    });
    
    document.addEventListener('touchmove', function(e) {
        if (!startX || !startY) return;
        
        const diffX = e.touches[0].clientX - startX;
        const diffY = e.touches[0].clientY - startY;
        
        if (Math.abs(diffX) > Math.abs(diffY) && window.innerWidth <= 991) {
            const sidebar = document.getElementById('sidebar');
            const body = document.body;
            
            if (diffX > 50 && startX < 20 && !body.classList.contains('sidebar-open')) {
                // Swipe right from left edge to open
                toggleSidebar(true);
            } else if (diffX < -50 && body.classList.contains('sidebar-open')) {
                // Swipe left to close
                toggleSidebar(false);
            }
        }
        
        startX = 0;
        startY = 0;
    });

    // Submenu toggle functionality
    function toggleSubmenu(element) {
        const navItem = element.closest('.nav-item.has-submenu');
        if (!navItem) return false;

        const submenu = navItem.querySelector('.submenu');
        if (!submenu) return false;

        const isOpen = navItem.classList.contains('open');

        // Close sibling submenus at the same level only (keep ancestors open)
        const container = navItem.parentElement;
        const siblings = container ? container.querySelectorAll(':scope > li.nav-item.has-submenu.open') : [];
        (siblings || []).forEach(item => {
            if (item !== navItem) {
                item.classList.remove('open');
                const otherSubmenu = item.querySelector('.submenu');
                if (otherSubmenu) otherSubmenu.classList.remove('open');
            }
        });

        // Toggle current submenu
        if (isOpen) {
            navItem.classList.remove('open');
            submenu.classList.remove('open');
        } else {
            navItem.classList.add('open');
            submenu.classList.add('open');
        }

        // Returning false lets inline onclick="return toggleSubmenu(this)" prevent default
        return false;
    }
    
    // Make toggleSubmenu globally available
    window.toggleSubmenu = toggleSubmenu;

    // Auto-open submenu if current page is in submenu
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, setting up menu handlers');
        
        // Alternative event delegation for submenu toggles
        document.addEventListener('click', function(e) {
            const link = e.target.closest('.nav-item.has-submenu > .nav-link');
            if (link) {
                console.log('Submenu link clicked via delegation');
                e.preventDefault();
                toggleSubmenu(link);
                return false;
            }
            
            // Handle theme toggle button
            if (e.target.closest('[onclick*="toggleTheme"]')) {
                e.preventDefault();
                toggleTheme();
                return false;
            }
            
            // Handle sidebar toggle button  
            if (e.target.closest('[onclick*="toggleSidebar"]')) {
                e.preventDefault();
                toggleSidebar();
                return false;
            }
            
            // Handle logout button
            if (e.target.closest('[onclick*="logout"]')) {
                e.preventDefault();
                logout();
                return false;
            }
        });
        
        // Find active submenu item and open its parent
        const activeSubmenuItem = document.querySelector('.submenu .nav-link.active');
        if (activeSubmenuItem) {
            const parentNavItem = activeSubmenuItem.closest('.nav-item.has-submenu');
            if (parentNavItem) {
                parentNavItem.classList.add('open');
                const submenu = parentNavItem.querySelector('.submenu');
                if (submenu) submenu.classList.add('open');
            }
        }
        
        // Test all submenu toggles
        const submenuToggles = document.querySelectorAll('.nav-item.has-submenu > .nav-link');
        console.log('Found submenu toggles:', submenuToggles.length);
        
        // Add hover effect for better UX and setup click handlers
        submenuToggles.forEach(toggle => {
            toggle.style.cursor = 'pointer';
            
            // Remove onclick attribute and add click listener instead
            toggle.removeAttribute('onclick');
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Direct click listener on submenu');
                toggleSubmenu(this);
                return false;
            });
        });
        
        // Setup other buttons with event listeners
        document.querySelectorAll('[onclick*="toggleTheme"]').forEach(btn => {
            btn.removeAttribute('onclick');
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                toggleTheme();
            });
        });
        
        document.querySelectorAll('[onclick*="toggleSidebar"]').forEach(btn => {
            btn.removeAttribute('onclick');
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                toggleSidebar();
            });
        });
        
        document.querySelectorAll('[onclick*="logout"]').forEach(btn => {
            btn.removeAttribute('onclick');
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                logout();
            });
        });
    });

    // Logout function
    function logout() {
        if(confirm('Apakah Anda yakin ingin keluar?')) {
            $.post('/api/public/logout').done(function(){ 
                window.location.href = '/'; 
            }).fail(function() {
                // Fallback if AJAX fails
                window.location.href = '/';
            });
        }
    }
    
    // Make functions globally available
    window.logout = logout;
    window.toggleTheme = toggleTheme;
    window.toggleSidebar = toggleSidebar;
</script>

<?php
// Render any page-specific scripts
if (isset($page_specific_scripts)) {
    echo $page_specific_scripts;
}
?>

</body>
</html>
