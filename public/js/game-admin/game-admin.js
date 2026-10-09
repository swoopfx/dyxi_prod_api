/**
 * GameAdmin Module Self-Contained JavaScript Interface
 */

document.addEventListener('DOMContentLoaded', function () {
    console.log('[GameAdmin] Self-contained layout scripts initialized.');

    // 1. Mobile Sidebar Toggle Listener
    const sidebarToggleBtn = document.getElementById('ga-sidebar-toggle');
    const sidebar = document.querySelector('.ga-sidebar');
    if (sidebarToggleBtn && sidebar) {
        sidebarToggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });
    }

    // 2. Real-time Live Stats Refresher
    function fetchLiveStats() {
        const livePlayersEl = document.getElementById('ga-live-players');
        const liveLatencyEl = document.getElementById('ga-live-latency');

        if (!livePlayersEl && !liveLatencyEl) return;

        fetch('/api/game-admin/stats')
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.success && data.metrics) {
                    if (livePlayersEl) {
                        const count = data.metrics.active_players !== undefined 
                            ? data.metrics.active_players 
                            : (data.metrics.global_active_players || 0);
                        livePlayersEl.textContent = Number(count).toLocaleString();
                    }
                    if (liveLatencyEl && data.metrics.server_latency_ms !== undefined) {
                        liveLatencyEl.textContent = data.metrics.server_latency_ms + ' ms';
                    }
                }
            })
            .catch(err => {
                console.warn('[GameAdmin] Polling stats error:', err);
            });
    }

    // Trigger immediate fetch and poll live stats every 8 seconds
    if (document.getElementById('ga-live-players')) {
        fetchLiveStats();
        setInterval(fetchLiveStats, 8000);
    }

    // 3. Live Client-side Table Filter for Game List
    const searchInput = document.getElementById('ga-game-search');
    const tableBody = document.querySelector('.ga-table tbody');

    if (searchInput && tableBody) {
        searchInput.addEventListener('keyup', function (e) {
            const query = e.target.value.toLowerCase().trim();
            const rows = tableBody.querySelectorAll('tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // 4. Modal Dialog Handlers
    const openModalBtn = document.getElementById('ga-btn-create-game');
    const closeModalBtn = document.getElementById('ga-close-modal');
    const modalBackdrop = document.getElementById('ga-modal-backdrop');

    if (openModalBtn && modalBackdrop) {
        openModalBtn.addEventListener('click', function () {
            modalBackdrop.classList.add('open');
        });
    }

    if (closeModalBtn && modalBackdrop) {
        closeModalBtn.addEventListener('click', function () {
            modalBackdrop.classList.remove('open');
        });
    }

    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', function (e) {
            if (e.target === modalBackdrop) {
                modalBackdrop.classList.remove('open');
            }
        });
    }

    // 5. Toast Notification Helper
    window.GameAdminToast = function (message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `ga-toast ga-toast-${type}`;
        toast.style.cssText = `
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: rgba(17, 24, 39, 0.95);
            border: 1px solid var(--ga-primary);
            color: #fff;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 0.88rem;
            z-index: 2000;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            transition: all 0.3s ease;
        `;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };
});
