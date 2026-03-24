# IT HUB Student Dashboard - Full Stack Implementation

## Overview
Complete full-stack student dashboard with all necessary database tables, API endpoints, and frontend functionality.

## Database Tables (Auto-Created)
The dashboard automatically creates these tables on load:

1. **lessons** - Course lessons with duration and ordering
2. **lesson_progress** - Tracks student lesson completion and time spent
3. **quizzes** - Quiz information and settings
4. **quiz_attempts** - Student quiz attempts and scores
5. **notifications** - System notifications for students
6. **certificates** - Generated certificates for completed courses
7. **study_sessions** - Study time tracking
8. **completed_lessons** - Alternative lesson completion tracking

## Dashboard Features

### 1. Learning Overview
- **Active Courses**: Count of in-progress enrollments
- **Completed Courses**: Count of finished courses
- **Day Streak**: Consecutive days of learning activity
- **Study Hours**: Total time spent learning

### 2. Daily Focus
- Shows the most relevant lesson to continue
- Displays course thumbnail, title, and progress
- "Continue Learning" button for quick access

### 3. Continue Learning Section
- Table of enrolled courses with progress bars
- Direct links to continue each course
- Shows instructor and completion percentage

### 4. AI Recommendations
- KNN-based course recommendations
- Shows recommended courses with details
- "Explore" button to browse more

### 5. Pending Tasks
- Lists quizzes due for completion
- Shows quiz title and course name
- Links to take pending quizzes

### 6. Quick Stats Sidebar
- Day streak counter
- Total study hours
- Quick action buttons

## Universal Sidebar Navigation
All student pages now include:
- Dashboard
- Browse Courses
- My Courses
- Certificates
- Quiz Results
- Discussions
- Notifications
- Profile
- Settings
- Logout

## API Endpoints Used
- `getEnrolledCourses()` - Fetch student enrollments
- `getKNNRecommendations()` - AI course recommendations
- `getEnrollmentStats()` - Enrollment statistics
- `calculateCourseProgress()` - Real-time progress tracking
- `getStudyTime()` - Study time analytics

## File Structure
```
student/
├── dashboard.php          # Main dashboard with auto-table creation
├── my-courses.php         # Course management
├── courses.php           # Course catalog
├── certificates.php      # Certificate viewing
├── quiz-results.php     # Quiz performance
├── settings.php         # Account settings
├── profile.php          # User profile
├── certificate.php      # Certificate generation
├── course-details.php   # Course information
├── lesson.php           # Lesson viewing
└── database/
    └── dashboard_setup.sql    # Manual setup script
```

## Access
Dashboard URL: `http://localhost/store/student/dashboard.php`

## Testing
1. Login as student
2. Navigate to dashboard
3. Verify all stats load correctly
4. Check "Continue Learning" section
5. Test navigation sidebar links
6. Verify AI recommendations appear

## Troubleshooting
If dashboard shows errors:
1. Check database connection in config/config.php
2. Ensure user is logged in with student role
3. Verify tables were created successfully
4. Check error logs for specific issues
