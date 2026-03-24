-- Sample Data for Enhanced Course Builder
-- This script adds sample lessons, resources, assignments, and notes

USE it_hub_clean;

-- First, let's check if we have a course to work with
-- If not, we'll create a sample course
INSERT IGNORE INTO courses (id, title, description, instructor_id, category_id, price, difficulty_level, status, thumbnail, created_at, updated_at)
VALUES (
    1, 
    'Complete Web Development Bootcamp', 
    'Learn modern web development from scratch with HTML, CSS, JavaScript, React, Node.js and more.',
    1, 
    1, 
    4999.00, 
    'beginner', 
    'published', 
    'https://via.placeholder.com/400x300/4CAF50/FFFFFF?text=Web+Dev+Course',
    NOW(),
    NOW()
);

-- Sample Lessons
INSERT IGNORE INTO lessons (id, course_id, title, content, lesson_type, duration_minutes, is_free, lesson_order, video_url, created_at, updated_at) VALUES
(1, 1, 'Introduction to Web Development', 'Welcome to the world of web development! In this lesson, we''ll cover the fundamentals of how the web works and what you''ll learn in this course.', 'text', 30, 1, 1, NULL, NOW(), NOW()),
(2, 1, 'HTML5 Basics', 'Learn the building blocks of the web - HTML5. We''ll cover semantic HTML, forms, multimedia, and best practices.', 'video', 45, 1, 2, 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_1mb.mp4', NOW(), NOW()),
(3, 1, 'CSS3 Fundamentals', 'Master CSS3 to style your web pages. Learn about selectors, box model, flexbox, grid, animations, and responsive design.', 'video', 60, 0, 3, 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_2mb.mp4', NOW(), NOW()),
(4, 1, 'JavaScript Essentials', 'Dive into JavaScript programming. Learn variables, functions, objects, arrays, DOM manipulation, and modern ES6+ features.', 'video', 90, 0, 4, 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_5mb.mp4', NOW(), NOW()),
(5, 1, 'Building Your First Website', 'Project time! Apply everything you''ve learned to build your first complete website from scratch.', 'assignment', 120, 0, 5, NULL, NOW(), NOW());

-- Sample Resources
INSERT IGNORE INTO lesson_resources (lesson_id, instructor_id, title, description, resource_type, external_url, is_downloadable, sort_order, created_at) VALUES
(1, 1, 'Course Syllabus', 'Complete course outline and learning objectives', 'document', 'https://example.com/syllabus.pdf', 1, 1, NOW()),
(1, 1, 'Development Tools Setup Guide', 'Step-by-step guide to setting up your development environment', 'document', 'https://example.com/setup-guide.pdf', 1, 2, NOW()),
(2, 1, 'HTML5 Cheat Sheet', 'Quick reference for HTML5 tags and attributes', 'document', 'https://example.com/html5-cheatsheet.pdf', 1, 1, NOW()),
(2, 1, 'HTML5 Video Examples', 'Collection of HTML5 video implementation examples', 'link', 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/video', 0, 2, NOW()),
(3, 1, 'CSS3 Flexbox Guide', 'Comprehensive guide to CSS Flexbox layout', 'document', 'https://example.com/css-flexbox.pdf', 1, 1, NOW()),
(3, 1, 'CSS Grid Examples', 'Interactive examples of CSS Grid layouts', 'link', 'https://cssgrid.io/', 0, 2, NOW()),
(4, 1, 'JavaScript Reference', 'Complete JavaScript language reference', 'document', 'https://example.com/js-reference.pdf', 1, 1, NOW()),
(4, 1, 'ES6+ Features', 'Modern JavaScript features and examples', 'link', 'https://github.com/lukehoban/es6features', 0, 2, NOW()),
(5, 1, 'Project Templates', 'Starter templates for your first website project', 'other', 'https://example.com/project-templates.zip', 1, 1, NOW());

-- Sample Assignments
INSERT IGNORE INTO lesson_assignments (lesson_id, instructor_id, title, description, assignment_type, due_date, points_possible, instructions, is_published, sort_order, created_at, updated_at) VALUES
(1, 1, 'Web Development Goals', 'Set your learning goals for this course', 'assignment', DATE_ADD(NOW(), INTERVAL 7 DAY), 50, 'Write a brief essay (300-500 words) about what you hope to achieve in this course and how you plan to apply these skills.', 1, 1, NOW(), NOW()),
(2, 1, 'HTML5 Practice', 'Build a simple HTML page', 'assignment', DATE_ADD(NOW(), INTERVAL 14 DAY), 100, 'Create a personal portfolio page using HTML5. Include at least: header, navigation, main content section, and footer. Use semantic HTML5 tags appropriately.', 1, 1, NOW(), NOW()),
(3, 1, 'CSS3 Styling Challenge', 'Style the HTML page', 'assignment', DATE_ADD(NOW(), INTERVAL 21 DAY), 150, 'Take your HTML portfolio page and style it using CSS3. Include: responsive design, flexbox or grid layout, animations, and a color scheme.', 1, 1, NOW(), NOW()),
(4, 1, 'JavaScript Quiz', 'Test your JavaScript knowledge', 'quiz', DATE_ADD(NOW(), INTERVAL 28 DAY), 200, 'Complete the JavaScript quiz covering variables, functions, objects, arrays, and DOM manipulation. You have 60 minutes to complete 20 questions.', 1, 1, NOW(), NOW()),
(5, 1, 'Final Website Project', 'Build your complete website', 'project', DATE_ADD(NOW(), INTERVAL 42 DAY), 300, 'Create a complete multi-page website that showcases everything you''ve learned. Requirements: minimum 5 pages, responsive design, interactive elements, and modern design principles.', 1, 1, NOW(), NOW());

-- Sample Notes
INSERT IGNORE INTO lesson_notes (lesson_id, instructor_id, instructor_notes, study_materials, created_at, updated_at) VALUES
(1, 1, 'Welcome students! Make sure to emphasize the importance of understanding the fundamentals before moving to advanced topics. Encourage questions and create an interactive learning environment.', '1. Mozilla Developer Network (MDN) Web Docs\n2. W3Schools HTML Tutorial\n3. freeCodeCamp curriculum\n4. YouTube: Traversy Media, The Net Ninja\n5. Book: "HTML and CSS: Design and Build Websites" by Jon Duckett', NOW(), NOW()),
(2, 1, 'Focus on semantic HTML and accessibility. Show real-world examples and have students code along. Emphasize the importance of proper document structure.', '1. HTML5 Doctor\n2. A11y Project (Accessibility)\n3. CanIUse.com for browser support\n4. HTML5 Specification\n5. CodePen for live coding examples', NOW(), NOW()),
(3, 1, 'CSS can be challenging for beginners. Start with basics, then gradually introduce flexbox and grid. Lots of hands-on practice needed. Encourage experimentation.', '1. CSS Tricks\n2. Flexbox Froggy (game)\n3. Grid Garden (game)\n4. CSS Zen Garden\n5. Book: "CSS: The Definitive Guide" by Eric Meyer', NOW(), NOW()),
(4, 1, 'JavaScript is the most complex topic. Break it down into manageable chunks. Use lots of examples and encourage debugging skills. Introduce modern ES6+ features gradually.', '1. JavaScript.info\n2. Eloquent JavaScript (free book)\n3. JavaScript30 (30 day challenge)\n4. You Don''t Know JS (book series)\n5. MDN JavaScript Guide', NOW(), NOW()),
(5, 1, 'This is the capstone project. Provide clear guidelines but allow creativity. Encourage students to build something they''re proud of. Offer regular feedback and support.', '1. GitHub Pages for hosting\n2. Netlify or Vercel for deployment\n3. Figma for design mockups\n4. Lighthouse for performance testing\n5. Google Web Dev guidelines', NOW(), NOW());

-- Show sample data status
SELECT 
    'Sample data inserted successfully' as status,
    (SELECT COUNT(*) FROM lessons WHERE course_id = 1) as lessons_count,
    (SELECT COUNT(*) FROM lesson_resources WHERE lesson_id IN (SELECT id FROM lessons WHERE course_id = 1)) as resources_count,
    (SELECT COUNT(*) FROM lesson_assignments WHERE lesson_id IN (SELECT id FROM lessons WHERE course_id = 1)) as assignments_count,
    (SELECT COUNT(*) FROM lesson_notes WHERE lesson_id IN (SELECT id FROM lessons WHERE course_id = 1)) as notes_count;
