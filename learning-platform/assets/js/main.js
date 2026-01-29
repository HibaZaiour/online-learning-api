// Modal functionality
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = 'var(--danger)';
            isValid = false;
        } else {
            input.style.borderColor = 'var(--light-blue)';
        }
    });
    
    return isValid;
}

// Confirm delete
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Quiz timer
let quizTimer;
function startQuizTimer(duration, display) {
    let timer = duration;
    const minutes = display.querySelector('#minutes');
    const seconds = display.querySelector('#seconds');
    
    quizTimer = setInterval(function() {
        const mins = parseInt(timer / 60, 10);
        const secs = parseInt(timer % 60, 10);
        
        minutes.textContent = mins < 10 ? '0' + mins : mins;
        seconds.textContent = secs < 10 ? '0' + secs : secs;
        
        if (--timer < 0) {
            clearInterval(quizTimer);
            document.getElementById('quizForm').submit();
        }
    }, 1000);
}

// Answer selection styling
document.addEventListener('DOMContentLoaded', function() {
    const answerOptions = document.querySelectorAll('.answer-option');
    answerOptions.forEach(option => {
        option.addEventListener('click', function() {
            const input = this.querySelector('input[type="radio"], input[type="checkbox"]');
            if (input.type === 'radio') {
                // Remove selected class from siblings
                const siblings = this.parentElement.querySelectorAll('.answer-option');
                siblings.forEach(s => s.classList.remove('selected'));
            }
            this.classList.toggle('selected');
        });
    });
});

// File upload preview
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Search functionality
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    const filter = input.value.toUpperCase();
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        let txtValue = rows[i].textContent || rows[i].innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
            rows[i].style.display = '';
        } else {
            rows[i].style.display = 'none';
        }
    }
}

// Progress bar animation
function updateProgressBar(elementId, percentage) {
    const progressBar = document.getElementById(elementId);
    if (progressBar) {
        progressBar.style.width = percentage + '%';
    }
}