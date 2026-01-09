}
        });
    };
    
    window.refreshDiscussions = function() {
        showNotification('Refreshing discussions...', 'info');
        setTimeout(() => location.reload(), 1000);
    };
    
    window.toggleSelectAll = function() {
        const checkboxes = document.querySelectorAll('.discussion-checkbox');
        const selectAll = document.getElementById('selectAll');
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
        });
        
        updateBulkActions();
    };
    
    window.updateBulkActions = function() {
        const checkboxes = document.querySelectorAll('.discussion-checkbox:checked');
        const bulkActions = document.getElementById('bulkActions');
        const selectedCount = document.getElementById('selectedCount');
        
        selectedCount.textContent = checkboxes.length;
        
        if (checkboxes.length > 0) {
            bulkActions.classList.add('show');
        } else {
            bulkActions.classList.remove('show');
        }
    };
    
    window.executeBulkAction = function() {
        const checkboxes = document.querySelectorAll('.discussion-checkbox:checked');
        const bulkAction = document.getElementById('bulkActionSelect').value;
        
        if (checkboxes.length === 0 || !bulkAction) {
            showNotification('Please select discussions and choose an action', 'warning');
            return;
        }
        
        const discussionIds = Array.from(checkboxes).map(cb => cb.value);
        
        if (!confirm(`Are you sure you want to ${bulkAction.replace('_', ' ')} ${checkboxes.length} discussions?`)) {
            return;
        }
        
        $.ajax({
            url: 'discussions.php',
            type: 'POST',
            data: {
                action: 'bulk_action',
                discussion_ids: discussionIds,
                bulk_action: bulkAction
            },
            success: function(response) {
                showNotification('Bulk action completed successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            },
            error: function() {
                showNotification('Error executing bulk action', 'error');
            }
        });
    };
    
    window.clearSelection = function() {
        document.querySelectorAll('.discussion-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('selectAll').checked = false;
        updateBulkActions();
    };
    
    window.clearFilters = function() {
        window.location.href = 'discussions.php';
    };
    
    window.showNotification = function(message, type = 'info') {
        const alertClass = type === 'success' ? 'alert-success' : 
                         type === 'error' ? 'alert-danger' : 
                         type === 'warning' ? 'alert-warning' : 'alert-info';
        
        const notification = $(`
            <div class="notification-toast ${alertClass}">
                <div class="d-flex align-items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 
                                     type === 'error' ? 'exclamation-triangle' : 
                                     type === 'warning' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                    <div class="flex-grow-1">${message}</div>
                    <button class="btn-close ms-2" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            </div>
        `);
        
        $('body').append(notification);
        
        setTimeout(() => {
            notification.fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
    };
    
    // Auto-refresh every 30 seconds for real-time updates
    setInterval(function() {
        if (document.visibilityState === 'visible') {
            // Check for new discussions silently
            $.ajax({
                url: '../api/check_new_discussions.php',
                type: 'GET',
                success: function(data) {
                    if (data.new_count > 0) {
                        showNotification(`${data.new_count} new discussion(s) available`, 'info');
                    }
                }
            });
        }
    }, 30000);
    
    // Keyboard shortcuts
    $(document).keydown(function(e) {
        // Ctrl+R for refresh
        if (e.ctrlKey && e.key === 'r') {
            e.preventDefault();
            refreshDiscussions();
        }
        // Ctrl+A for select all
        if (e.ctrlKey && e.key === 'a') {
            e.preventDefault();
            document.getElementById('selectAll').checked = true;
            toggleSelectAll();
        }
        // Escape to clear selection
        if (e.key === 'Escape') {
            clearSelection();
        }
    });
    
    // Search functionality with debouncing
    let searchTimeout;
    $('#searchInput').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            $('#filterForm').submit();
        }, 500);
    });
    
    // Infinite scroll for pagination
    let loading = false;
    let currentPage = <?php echo $page; ?>;
    
    $(window).scroll(function() {
        if ($(window).scrollTop() + $(window).height() > $(document).height() - 100 && !loading) {
            loading = true;
            // Load more discussions
            $.ajax({
                url: '../api/load_more_discussions.php',
                type: 'GET',
                data: { 
                    page: currentPage + 1,
                    filters: $('#filterForm').serialize()
                },
                success: function(data) {
                    if (data.discussions && data.discussions.length > 0) {
                        data.discussions.forEach(discussion => {
                            appendDiscussionCard(discussion);
                        });
                        currentPage++;
                    }
                    loading = false;
                }
            });
        }
    });
    
    window.appendDiscussionCard = function(discussion) {
        const cardHtml = `
            <div class="discussion-card mb-3 ${discussion.is_pinned ? 'pinned' : ''}" style="opacity: 0; transform: translateY(20px);">
                <div class="card-body">
                    <!-- Card content here -->
                </div>
            </div>
        `;
        
        $('.discussions-list').append(cardHtml);
        $('.discussion-card:last').animate({
            opacity: 1,
            transform: 'translateY(0)'
        }, 500);
    };
});
</script>
</body>
</html>
