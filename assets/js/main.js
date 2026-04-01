// IT HUB JavaScript Functions

// Determine base path for API calls
var basePath = '';
var currentPath = window.location.pathname;
if (currentPath.includes('/student/') || currentPath.includes('/instructor/') || currentPath.includes('/admin/')) {
    basePath = '../';
} else {
    basePath = '';
}

$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Confirm delete actions
    $('.delete-btn').click(function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
            return false;
        }
    });

    // File upload preview
    $('.file-upload input[type="file"]').change(function(e) {
        var file = e.target.files[0];
        var reader = new FileReader();
        
        reader.onload = function(e) {
            $('.file-upload-preview').html('<img src="' + e.target.result + '" class="img-thumbnail" style="max-width: 200px;">');
        };
        
        if (file) {
            reader.readAsDataURL(file);
        }
    });

    // Drag and drop file upload
    $('.file-upload').on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('dragover');
    });

    $('.file-upload').on('dragleave', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
    });

    $('.file-upload').on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
        
        var files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            $(this).find('input[type="file"]')[0].files = files;
            $(this).find('input[type="file"]').trigger('change');
        }
    });

    // Live search
    $('.live-search').on('input', function() {
        var query = $(this).val();
        var searchUrl = $(this).data('search-url');
        var resultsContainer = $($(this).data('results-container'));
        
        if (query.length < 2) {
            resultsContainer.empty();
            return;
        }
        
        $.ajax({
            url: searchUrl,
            type: 'GET',
            data: { q: query },
            success: function(data) {
                resultsContainer.html(data);
            },
            error: function() {
                resultsContainer.html('<div class="alert alert-danger">Error loading search results</div>');
            }
        });
    });

    // Load more pagination
    $('.load-more').click(function(e) {
        e.preventDefault();
        var btn = $(this);
        var page = parseInt(btn.data('page')) + 1;
        var url = btn.data('url');
        var container = $(btn.data('container'));
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');
        
        $.ajax({
            url: url,
            type: 'GET',
            data: { page: page },
            success: function(data) {
                container.append(data);
                btn.data('page', page);
                btn.prop('disabled', false).html('Load More');
                
                if (data.trim() === '') {
                    btn.hide();
                }
            },
            error: function() {
                btn.prop('disabled', false).html('Load More');
                showAlert('Error loading more content', 'danger');
            }
        });
    });

    // Form validation
    $('form[data-validate]').submit(function(e) {
        var form = $(this);
        var isValid = true;
        
        form.find('[required]').each(function() {
            if ($(this).val().trim() === '') {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        // Email validation
        form.find('input[type="email"]').each(function() {
            var email = $(this).val();
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!emailRegex.test(email)) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        // Password confirmation
        var password = form.find('#password').val();
        var confirmPassword = form.find('#confirm_password').val();
        
        if (password && confirmPassword && password !== confirmPassword) {
            form.find('#confirm_password').addClass('is-invalid');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            showAlert('Please correct the errors in the form', 'danger');
        }
    });

    // Clear validation on input
    $('input, select, textarea').on('input', function() {
        $(this).removeClass('is-invalid');
    });

    // AJAX form submission
    $('.ajax-form').submit(function(e) {
        e.preventDefault();
        
        var form = $(this);
        var url = form.attr('action');
        var method = form.attr('method') || 'POST';
        var data = new FormData(form[0]);
        var submitBtn = form.find('button[type="submit"]');
        var originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        $.ajax({
            url: url,
            type: method,
            data: data,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert(response.message || 'Operation successful', 'success');
                    
                    if (response.redirect) {
                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 1500);
                    } else if (response.reload) {
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        form[0].reset();
                        if (response.callback) {
                            window[response.callback](response);
                        }
                    }
                } else {
                    showAlert(response.message || 'Operation failed', 'danger');
                }
            },
            error: function(xhr) {
                var message = 'An error occurred';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showAlert(message, 'danger');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Toggle password visibility
    $('.toggle-password').click(function() {
        var input = $($(this).data('target'));
        var icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Copy to clipboard
    $('.copy-btn').click(function() {
        var text = $(this).data('text');
        var btn = $(this);
        
        navigator.clipboard.writeText(text).then(function() {
            var originalText = btn.html();
            btn.html('<i class="fas fa-check"></i> Copied!');
            
            setTimeout(function() {
                btn.html(originalText);
            }, 2000);
        });
    });

    // Print functionality
    $('.print-btn').click(function() {
        window.print();
    });

    // Export to CSV
    $('.export-csv').click(function() {
        var table = $($(this).data('table'));
        var csv = [];
        
        // Get headers
        var headers = [];
        table.find('thead th').each(function() {
            headers.push($(this).text().trim());
        });
        csv.push(headers.join(','));
        
        // Get rows
        table.find('tbody tr').each(function() {
            var row = [];
            $(this).find('td').each(function() {
                row.push('"' + $(this).text().trim() + '"');
            });
            csv.push(row.join(','));
        });
        
        // Download CSV
        var csvContent = csv.join('\n');
        var blob = new Blob([csvContent], { type: 'text/csv' });
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'export.csv';
        a.click();
        window.URL.revokeObjectURL(url);
    });

    // Real-time clock
    function updateClock() {
        var now = new Date();
        var time = now.toLocaleTimeString();
        var date = now.toLocaleDateString();
        
        $('.current-time').text(time);
        $('.current-date').text(date);
    }
    
    setInterval(updateClock, 1000);
    updateClock();

    // Notification system
    function checkNotifications() {
        $.ajax({
            url: basePath + 'api/notifications.php',
            type: 'GET',
            success: function(data) {
                if (data.count > 0) {
                    $('.notification-badge').text(data.count).show();
                } else {
                    $('.notification-badge').hide();
                }
            }
        });
    }
    
    // Check notifications every 30 seconds - only if notification badge exists
    if ($('.notification-badge').length > 0) {
        setInterval(checkNotifications, 30000);
        checkNotifications();
    }

    // Chat functionality
    function sendMessage() {
        var message = $('#chatMessage').val().trim();
        var receiverId = $('#receiverId').val();
        var courseId = $('#courseId').val();
        
        if (message === '') return;
        
        $.ajax({
            url: basePath + 'api/send_message.php',
            type: 'POST',
            data: {
                message: message,
                receiver_id: receiverId,
                course_id: courseId
            },
            success: function(response) {
                if (response.success) {
                    $('#chatMessage').val('');
                    loadMessages();
                } else {
                    showAlert(response.message, 'danger');
                }
            }
        });
    }
    
    function loadMessages() {
        var receiverId = $('#receiverId').val();
        var courseId = $('#courseId').val();
        
        // Check if required elements exist
        if (!receiverId || !courseId) {
            return; // Not on messaging page
        }
        
        $.ajax({
            url: basePath + 'api/get_messages.php',
            type: 'GET',
            data: {
                receiver_id: receiverId,
                course_id: courseId
            },
            success: function(data) {
                var chatMessages = $('#chatMessages');
                if (chatMessages.length > 0) {
                    chatMessages.html(data);
                    scrollToBottom();
                }
            }
        });
    }
    
    function scrollToBottom() {
        var chatMessages = $('#chatMessages');
        if (chatMessages.length > 0 && chatMessages[0]) {
            chatMessages.scrollTop(chatMessages[0].scrollHeight);
        }
    }
    
    // Send message on Enter key
    $('#chatMessage').keypress(function(e) {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    // Load messages every 5 seconds - only on messaging pages
    if ($('#chatMessages').length > 0) {
        setInterval(loadMessages, 5000);
    }
    
    // Scroll Reveal Animation
    function reveal() {
        var reveals = document.querySelectorAll('.reveal');
        
        reveals.forEach(function(element) {
            var elementTop = element.getBoundingClientRect().top;
            var elementVisible = 150;
            
            if (elementTop < window.innerHeight - elementVisible) {
                element.classList.add('active');
            }
        });
    }
    
    window.addEventListener('scroll', reveal);
    reveal(); // Initial check
    
    // Parallax scrolling effect
    function parallax() {
        var parallaxElements = document.querySelectorAll('.parallax');
        
        parallaxElements.forEach(function(element) {
            var speed = element.dataset.speed || 0.5;
            var yPos = -(window.pageYOffset * speed);
            
            element.style.transform = 'translateY(' + yPos + 'px)';
        });
    }
    
    window.addEventListener('scroll', parallax);
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            var href = this.getAttribute('href');
            
            // Skip if href is empty or just '#'
            if (!href || href === '#') {
                return;
            }
            
            var targetId = href.substring(1); // Remove the # prefix
            
            // Skip if targetId is empty
            if (!targetId) {
                return;
            }
            
            var target = document.getElementById(targetId);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Animated counter for statistics
    function animateCounter() {
        var counters = document.querySelectorAll('.stat-card h3');
        
        counters.forEach(function(counter) {
            var target = parseInt(counter.innerText.replace(/[^0-9]/g, ''));
            var increment = target / 100;
            var current = 0;
            
            var timer = setInterval(function() {
                current += increment;
                
                if (current >= target) {
                    counter.innerText = counter.innerText.replace(/[0-9]+/, target);
                    clearInterval(timer);
                } else {
                    counter.innerText = counter.innerText.replace(/[0-9]+/, Math.floor(current));
                }
            }, 20);
        });
    }
    
    // Trigger counter animation when stats section is visible
    var statsSection = document.querySelector('.stat-card');
    if (statsSection) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    animateCounter();
                    observer.unobserve(entry.target);
                }
            });
        });
        
        observer.observe(statsSection);
    }
    
    // Add hover effect to cards
    document.querySelectorAll('.card, .feature-card').forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Button ripple effect
    document.querySelectorAll('.btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
            var ripple = document.createElement('span');
            var rect = this.getBoundingClientRect();
            var size = Math.max(rect.width, rect.height);
            var x = e.clientX - rect.left - size / 2;
            var y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            this.appendChild(ripple);
            
            setTimeout(function() {
                ripple.remove();
            }, 600);
        });
    });
});

// Utility functions
function showAlert(message, type) {
    var alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Remove existing alerts
    $('.alert').fadeOut('fast', function() {
        $(this).remove();
    });
    
    // Add new alert
    $('main .container').prepend(alertHtml);
    
    // Auto-hide after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
}

function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

function formatDate(dateString) {
    var date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

function slugify(text) {
    return text.toString().toLowerCase()
        .replace(/\s+/g, '-')           // Replace spaces with -
        .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
        .replace(/\-\-+/g, '-')         // Replace multiple - with single -
        .replace(/^-+/, '')             // Trim - from start of text
        .replace(/-+$/, '');            // Trim - from end of text
}

// Chart initialization (if Chart.js is available)
if (typeof Chart !== 'undefined') {
    function createChart(canvasId, type, data, options) {
        var ctx = document.getElementById(canvasId).getContext('2d');
        return new Chart(ctx, {
            type: type,
            data: data,
            options: options
        });
    }
}
