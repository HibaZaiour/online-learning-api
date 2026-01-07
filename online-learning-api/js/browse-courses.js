// Browse Courses JavaScript

const API_BASE_URL = window.location.origin;
let allCourses = [];

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadCourses();
    setupFilterListener();
});

// Setup filter listener
function setupFilterListener() {
    const difficultyFilter = document.getElementById('difficultyFilter');
    if (difficultyFilter) {
        difficultyFilter.addEventListener('change', function() {
            filterCourses(this.value);
        });
    }
}

// Load courses from API
async function loadCourses() {
    const loadingState = document.getElementById('loadingState');
    const coursesList = document.getElementById('coursesList');
    const emptyState = document.getElementById('emptyState');

    if (loadingState) loadingState.style.display = 'block';
    if (coursesList) coursesList.innerHTML = '';
    if (emptyState) emptyState.style.display = 'none';

    try {
        const response = await fetch(`${API_BASE_URL}/api/courses/index.php`);
        
        if (!response.ok) {
            throw new Error('Failed to load courses');
        }
        
        allCourses = await response.json();
        displayCourses(allCourses);
    } catch (error) {
        console.error('Error loading courses:', error);
        if (coursesList) {
            coursesList.innerHTML = '<li style="padding: 2rem; text-align: center; color: var(--text-light);">Failed to load courses. Please try again later.</li>';
        }
    } finally {
        if (loadingState) loadingState.style.display = 'none';
    }
}

// Filter courses by difficulty
function filterCourses(difficulty) {
    const coursesList = document.getElementById('coursesList');
    const emptyState = document.getElementById('emptyState');

    if (!difficulty || difficulty === '') {
        // Show all courses
        displayCourses(allCourses);
        return;
    }

    // Filter courses by difficulty
    const filtered = allCourses.filter(course => course.difficulty === difficulty);
    displayCourses(filtered);
}

// Display courses
function displayCourses(courses) {
    const coursesList = document.getElementById('coursesList');
    const emptyState = document.getElementById('emptyState');

    if (!coursesList) return;

    coursesList.innerHTML = '';

    if (courses.length === 0) {
        if (emptyState) emptyState.style.display = 'block';
        return;
    }

    if (emptyState) emptyState.style.display = 'none';

    courses.forEach(course => {
        const listItem = document.createElement('li');
        listItem.className = 'course-item';

        const difficultyClass = course.difficulty ? `difficulty-${course.difficulty.toLowerCase()}` : 'difficulty-easy';

        listItem.innerHTML = `
            <span class="course-name">${escapeHtml(course.title)}</span>
            <span class="course-difficulty ${difficultyClass}">${escapeHtml(course.difficulty || 'Easy')}</span>
        `;

        coursesList.appendChild(listItem);
    });
}

// Utility function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
