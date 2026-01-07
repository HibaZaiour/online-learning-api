// Course Details JavaScript

const API_BASE_URL = window.location.origin;

let course = null;
let lessons = [];
let courseProgress = null;
let completedLessonIds = [];

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const courseId = urlParams.get('course_id');

    if (!courseId) {
        showError('Course ID is missing.');
        return;
    }

    loadCourseDetails(courseId);
    loadLessons(courseId);
    checkAuthAndLoadProgress(courseId);
});

// Check authentication and load progress
async function checkAuthAndLoadProgress(courseId) {
    const token = localStorage.getItem('token');
    if (token) {
        await loadCourseProgress(courseId);
    }
}

// Load course details
async function loadCourseDetails(courseId) {
    showLoading();
    hideError();

    try {
        const response = await fetch(`${API_BASE_URL}/api/courses/read.php?course_id=${courseId}`);
        
        if (!response.ok) {
            if (response.status === 404) {
                throw new Error('Course not found.');
            }
            throw new Error('Failed to load course details.');
        }
        
        course = await response.json();
        displayCourseDetails(course);
    } catch (error) {
        console.error('Error loading course details:', error);
        showError(error.message);
    } finally {
        hideLoading();
    }
}

// Load lessons
async function loadLessons(courseId) {
    try {
        const response = await fetch(`${API_BASE_URL}/api/courses/lessons.php?course_id=${courseId}`);
        
        if (!response.ok) throw new Error('Failed to load lessons');
        
        lessons = await response.json();
        displayLessons(lessons);
    } catch (error) {
        console.error('Error loading lessons:', error);
    }
}

// Load course progress
async function loadCourseProgress(courseId) {
    const token = localStorage.getItem('token');
    if (!token) return;

    try {
        const response = await fetch(`${API_BASE_URL}/api/courses/progress.php?course_id=${courseId}`, {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });

        if (response.ok) {
            courseProgress = await response.json();
            completedLessonIds = courseProgress.completed_lessons || [];
            updateUIWithProgress();
        }
    } catch (error) {
        console.error('Error loading course progress:', error);
    }
}

// Display course details
function displayCourseDetails(course) {
    const courseDetails = document.getElementById('courseDetails');
    if (!courseDetails) return;

    courseDetails.style.display = 'block';

    // Set course image
    const courseImage = document.getElementById('courseImage');
    if (courseImage) {
        courseImage.src = course.image_url || 'https://via.placeholder.com/300x200?text=Course';
        courseImage.onerror = function() {
            this.src = 'https://via.placeholder.com/300x200?text=Course';
        };
    }

    // Set course title
    const courseTitle = document.getElementById('courseTitle');
    if (courseTitle) {
        courseTitle.textContent = course.title || 'Course Title';
    }

    // Set course instructor
    const courseInstructor = document.getElementById('courseInstructor');
    if (courseInstructor) {
        courseInstructor.textContent = `Instructor: ${course.instructor || 'TBA'}`;
    }

    // Set course category
    const courseCategory = document.getElementById('courseCategory');
    if (courseCategory) {
        courseCategory.textContent = course.category_name || 'Uncategorized';
    }

    // Set course difficulty
    const courseDifficulty = document.getElementById('courseDifficulty');
    if (courseDifficulty) {
        const difficultyClass = course.difficulty ? course.difficulty.toLowerCase() : 'beginner';
        courseDifficulty.textContent = course.difficulty || 'Beginner';
        courseDifficulty.className = `course-difficulty badge-${difficultyClass}`;
    }

    // Set course description
    const courseDescription = document.getElementById('courseDescription');
    if (courseDescription) {
        courseDescription.textContent = course.description || 'No description available.';
    }

    // Set enrollment count
    const enrollmentCount = document.getElementById('enrollmentCount');
    if (enrollmentCount) {
        const count = course.enrollment_count || 0;
        enrollmentCount.textContent = `${count} ${count === 1 ? 'student enrolled' : 'students enrolled'}`;
    }

    // Setup enroll button
    setupEnrollButton();
}

// Setup enroll button
function setupEnrollButton() {
    const enrollBtn = document.getElementById('enrollBtn');
    const startLearningBtn = document.getElementById('startLearningBtn');
    const continueLearningBtn = document.getElementById('continueLearningBtn');

    if (courseProgress && courseProgress.enrolled) {
        // User is enrolled
        if (enrollBtn) enrollBtn.style.display = 'none';
        
        const progress = courseProgress.progress_percentage || 0;
        if (progress === 0) {
            if (startLearningBtn) {
                startLearningBtn.style.display = 'block';
                startLearningBtn.onclick = function() {
                    if (lessons.length > 0) {
                        window.location.href = `#lesson-${lessons[0].lesson_id}`;
                    }
                };
            }
        } else {
            if (continueLearningBtn) {
                continueLearningBtn.style.display = 'block';
                continueLearningBtn.onclick = function() {
                    // Find first incomplete lesson
                    const incompleteLesson = lessons.find(lesson => 
                        !completedLessonIds.includes(lesson.lesson_id)
                    );
                    if (incompleteLesson) {
                        window.location.href = `#lesson-${incompleteLesson.lesson_id}`;
                    } else if (lessons.length > 0) {
                        window.location.href = `#lesson-${lessons[0].lesson_id}`;
                    }
                };
            }
        }
    } else {
        // User is not enrolled
        if (startLearningBtn) startLearningBtn.style.display = 'none';
        if (continueLearningBtn) continueLearningBtn.style.display = 'none';
        
        if (enrollBtn) {
            enrollBtn.style.display = 'block';
            enrollBtn.onclick = function() {
                enrollInCourse();
            };
        }
    }
}

// Enroll in course
async function enrollInCourse() {
    const token = localStorage.getItem('token');
    
    if (!token) {
        alert('Please log in to enroll in this course.');
        window.location.href = 'login.html';
        return;
    }

    const enrollBtn = document.getElementById('enrollBtn');
    if (!enrollBtn) return;

    const originalText = enrollBtn.textContent;
    enrollBtn.disabled = true;
    enrollBtn.textContent = 'Enrolling...';

    try {
        const response = await fetch(`${API_BASE_URL}/api/courses/enroll.php?course_id=${course.course_id}`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();

        if (response.ok) {
            alert('Successfully enrolled in the course!');
            // Reload progress to update UI
            await loadCourseProgress(course.course_id);
            setupEnrollButton();
        } else {
            alert(data.message || 'Failed to enroll in the course.');
            enrollBtn.disabled = false;
            enrollBtn.textContent = originalText;
        }
    } catch (error) {
        console.error('Error enrolling in course:', error);
        alert('An error occurred. Please try again.');
        enrollBtn.disabled = false;
        enrollBtn.textContent = originalText;
    }
}

// Display lessons
function displayLessons(lessonsToDisplay) {
    const lessonsList = document.getElementById('lessonsList');
    if (!lessonsList) return;

    lessonsList.innerHTML = '';

    if (lessonsToDisplay.length === 0) {
        lessonsList.innerHTML = '<p style="color: var(--text-light); padding: 2rem;">No lessons available for this course.</p>';
        return;
    }

    lessonsToDisplay.forEach((lesson, index) => {
        const lessonItem = createLessonItem(lesson, index + 1);
        lessonsList.appendChild(lessonItem);
    });
}

// Create lesson item
function createLessonItem(lesson, number) {
    const item = document.createElement('div');
    item.className = 'lesson-item';
    item.id = `lesson-${lesson.lesson_id}`;

    const isCompleted = completedLessonIds.includes(lesson.lesson_id);
    if (isCompleted) {
        item.classList.add('completed');
    }

    const duration = lesson.duration ? `${lesson.duration} min` : 'N/A';

    item.innerHTML = `
        <div class="lesson-info">
            <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                <span class="lesson-number">${number}</span>
                <h3 class="lesson-title">${escapeHtml(lesson.title || 'Untitled Lesson')}</h3>
            </div>
            ${lesson.description ? `<p class="lesson-description">${escapeHtml(lesson.description)}</p>` : ''}
            <div class="lesson-meta">
                <span>Duration: ${duration}</span>
            </div>
        </div>
        <div class="lesson-actions">
            ${isCompleted ? 
                '<span class="lesson-completed-icon">✓</span>' : 
                `<button class="btn-complete-lesson" onclick="markLessonComplete(${lesson.lesson_id}, this)">
                    Mark as Complete
                </button>`
            }
        </div>
    `;

    return item;
}

// Mark lesson as complete
async function markLessonComplete(lessonId, button) {
    const token = localStorage.getItem('token');
    
    if (!token) {
        alert('Please log in to mark lessons as complete.');
        window.location.href = 'login.html';
        return;
    }

    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Completing...';

    try {
        const response = await fetch(`${API_BASE_URL}/api/lessons/complete.php?lesson_id=${lessonId}`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();

        if (response.ok) {
            // Update UI
            completedLessonIds.push(lessonId);
            const lessonItem = button.closest('.lesson-item');
            if (lessonItem) {
                lessonItem.classList.add('completed');
                button.outerHTML = '<span class="lesson-completed-icon">✓</span>';
            }

            // Reload progress to update enrollment progress
            await loadCourseProgress(course.course_id);
            setupEnrollButton();
        } else {
            alert(data.message || 'Failed to mark lesson as complete.');
            button.disabled = false;
            button.textContent = originalText;
        }
    } catch (error) {
        console.error('Error marking lesson as complete:', error);
        alert('An error occurred. Please try again.');
        button.disabled = false;
        button.textContent = originalText;
    }
}

// Update UI with progress
function updateUIWithProgress() {
    if (!courseProgress || !courseProgress.enrolled) return;

    // Re-render lessons with completion status
    displayLessons(lessons);
    setupEnrollButton();
}

// UI State Management
function showLoading() {
    const loadingState = document.getElementById('loadingState');
    if (loadingState) loadingState.style.display = 'flex';
    const courseDetails = document.getElementById('courseDetails');
    if (courseDetails) courseDetails.style.display = 'none';
}

function hideLoading() {
    const loadingState = document.getElementById('loadingState');
    if (loadingState) loadingState.style.display = 'none';
}

function showError(message) {
    const errorState = document.getElementById('errorState');
    if (errorState) {
        errorState.style.display = 'block';
        if (message) {
            errorState.querySelector('p').textContent = message;
        }
    }
}

function hideError() {
    const errorState = document.getElementById('errorState');
    if (errorState) errorState.style.display = 'none';
}

// Utility function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}


