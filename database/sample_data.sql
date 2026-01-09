-- Additional sample data for IT HUB platform

-- Insert more sample courses
INSERT INTO courses (title, description, category_id, instructor_id, price, duration_hours, difficulty_level, status) VALUES
('Web Development Fundamentals', 'Learn HTML, CSS, and JavaScript from scratch. Build responsive websites and understand modern web development practices.', 3, 2, 49.99, 40, 'beginner', 'published'),
('Advanced PHP Programming', 'Master PHP with advanced concepts including OOP, MVC patterns, and framework development.', 2, 2, 79.99, 60, 'advanced', 'published'),
('Database Design and SQL', 'Learn database design principles and SQL programming. Work with MySQL and understand relational databases.', 4, 1, 59.99, 45, 'intermediate', 'published'),
('Cybersecurity Essentials', 'Introduction to cybersecurity concepts, ethical hacking, and security best practices for IT professionals.', 5, 1, 89.99, 50, 'intermediate', 'published'),
('Mobile App Development with React Native', 'Build cross-platform mobile applications using React Native framework.', 6, 2, 99.99, 70, 'advanced', 'published'),
('Cloud Computing with AWS', 'Master Amazon Web Services and cloud computing concepts for modern IT infrastructure.', 7, 1, 119.99, 80, 'advanced', 'published'),
('Data Science with Python', 'Learn data analysis, visualization, and machine learning using Python and popular libraries.', 8, 2, 109.99, 90, 'advanced', 'published'),
('Network Administration', 'Comprehensive guide to network administration, protocols, and troubleshooting.', 1, 1, 69.99, 55, 'intermediate', 'published');

-- Insert sample lessons for Web Development Fundamentals
INSERT INTO lessons (course_id, title, content, lesson_order, lesson_type, duration_minutes, is_free) VALUES
(4, 'Introduction to HTML', 'Learn the basics of HTML5, semantic tags, and document structure.', 1, 'text', 30, TRUE),
(4, 'HTML Forms and Input', 'Master HTML forms, input types, validation, and user interaction.', 2, 'text', 45, FALSE),
(4, 'CSS Fundamentals', 'Introduction to CSS, selectors, properties, and basic styling.', 3, 'text', 40, TRUE),
(4, 'Responsive Design with Flexbox', 'Learn modern CSS layout techniques with Flexbox for responsive designs.', 4, 'text', 50, FALSE),
(4, 'JavaScript Basics', 'Introduction to JavaScript programming, variables, functions, and DOM manipulation.', 5, 'text', 60, FALSE),
(4, 'Building Your First Website', 'Combine HTML, CSS, and JavaScript to build a complete website project.', 6, 'text', 90, FALSE);

-- Insert sample lessons for Advanced PHP Programming
INSERT INTO lessons (course_id, title, content, lesson_order, lesson_type, duration_minutes, is_free) VALUES
(5, 'Object-Oriented PHP', 'Master OOP concepts in PHP including classes, inheritance, and polymorphism.', 1, 'text', 60, TRUE),
(5, 'MVC Architecture', 'Understanding and implementing Model-View-Controller pattern in PHP.', 2, 'text', 75, FALSE),
(5, 'Database Integration', 'Advanced database operations with PDO and prepared statements.', 3, 'text', 65, FALSE),
(5, 'REST API Development', 'Building RESTful APIs with PHP for modern web applications.', 4, 'text', 80, FALSE),
(5, 'Security Best Practices', 'Implementing security measures including authentication, authorization, and data protection.', 5, 'text', 70, FALSE),
(5, 'Performance Optimization', 'Techniques for optimizing PHP applications for better performance.', 6, 'text', 55, FALSE);

-- Insert sample quizzes
INSERT INTO quizzes (course_id, title, description, time_limit_minutes, passing_score, max_attempts, status) VALUES
(4, 'HTML & CSS Basics Quiz', 'Test your knowledge of HTML and CSS fundamentals covered in the first few lessons.', 30, 70.00, 3, 'published'),
(4, 'JavaScript Fundamentals Quiz', 'Evaluate your understanding of JavaScript basics and DOM manipulation.', 45, 75.00, 3, 'published'),
(5, 'PHP OOP Quiz', 'Test your knowledge of object-oriented programming concepts in PHP.', 40, 80.00, 3, 'published'),
(5, 'MVC Architecture Quiz', 'Evaluate your understanding of MVC pattern and its implementation.', 35, 75.00, 3, 'published');

-- Insert sample quiz questions
INSERT INTO quiz_questions (quiz_id, question_text, question_type, points, question_order) VALUES
(1, 'What does HTML stand for?', 'multiple_choice', 1.00, 1),
(1, 'Which CSS property is used to change the text color of an element?', 'multiple_choice', 1.00, 2),
(1, 'What is the purpose of the DOCTYPE declaration in HTML?', 'multiple_choice', 1.00, 3),
(1, 'Which HTML5 element is used for navigation links?', 'multiple_choice', 1.00, 4),
(1, 'CSS Flexbox is used for:', 'multiple_choice', 1.00, 5);

-- Insert quiz options for first quiz
INSERT INTO quiz_options (question_id, option_text, is_correct, option_order) VALUES
(1, 'Hyper Text Markup Language', TRUE, 1),
(1, 'High Tech Modern Language', FALSE, 2),
(1, 'Home Tool Markup Language', FALSE, 3),
(1, 'Hyperlinks and Text Markup Language', FALSE, 4),
(2, 'text-color', FALSE, 1),
(2, 'color', TRUE, 2),
(2, 'font-color', FALSE, 3),
(2, 'text-style', FALSE, 4),
(3, 'To specify the HTML version', TRUE, 1),
(3, 'To create comments', FALSE, 2),
(3, 'To link CSS files', FALSE, 3),
(3, 'To define metadata', FALSE, 4),
(4, '<navigation>', FALSE, 1),
(4, '<nav>', TRUE, 2),
(4, '<menu>', FALSE, 3),
(4, '<navbar>', FALSE, 4),
(5, 'Creating animations', FALSE, 1),
(5, 'Creating flexible layouts', TRUE, 2),
(5, 'Adding colors', FALSE, 3),
(5, 'Creating shadows', FALSE, 4);

-- Insert sample discussions
INSERT INTO discussions (course_id, student_id, title, content) VALUES
(4, 3, 'Best practices for responsive design?', 'I''m working on making my website responsive. What are the best practices for breakpoints and mobile-first design?'),
(4, 4, 'JavaScript frameworks vs vanilla JS', 'Should I learn a framework like React or focus on vanilla JavaScript first as a beginner?'),
(5, 3, 'When to use static vs instance methods?', 'I''m confused about when to use static methods versus instance methods in PHP OOP. Can someone explain?'),
(5, 4, 'Best MVC framework for PHP?', 'Which MVC framework would you recommend for a beginner: Laravel, Symfony, or CodeIgniter?');

-- Insert sample feedback
INSERT INTO feedback (course_id, student_id, instructor_id, rating, review) VALUES
(4, 3, 2, 5, 'Excellent course! The instructor explains concepts clearly and the projects are very practical.'),
(4, 4, 2, 4, 'Great content and well-structured. Would love to see more advanced topics covered.'),
(5, 3, 2, 5, 'Perfect for understanding PHP OOP. The examples are real-world and very helpful.'),
(5, 4, 2, 4, 'Comprehensive coverage of MVC. Could use more hands-on exercises though.');

-- Insert sample enrollments
INSERT INTO enrollments (student_id, course_id, progress_percentage, status) VALUES
(3, 4, 75.00, 'active'),
(3, 5, 40.00, 'active'),
(4, 4, 60.00, 'active'),
(4, 5, 85.00, 'active'),
(5, 4, 30.00, 'active'),
(5, 6, 20.00, 'active');

-- Insert sample course progress
INSERT INTO course_progress (student_id, course_id, lesson_id, is_completed, time_spent_minutes) VALUES
(3, 4, 1, TRUE, 30),
(3, 4, 2, TRUE, 45),
(3, 4, 3, TRUE, 40),
(3, 4, 4, TRUE, 50),
(3, 4, 5, FALSE, 20),
(4, 4, 1, TRUE, 25),
(4, 4, 2, TRUE, 40),
(4, 4, 3, TRUE, 35),
(4, 4, 4, FALSE, 30);

-- Insert sample quiz attempts
INSERT INTO quiz_attempts (student_id, quiz_id, attempt_number, score, total_points, percentage, passed, status) VALUES
(3, 1, 1, 4.00, 5.00, 80.00, TRUE, 'completed'),
(4, 1, 1, 3.00, 5.00, 60.00, FALSE, 'completed'),
(3, 2, 1, 0.00, 0.00, 0.00, FALSE, 'in_progress');

-- Insert sample quiz answers
INSERT INTO quiz_answers (attempt_id, question_id, selected_option_id, is_correct, points_earned) VALUES
(1, 1, 1, TRUE, 1.00),
(1, 2, 2, TRUE, 1.00),
(1, 3, 1, TRUE, 1.00),
(1, 4, 2, TRUE, 1.00),
(1, 5, 2, TRUE, 1.00),
(2, 1, 1, TRUE, 1.00),
(2, 2, 1, FALSE, 0.00),
(2, 3, 1, TRUE, 1.00),
(2, 4, 1, FALSE, 0.00),
(2, 5, 1, FALSE, 0.00);

-- Insert sample notifications
INSERT INTO notifications (user_id, title, message, notification_type) VALUES
(3, 'New Course Available', 'Check out our new course on Advanced PHP Programming!', 'info'),
(3, 'Quiz Reminder', 'Don\'t forget to complete your JavaScript Fundamentals Quiz', 'warning'),
(4, 'Course Updated', 'Web Development Fundamentals has new content available', 'success'),
(5, 'Welcome to IT HUB', 'Get started with your learning journey today!', 'info');

-- Insert sample chat messages
INSERT INTO chat_messages (sender_id, receiver_id, course_id, message, message_type) VALUES
(2, 3, 4, 'Welcome to the course! Feel free to ask any questions.', 'text'),
(3, 2, 4, 'Thank you! I\'m excited to learn web development.', 'text'),
(2, 4, 4, 'Great progress on your assignments! Keep up the good work.', 'text'),
(4, 2, 4, 'Thanks! The lessons are very helpful.', 'text');

-- Insert sample admin logs
INSERT INTO admin_logs (user_id, action, details, ip_address) VALUES
(1, 'login', 'Admin logged in', '127.0.0.1'),
(1, 'create_user', 'Created new user: alice_student', '127.0.0.1'),
(1, 'approve_course', 'Approved course: Web Development Fundamentals', '127.0.0.1'),
(1, 'view_reports', 'Viewed system reports', '127.0.0.1'),
(2, 'login', 'Instructor logged in', '127.0.0.1'),
(2, 'create_course', 'Created course: Advanced PHP Programming', '127.0.0.1'),
(3, 'login', 'Student logged in', '127.0.0.1'),
(3, 'enroll_course', 'Enrolled in course: Web Development Fundamentals', '127.0.0.1');
