-- Sample Lessons and Demo Content for IT HUB

-- Insert sample lessons for Web Development course
INSERT INTO lessons (course_id, title, description, content, lesson_type, video_url, duration, lesson_order, is_published) VALUES
(1, 'Introduction to HTML', 'Learn the basics of HTML and web structure', 'In this lesson, you will learn the fundamentals of HTML including tags, attributes, and document structure. We will cover how to create headings, paragraphs, lists, and links.', 'video', 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_1mb.mp4', '45 minutes', 1, 1),
(1, 'CSS Fundamentals', 'Master CSS styling and layout techniques', 'Learn how to style your HTML pages with CSS. This lesson covers selectors, properties, colors, fonts, and basic layout techniques including Flexbox and Grid.', 'video', 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_2mb.mp4', '60 minutes', 2, 1),
(1, 'JavaScript Basics', 'Introduction to JavaScript programming', 'Get started with JavaScript programming. Learn variables, functions, loops, and basic DOM manipulation to make your web pages interactive.', 'text', 'JavaScript is the programming language of the web. It allows you to implement complex features on web pages.', '50 minutes', 3, 1),
(1, 'Building Your First Website', 'Create a complete website from scratch', 'Put everything together and build your first complete website. We will create a personal portfolio website using HTML, CSS, and JavaScript.', 'video', 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_5mb.mp4', '90 minutes', 4, 1);

-- Insert sample lessons for PHP Programming course
INSERT INTO lessons (course_id, title, description, content, lesson_type, video_url, duration, lesson_order, is_published) VALUES
(2, 'PHP Introduction', 'Get started with PHP programming', 'Learn what PHP is, how it works, and how to set up your development environment. We will cover basic syntax and create your first PHP script.', 'video', 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_1mb.mp4', '40 minutes', 1, 1),
(2, 'Variables and Data Types', 'Understanding PHP variables and data types', 'Learn about PHP variables, data types, and type casting. We will explore strings, integers, floats, arrays, and objects.', 'text', 'PHP supports several types of variables: strings, integers, floats, arrays, objects, NULL, and booleans. Each type has specific use cases and methods.', '35 minutes', 2, 1),
(2, 'Control Structures', 'Master if-else statements and loops', 'Learn how to control the flow of your PHP programs using conditional statements and loops. We will cover if-else, switch, for, while, and foreach loops.', 'video', 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_2mb.mp4', '55 minutes', 3, 1),
(2, 'Functions and Scope', 'Creating and using PHP functions', 'Learn how to create reusable functions, understand scope, and work with parameters and return values.', 'text', 'Functions are blocks of reusable code that perform specific tasks. They help organize code and make it more maintainable.', '45 minutes', 4, 1);

-- Insert sample lessons for Database Design course
INSERT INTO lessons (course_id, title, description, content, lesson_type, video_url, duration, lesson_order, is_published) VALUES
(3, 'Database Fundamentals', 'Introduction to database concepts', 'Learn what databases are, why we need them, and the basic concepts of data modeling and normalization.', 'video', 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_1mb.mp4', '50 minutes', 1, 1),
(3, 'SQL Basics', 'Learn fundamental SQL queries', 'Master the basic SQL commands including SELECT, INSERT, UPDATE, DELETE, and how to filter and sort data.', 'text', 'SQL (Structured Query Language) is used to communicate with databases. The basic commands include SELECT for retrieving data, INSERT for adding data, UPDATE for modifying data, and DELETE for removing data.', '60 minutes', 2, 1),
(3, 'Advanced Queries', 'Complex SQL operations', 'Learn about JOINs, subqueries, aggregate functions, and advanced filtering techniques.', 'video', 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_3mb.mp4', '75 minutes', 3, 1),
(3, 'Database Design Principles', 'Design efficient database schemas', 'Learn about normalization, indexing, relationships, and best practices for designing efficient database schemas.', 'text', 'Good database design is crucial for performance and maintainability. Learn about the three normal forms and when to use them.', '65 minutes', 4, 1);

-- Insert sample lessons for UI/UX Design course
INSERT INTO lessons (course_id, title, description, content, lesson_type, video_url, duration, lesson_order, is_published) VALUES
(4, 'Design Principles', 'Fundamental principles of good design', 'Learn about color theory, typography, layout, and the fundamental principles that make designs effective and appealing.', 'video', 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_2mb.mp4', '55 minutes', 1, 1),
(4, 'User Research', 'Understanding your users', 'Learn how to conduct user research, create user personas, and gather insights that inform your design decisions.', 'text', 'User research is the foundation of good UX design. It helps you understand user needs, behaviors, and pain points.', '45 minutes', 2, 1),
(4, 'Wireframing and Prototyping', 'Creating design mockups', 'Learn how to create wireframes, mockups, and interactive prototypes using modern design tools.', 'video', 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_2mb.mp4', '70 minutes', 3, 1),
(4, 'Responsive Design', 'Designing for multiple devices', 'Learn how to create designs that work seamlessly across desktop, tablet, and mobile devices.', 'text', 'Responsive design ensures your user interface adapts to different screen sizes and devices, providing a consistent experience.', '50 minutes', 4, 1);

-- Insert sample lessons for Digital Marketing course
INSERT INTO lessons (course_id, title, description, content, lesson_type, video_url, duration, lesson_order, is_published) VALUES
(5, 'Marketing Fundamentals', 'Introduction to digital marketing', 'Learn the core concepts of digital marketing, including the marketing funnel, customer journey, and key metrics.', 'video', 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_1mb.mp4', '45 minutes', 1, 1),
(5, 'SEO Basics', 'Search Engine Optimization fundamentals', 'Learn how search engines work and the basic techniques to improve your website\'s ranking in search results.', 'text', 'SEO is the process of optimizing your website to rank higher in search engine results pages. Learn about on-page, off-page, and technical SEO.', '60 minutes', 2, 1),
(5, 'Social Media Marketing', 'Leveraging social platforms', 'Learn how to create effective social media strategies and use platforms like Facebook, Instagram, and Twitter for marketing.', 'video', 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_3mb.mp4', '55 minutes', 3, 1),
(5, 'Email Marketing', 'Building effective email campaigns', 'Learn how to create, send, and analyze email marketing campaigns that convert.', 'text', 'Email marketing remains one of the most effective digital marketing channels. Learn about list building, segmentation, and campaign optimization.', '40 minutes', 4, 1);

-- Insert sample lessons for Cyber Security course
INSERT INTO lessons (course_id, title, description, content, lesson_type, video_url, duration, lesson_order, is_published) VALUES
(6, 'Security Fundamentals', 'Introduction to cybersecurity', 'Learn the basic concepts of cybersecurity, including threats, vulnerabilities, and risk management.', 'video', 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_2mb.mp4', '50 minutes', 1, 1),
(6, 'Network Security', 'Protecting network infrastructure', 'Learn about firewalls, intrusion detection systems, and network security best practices.', 'text', 'Network security involves protecting the integrity, confidentiality, and availability of computer networks and data.', '65 minutes', 2, 1),
(6, 'Web Application Security', 'Securing web applications', 'Learn about common web vulnerabilities like XSS, SQL injection, and how to protect against them.', 'video', 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_3mb.mp4', '70 minutes', 3, 1),
(6, 'Security Best Practices', 'Implementing security measures', 'Learn about security policies, incident response, and best practices for maintaining security.', 'text', 'Security is an ongoing process. Learn about continuous monitoring, incident response, and maintaining security hygiene.', '55 minutes', 4, 1);

-- Add some sample completed lessons for demo student
INSERT INTO completed_lessons (student_id, lesson_id, completed_at) VALUES
-- Alice (student_id=3) completed some lessons
(3, 1, NOW() - INTERVAL 2 DAY),
(3, 2, NOW() - INTERVAL 1 DAY),
(3, 5, NOW() - INTERVAL 3 DAY),
(3, 6, NOW() - INTERVAL 2 DAY);

-- Update course progress for demo student
UPDATE enrollments SET progress_percentage = 50 WHERE student_id = 3 AND course_id = 1;
UPDATE enrollments SET progress_percentage = 25 WHERE student_id = 3 AND course_id = 2;
