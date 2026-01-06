    </main>
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h4>CONSTRUKT</h4>
                    <p>Your reliable construction materials supplier</p>
                </div>
                <div class="footer-col">
                    <h4>Contact</h4>
                    <p>+370 800 12345</p>
                    <p>info@construkt.lt</p>
                </div>
                <div class="footer-col">
                    <h4>Working Hours</h4>
                    <p>Mon-Fri: 8:00 - 20:00</p>
                    <p>Sat: 9:00 - 18:00</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Construkt. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <?php if ($currentUser): ?>
    <style>
    /* Chatbot Widget Positioning - Above Footer */
    #chatbot-widget-container,
    .chatbot-widget,
    [class*="chatbot"] {
        bottom: 100px !important;
    }

    /* Draggable widget styles */
    .chatbot-drag-handle {
        cursor: move;
        user-select: none;
    }
    </style>

    <script>
        window.CHATBOT_CONFIG = {
            apiUrl: '<?= CHATBOT_API_URL ?>',
            userId: <?= $currentUser['id'] ?>,
            userName: '<?= htmlspecialchars($currentUser['first_name']) ?>',
            theme: 'light',
            position: 'bottom-right'
        };

        // Make chatbot widget draggable
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                // Find the chatbot widget container
                const widget = document.querySelector('[class*="chatbot"], #chatbot-widget-container, .chatbot-container');
                if (!widget) return;

                // Get stored position
                const savedPos = localStorage.getItem('chatbot_position');
                if (savedPos) {
                    const pos = JSON.parse(savedPos);
                    widget.style.position = 'fixed';
                    widget.style.left = pos.left + 'px';
                    widget.style.top = pos.top + 'px';
                    widget.style.right = 'auto';
                    widget.style.bottom = 'auto';
                }

                let isDragging = false;
                let startX, startY, startLeft, startTop;

                // Find header/title area to use as drag handle
                const header = widget.querySelector('[class*="header"], [class*="title"], [class*="Header"]');
                const dragTarget = header || widget;

                dragTarget.style.cursor = 'move';

                dragTarget.addEventListener('mousedown', function(e) {
                    // Don't start drag if clicking on buttons or inputs
                    if (e.target.tagName === 'BUTTON' || e.target.tagName === 'INPUT' ||
                        e.target.tagName === 'TEXTAREA' || e.target.closest('button')) {
                        return;
                    }

                    isDragging = true;
                    startX = e.clientX;
                    startY = e.clientY;

                    const rect = widget.getBoundingClientRect();
                    startLeft = rect.left;
                    startTop = rect.top;

                    widget.style.transition = 'none';
                    e.preventDefault();
                });

                document.addEventListener('mousemove', function(e) {
                    if (!isDragging) return;

                    const deltaX = e.clientX - startX;
                    const deltaY = e.clientY - startY;

                    let newLeft = startLeft + deltaX;
                    let newTop = startTop + deltaY;

                    // Keep widget within viewport
                    const widgetRect = widget.getBoundingClientRect();
                    const maxLeft = window.innerWidth - widgetRect.width;
                    const maxTop = window.innerHeight - widgetRect.height;

                    newLeft = Math.max(0, Math.min(newLeft, maxLeft));
                    newTop = Math.max(0, Math.min(newTop, maxTop));

                    widget.style.position = 'fixed';
                    widget.style.left = newLeft + 'px';
                    widget.style.top = newTop + 'px';
                    widget.style.right = 'auto';
                    widget.style.bottom = 'auto';
                });

                document.addEventListener('mouseup', function() {
                    if (isDragging) {
                        isDragging = false;
                        widget.style.transition = '';

                        // Save position
                        const rect = widget.getBoundingClientRect();
                        localStorage.setItem('chatbot_position', JSON.stringify({
                            left: rect.left,
                            top: rect.top
                        }));
                    }
                });

                // Touch support for mobile
                dragTarget.addEventListener('touchstart', function(e) {
                    if (e.target.tagName === 'BUTTON' || e.target.tagName === 'INPUT' ||
                        e.target.tagName === 'TEXTAREA' || e.target.closest('button')) {
                        return;
                    }

                    const touch = e.touches[0];
                    isDragging = true;
                    startX = touch.clientX;
                    startY = touch.clientY;

                    const rect = widget.getBoundingClientRect();
                    startLeft = rect.left;
                    startTop = rect.top;

                    widget.style.transition = 'none';
                }, { passive: true });

                document.addEventListener('touchmove', function(e) {
                    if (!isDragging) return;

                    const touch = e.touches[0];
                    const deltaX = touch.clientX - startX;
                    const deltaY = touch.clientY - startY;

                    let newLeft = startLeft + deltaX;
                    let newTop = startTop + deltaY;

                    const widgetRect = widget.getBoundingClientRect();
                    const maxLeft = window.innerWidth - widgetRect.width;
                    const maxTop = window.innerHeight - widgetRect.height;

                    newLeft = Math.max(0, Math.min(newLeft, maxLeft));
                    newTop = Math.max(0, Math.min(newTop, maxTop));

                    widget.style.position = 'fixed';
                    widget.style.left = newLeft + 'px';
                    widget.style.top = newTop + 'px';
                    widget.style.right = 'auto';
                    widget.style.bottom = 'auto';
                }, { passive: true });

                document.addEventListener('touchend', function() {
                    if (isDragging) {
                        isDragging = false;
                        widget.style.transition = '';

                        const rect = widget.getBoundingClientRect();
                        localStorage.setItem('chatbot_position', JSON.stringify({
                            left: rect.left,
                            top: rect.top
                        }));
                    }
                });

            }, 1000); // Wait for widget to load
        });
    </script>
    <script src="/js/chatbot-widget.js" defer></script>
    <?php endif; ?>
</body>
</html>
