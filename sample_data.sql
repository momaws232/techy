-- First, let's clear existing data in the correct order
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE post_likes;
TRUNCATE TABLE posts;
TRUNCATE TABLE topics;
TRUNCATE TABLE news;
TRUNCATE TABLE activity_log;
TRUNCATE TABLE forum_moderators;
TRUNCATE TABLE forums;
TRUNCATE TABLE user_pc_specs;
TRUNCATE TABLE system_requirements;
TRUNCATE TABLE settings;
TRUNCATE TABLE profanity_filters;
TRUNCATE TABLE products;
TRUNCATE TABLE users;

SET FOREIGN_KEY_CHECKS = 1;

-- Insert sample users
INSERT INTO users (username, email, password, role, status, display_name, bio, fullname) VALUES
('admin', 'admin@techforum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', 'Admin User', 'System Administrator', 'John Admin'),
('moderator', 'mod@techforum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'active', 'Forum Moderator', 'Community Moderator', 'Sarah Mod'),
('techguru', 'tech@techforum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 'Tech Guru', 'Hardware enthusiast and tech reviewer', 'Mike Tech'),
('gamer123', 'gamer@techforum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 'Pro Gamer', 'Professional gamer and streamer', 'Alex Game'),
('devmaster', 'dev@techforum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 'Dev Master', 'Software developer and tech enthusiast', 'Lisa Dev'),
('pcbuilder', 'pc@techforum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 'PC Builder', 'Custom PC builder and hardware expert', 'Tom Builder'),
('codewizard', 'code@techforum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 'Code Wizard', 'Senior software developer', 'Emma Code'),
('securitypro', 'security@techforum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 'Security Pro', 'Cybersecurity expert', 'David Secure'),
('mobileexpert', 'mobile@techforum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 'Mobile Expert', 'Mobile technology specialist', 'Sophie Mobile');

-- Insert forum categories and forums
INSERT INTO forums (id, name, category, description, icon, status) VALUES
('hardware', 'Hardware Discussion', 'Hardware', 'Discuss computer hardware, components, and builds', 'fas fa-microchip', 'active'),
('software', 'Software Development', 'Software', 'Programming, development, and software discussions', 'fas fa-code', 'active'),
('gaming', 'Gaming Community', 'Gaming', 'Video games, gaming hardware, and gaming culture', 'fas fa-gamepad', 'active'),
('networking', 'Networking & Security', 'Networking', 'Network infrastructure, security, and protocols', 'fas fa-network-wired', 'active'),
('mobile', 'Mobile Technology', 'Mobile', 'Smartphones, tablets, and mobile apps', 'fas fa-mobile-alt', 'active'),
('builds', 'PC Builds', 'Hardware', 'Share and discuss custom PC builds', 'fas fa-desktop', 'active'),
('programming', 'Programming Languages', 'Software', 'Discussion about various programming languages', 'fas fa-laptop-code', 'active'),
('security', 'Security & Privacy', 'Networking', 'Cybersecurity and privacy discussions', 'fas fa-shield-alt', 'active'),
('reviews', 'Product Reviews', 'Hardware', 'Share and read product reviews', 'fas fa-star', 'active'),
('troubleshooting', 'Tech Support', 'Hardware', 'Get help with technical issues', 'fas fa-wrench', 'active');

-- Insert forum moderators
INSERT INTO forum_moderators (forum_id, user_id) VALUES
('hardware', 3),  -- techguru
('software', 5),  -- devmaster
('gaming', 4),    -- gamer123
('networking', 8), -- securitypro
('mobile', 9),    -- mobileexpert
('builds', 6),    -- pcbuilder
('programming', 7), -- codewizard
('security', 8),  -- securitypro
('reviews', 3),   -- techguru
('troubleshooting', 2); -- moderator

-- Insert topics
INSERT INTO topics (title, forum_id, author_id, content, is_sticky, is_announcement, views) VALUES
-- Hardware Category
('Welcome to Tech Forum!', 'hardware', 1, 'Welcome to our community! This is the official welcome thread.', 1, 1, 150),
('Best CPU for Gaming in 2024', 'hardware', 6, 'Detailed analysis of the best CPUs for gaming in 2024...', 0, 0, 200),
('GPU Price Trends', 'hardware', 3, 'Analysis of current GPU pricing and availability...', 0, 0, 280),
('Gaming Monitor Recommendations', 'hardware', 4, 'Best gaming monitors for different budgets...', 0, 0, 220),
('RAM Speed vs Capacity', 'hardware', 6, 'Which is more important for gaming?', 0, 0, 180),
('SSD vs HDD for Gaming', 'hardware', 3, 'Storage solutions for modern gaming...', 0, 0, 160),

-- Software Category
('Getting Started with Python', 'software', 7, 'A comprehensive guide for beginners starting with Python...', 1, 0, 180),
('Web Development Roadmap', 'software', 5, 'Complete roadmap for becoming a web developer...', 1, 0, 350),
('JavaScript vs Python for Beginners', 'programming', 9, 'Which language should beginners learn first?...', 0, 0, 300),
('Best IDEs for Development', 'software', 7, 'Top development environments for different languages...', 0, 0, 250),
('Git Best Practices', 'software', 5, 'Essential Git commands and workflows...', 0, 0, 220),
('Docker for Beginners', 'software', 7, 'Getting started with containerization...', 0, 0, 190),

-- Gaming Category
('Latest Gaming Trends', 'gaming', 4, 'Discussion about the latest trends in gaming...', 0, 0, 120),
('Cloud Gaming Services Comparison', 'gaming', 6, 'Comparing different cloud gaming services...', 0, 0, 180),
('Best Gaming Headsets 2024', 'gaming', 3, 'Top gaming audio solutions...', 0, 0, 150),
('Gaming Mouse Recommendations', 'gaming', 4, 'Best gaming mice for different genres...', 0, 0, 140),
('Gaming Chair Reviews', 'gaming', 6, 'Comfort and ergonomics for long gaming sessions...', 0, 0, 130),
('Streaming Setup Guide', 'gaming', 4, 'Complete guide to streaming your gameplay...', 0, 0, 160),

-- Networking Category
('Network Security Best Practices', 'networking', 8, 'Essential security practices for network administrators...', 1, 0, 90),
('Home Network Setup Guide', 'networking', 2, 'Complete guide to setting up a home network...', 1, 0, 160),
('Latest Security Threats', 'security', 8, 'Discussion about recent cybersecurity threats...', 1, 0, 150),
('VPN Recommendations', 'networking', 9, 'Best VPN services for different needs...', 0, 0, 140),
('Router Setup Guide', 'networking', 8, 'Optimizing your home network...', 0, 0, 120),
('Network Troubleshooting', 'networking', 2, 'Common network issues and solutions...', 0, 0, 110),

-- Mobile Category
('iPhone 15 Pro Review', 'mobile', 9, 'Detailed review of the new iPhone 15 Pro...', 0, 0, 400),
('Android vs iOS Development', 'mobile', 7, 'Comparing mobile app development platforms...', 0, 0, 200),
('Best Android Phones 2024', 'mobile', 9, 'Top Android smartphones comparison...', 0, 0, 180),
('Mobile App Development Tips', 'mobile', 5, 'Best practices for mobile development...', 0, 0, 160),
('Smartphone Camera Guide', 'mobile', 9, 'Understanding mobile photography...', 0, 0, 150),
('Mobile Gaming Setup', 'mobile', 4, 'Optimizing your phone for gaming...', 0, 0, 140),

-- PC Builds Category
('My First Custom PC Build', 'builds', 6, 'Sharing my experience building my first custom PC...', 0, 0, 250),
('Budget Gaming PC Guide', 'builds', 3, 'Building a gaming PC under $1000...', 0, 0, 220),
('High-End Workstation Build', 'builds', 6, 'Professional workstation components...', 0, 0, 190),
('SFF PC Build Guide', 'builds', 3, 'Building a small form factor PC...', 0, 0, 170),
('Water Cooling Guide', 'builds', 6, 'Complete guide to custom water cooling...', 0, 0, 160),
('RGB Setup Guide', 'builds', 4, 'Creating the perfect RGB setup...', 0, 0, 150),

-- Programming Category
('Python Web Development', 'programming', 5, 'Building web apps with Python...', 0, 0, 180),
('JavaScript Frameworks', 'programming', 7, 'Comparing popular JS frameworks...', 0, 0, 170),
('C++ for Game Development', 'programming', 4, 'Getting started with game programming...', 0, 0, 160),
('Database Design Patterns', 'programming', 5, 'Best practices for database design...', 0, 0, 150),
('API Development Guide', 'programming', 7, 'Building RESTful APIs...', 0, 0, 140),
('Code Optimization Tips', 'programming', 5, 'Writing efficient code...', 0, 0, 130),

-- Security Category
('Password Security Guide', 'security', 8, 'Creating and managing secure passwords...', 0, 0, 170),
('Two-Factor Authentication', 'security', 2, 'Setting up 2FA for various services...', 0, 0, 160),
('Malware Prevention', 'security', 8, 'Protecting your system from malware...', 0, 0, 150),
('Secure Coding Practices', 'security', 7, 'Writing secure code...', 0, 0, 140),
('Privacy Tools Guide', 'security', 8, 'Tools for maintaining online privacy...', 0, 0, 130),
('Security Audit Guide', 'security', 2, 'Conducting security audits...', 0, 0, 120),

-- Reviews Category
('Gaming Monitor Reviews', 'reviews', 3, 'Detailed reviews of gaming monitors...', 0, 0, 200),
('Keyboard Comparison', 'reviews', 6, 'Mechanical keyboard reviews...', 0, 0, 190),
('Headset Reviews', 'reviews', 4, 'Gaming headset comparisons...', 0, 0, 180),
('Mouse Reviews', 'reviews', 3, 'Gaming mouse recommendations...', 0, 0, 170),
('Chair Reviews', 'reviews', 6, 'Gaming chair comparisons...', 0, 0, 160),
('PC Case Reviews', 'reviews', 3, 'Best PC cases for different builds...', 0, 0, 150),

-- Troubleshooting Category
('Common PC Issues', 'troubleshooting', 2, 'Solutions to common PC problems...', 0, 0, 300),
('BSOD Guide', 'troubleshooting', 3, 'Understanding and fixing blue screens...', 0, 0, 280),
('Performance Issues', 'troubleshooting', 6, 'Fixing slow PC performance...', 0, 0, 260),
('Driver Problems', 'troubleshooting', 2, 'Solving driver-related issues...', 0, 0, 240),
('Network Troubleshooting', 'troubleshooting', 8, 'Fixing network connectivity...', 0, 0, 220),
('Software Conflicts', 'troubleshooting', 5, 'Resolving software compatibility...', 0, 0, 200);

-- Insert posts for each topic (showing a few examples, you can add more)
INSERT INTO posts (topic_id, author_id, content) VALUES
-- Hardware Category Posts
(1, 1, 'Welcome everyone! Feel free to introduce yourself.'),
(1, 3, 'Thanks for the welcome! Excited to be part of this community.'),
(1, 4, 'Great to see a new tech forum. Looking forward to the discussions.'),
(2, 3, 'The AMD Ryzen 9 7950X is currently the best gaming CPU...'),
(2, 4, 'I agree, but the Intel i9-14900K is also worth considering...'),
(2, 6, 'What about the Ryzen 7 7800X3D? It has amazing gaming performance.'),

-- Software Category Posts
(7, 5, 'Python is an excellent choice for beginners...'),
(7, 7, 'I would also recommend starting with Python. The syntax is very readable.'),
(7, 9, 'Python was my first language too. Great choice!'),
(8, 5, 'Here\'s a comprehensive web development roadmap...'),
(8, 7, 'Don\'t forget to learn about databases and APIs!'),
(8, 9, 'Great roadmap! I would add mobile development as well.'),

-- Gaming Category Posts
(13, 4, 'Cloud gaming is becoming increasingly popular...'),
(13, 6, 'I tried GeForce NOW and it works surprisingly well.'),
(13, 8, 'What about latency issues with cloud gaming?'),
(14, 4, 'Here are my top gaming headset recommendations...'),
(14, 6, 'I can vouch for the HyperX Cloud II!'),
(14, 8, 'What about wireless options?'),

-- Networking Category Posts
(19, 2, 'Essential security practices for network administrators...'),
(19, 8, 'Don\'t forget to regularly update your firewall rules.'),
(19, 9, 'Two-factor authentication is a must these days.'),
(20, 8, 'Here\'s a complete guide to setting up your home network...'),
(20, 2, 'Great guide! I would add more about mesh networks.'),
(20, 9, 'What about network security?'),

-- Mobile Category Posts
(25, 9, 'Detailed review of the iPhone 15 Pro...'),
(25, 4, 'How does it compare to the previous model?'),
(25, 8, 'The security features are impressive.'),
(26, 9, 'Comparing Android and iOS development...'),
(26, 7, 'Both platforms have their strengths and weaknesses.'),
(26, 5, 'What about cross-platform development?'),

-- PC Builds Category Posts
(31, 6, 'Here\'s my first custom PC build...'),
(31, 3, 'Great build! What made you choose those components?'),
(31, 4, 'The cable management looks really clean.'),
(32, 6, 'Building a gaming PC under $1000 is possible...'),
(32, 3, 'Here are some alternative components...'),
(32, 4, 'Don\'t forget about the power supply!'),

-- Programming Category Posts
(37, 7, 'Python is great for web development...'),
(37, 5, 'I prefer Django for larger projects.'),
(37, 9, 'Flask is better for smaller applications.'),
(38, 7, 'Comparing React, Vue, and Angular...'),
(38, 5, 'React has the largest community.'),
(38, 9, 'Vue is easier to learn for beginners.'),

-- Security Category Posts
(43, 8, 'Creating strong passwords is essential...'),
(43, 2, 'Use a password manager!'),
(43, 9, 'Don\'t reuse passwords across sites.'),
(44, 8, 'Setting up 2FA on various platforms...'),
(44, 2, 'Authy is a great 2FA app.'),
(44, 9, 'What about hardware security keys?'),

-- Reviews Category Posts
(49, 3, 'Here are my top gaming monitor picks...'),
(49, 4, 'What about ultrawide monitors?'),
(49, 6, 'Don\'t forget about response time!'),
(50, 3, 'Comparing different mechanical switches...'),
(50, 4, 'I prefer Cherry MX switches.'),
(50, 6, 'What about optical switches?'),

-- Troubleshooting Category Posts
(55, 2, 'Common PC issues and their solutions...'),
(55, 3, 'Great guide! I would add more about overheating.'),
(55, 8, 'What about software-related issues?'),
(56, 2, 'Understanding BSOD error codes...'),
(56, 3, 'Most BSODs are driver-related.'),
(56, 8, 'Don\'t forget to check Windows updates.');

-- Insert news articles
INSERT INTO news (title, category, author_id, summary, content, publish_date, status) VALUES
('New AI Breakthrough in Tech Industry', 'Technology', 1, 'Major advancement in AI technology announced...', 'Full article content here...', '2024-03-15', 'published'),
('Gaming Industry Trends 2024', 'Gaming', 4, 'Latest trends shaping the gaming industry...', 'Full article content here...', '2024-03-14', 'published'),
('Cybersecurity Best Practices', 'Security', 2, 'Essential security measures for 2024...', 'Full article content here...', '2024-03-13', 'published'),
('New Generation of CPUs Announced', 'Hardware', 3, 'Major CPU manufacturers announce new processors...', 'Full article content here...', '2024-03-12', 'published'),
('Mobile App Development Trends', 'Mobile', 9, 'Latest trends in mobile app development...', 'Full article content here...', '2024-03-11', 'published'),
('Cloud Computing Revolution', 'Technology', 8, 'How cloud computing is transforming businesses...', 'Full article content here...', '2024-03-10', 'published'),
('Gaming Monitor Technology', 'Hardware', 4, 'Latest advancements in gaming display technology...', 'Full article content here...', '2024-03-09', 'published'),
('Programming Language Popularity', 'Software', 5, 'Most popular programming languages in 2024...', 'Full article content here...', '2024-03-08', 'published'),
('Smartphone Camera Evolution', 'Mobile', 9, 'How smartphone cameras have evolved...', 'Full article content here...', '2024-03-07', 'published'),
('Network Security Threats', 'Security', 8, 'Emerging threats in network security...', 'Full article content here...', '2024-03-06', 'published');

-- Insert system requirements
INSERT INTO system_requirements (name, category, min_cpu, rec_cpu, min_gpu, rec_gpu, min_ram, rec_ram, min_storage, rec_storage, os, additional_notes) VALUES
('High-End Gaming PC', 'Gaming', 'Intel i5-12400', 'Intel i7-13700K', 'RTX 3060', 'RTX 4070', 16, 32, 512, 1000, 'Windows 11', 'Optimized for 1440p gaming'),
('Development Workstation', 'Development', 'AMD Ryzen 5 5600X', 'AMD Ryzen 9 7950X', 'RTX 3060', 'RTX 4080', 32, 64, 1000, 2000, 'Windows 11/Linux', 'Ideal for software development'),
('Content Creation PC', 'Content Creation', 'Intel i7-12700K', 'Intel i9-14900K', 'RTX 3070', 'RTX 4090', 32, 64, 1000, 2000, 'Windows 11', 'Optimized for video editing'),
('Budget Gaming PC', 'Gaming', 'AMD Ryzen 5 5600', 'AMD Ryzen 7 5800X', 'RTX 3060', 'RTX 4060', 16, 32, 512, 1000, 'Windows 11', '1080p gaming focused'),
('Server Workstation', 'Server', 'Intel Xeon E5', 'Intel Xeon Gold', 'N/A', 'N/A', 32, 128, 2000, 4000, 'Linux', 'Optimized for server workloads');

-- Insert user PC specs
INSERT INTO user_pc_specs (user_id, name, cpu, gpu, ram, storage, os, additional_info) VALUES
(3, 'Main Gaming Rig', 'AMD Ryzen 9 7950X', 'RTX 4090', 64, 2000, 'Windows 11 Pro', 'Custom water cooling'),
(4, 'Streaming Setup', 'Intel i9-14900K', 'RTX 4080', 32, 2000, 'Windows 11 Pro', 'Dual monitor setup'),
(5, 'Development Machine', 'AMD Ryzen 9 7950X', 'RTX 4070', 64, 2000, 'Ubuntu 22.04', 'Docker development environment'),
(6, 'Content Creation PC', 'Intel i9-14900K', 'RTX 4090', 64, 4000, 'Windows 11 Pro', 'Triple monitor setup'),
(7, 'Gaming PC', 'AMD Ryzen 7 7800X3D', 'RTX 4070 Ti', 32, 2000, 'Windows 11 Pro', 'Custom RGB lighting'),
(8, 'Security Workstation', 'Intel i7-13700K', 'RTX 3060', 32, 1000, 'Kali Linux', 'Dedicated security tools'),
(9, 'Mobile Dev Setup', 'M1 MacBook Pro', 'M1 GPU', 16, 1000, 'macOS', 'iOS development environment');

-- Insert settings
INSERT INTO settings (setting_name, setting_value, setting_description) VALUES
('siteTitle', 'Tech Forum', 'The main title of the website'),
('siteDescription', 'Your ultimate tech community', 'The main description of the website'),
('postsPerPage', '20', 'Number of posts to display per page'),
('topicsPerPage', '15', 'Number of topics to display per page'),
('enableRegistration', 'true', 'Whether new user registration is enabled'),
('maxAttachments', '5', 'Maximum number of attachments per post'),
('maxAttachmentSize', '10', 'Maximum attachment size in MB'),
('enableRichText', 'true', 'Enable rich text editor'),
('enableMarkdown', 'true', 'Enable markdown support'),
('enableCodeHighlighting', 'true', 'Enable syntax highlighting for code blocks');

-- Insert activity log
INSERT INTO activity_log (user_id, action, object_type, object_id, object_name) VALUES
(1, 'create', 'topic', 1, 'Welcome to Tech Forum!'),
(3, 'create', 'topic', 2, 'Best CPU for Gaming in 2024'),
(4, 'reply', 'topic', 2, 'Best CPU for Gaming in 2024'),
(5, 'create', 'topic', 3, 'Getting Started with Python'),
(6, 'create', 'topic', 6, 'My First Custom PC Build'),
(7, 'create', 'topic', 7, 'JavaScript vs Python for Beginners'),
(8, 'create', 'topic', 8, 'Latest Security Threats'),
(9, 'create', 'topic', 9, 'iPhone 15 Pro Review'),
(3, 'create', 'topic', 10, 'GPU Price Trends'),
(5, 'create', 'topic', 11, 'Web Development Roadmap');

-- Insert post likes
INSERT INTO post_likes (post_id, user_id) VALUES
(1, 3), (1, 4), (1, 5), (1, 6),
(2, 4), (2, 5), (2, 7),
(3, 3), (3, 5), (3, 8),
(4, 4), (4, 6), (4, 9),
(5, 3), (5, 7), (5, 8),
(6, 4), (6, 5), (6, 9),
(7, 3), (7, 6), (7, 8),
(8, 4), (8, 7), (8, 9),
(9, 3), (9, 5), (9, 6),
(10, 4), (10, 8), (10, 9);

-- Insert profanity filters
INSERT INTO profanity_filters (word, severity, replacement, created_by) VALUES
('badword1', 'high', '****', 1),
('badword2', 'medium', '***', 1),
('badword3', 'low', '**', 1),
('badword4', 'high', '****', 1),
('badword5', 'medium', '***', 1);

-- Insert products
INSERT INTO products (name, category, price, description, added_date, status, amazon_link) VALUES
('Gaming Mouse Pro', 'Peripherals', 79.99, 'High-precision gaming mouse with RGB lighting', '2024-03-15', 'active', 'https://www.amazon.com/dp/B07GBZ4Q68'),
('Mechanical Keyboard', 'Peripherals', 129.99, 'Mechanical gaming keyboard with custom switches', '2024-03-15', 'active', 'https://www.amazon.com/dp/B07GSWT56W'),
('Gaming Headset', 'Audio', 149.99, '7.1 surround sound gaming headset', '2024-03-15', 'active', 'https://www.amazon.com/dp/B07GW283JG'),
('4K Gaming Monitor', 'Displays', 499.99, '27-inch 4K gaming monitor with 144Hz refresh rate', '2024-03-15', 'active', 'https://www.amazon.com/dp/B07JXCR263'),
('Gaming Chair', 'Furniture', 299.99, 'Ergonomic gaming chair with lumbar support', '2024-03-15', 'active', 'https://www.amazon.com/dp/B07GCKL7F5'),
('Streaming Microphone', 'Audio', 199.99, 'Professional streaming microphone with pop filter', '2024-03-15', 'active', 'https://www.amazon.com/dp/B07GQT8879'),
('Gaming Mousepad', 'Peripherals', 29.99, 'Extended RGB gaming mousepad', '2024-03-15', 'active', 'https://www.amazon.com/dp/B07GSRB1PS'),
('Webcam Pro', 'Peripherals', 89.99, '1080p streaming webcam with autofocus', '2024-03-15', 'active', 'https://www.amazon.com/dp/B07K95WFWM'),
('Gaming Router', 'Networking', 199.99, 'High-performance gaming router with QoS', '2024-03-15', 'active', 'https://www.amazon.com/dp/B07GVR9TG7'),
('RGB Light Strip', 'Accessories', 39.99, 'RGB LED light strip for PC setup', '2024-03-15', 'active', 'https://www.amazon.com/dp/B07DPNNRH5'),
('Gaming Desk', 'Furniture', 249.99, 'Large gaming desk with cable management', '2024-03-15', 'active', 'https://www.amazon.com/dp/B07GFN6CVB'),
('USB Hub', 'Accessories', 49.99, 'USB 3.0 hub with individual power switches', '2024-03-15', 'active', 'https://www.amazon.com/dp/B07DW646GY'),
('Gaming Speakers', 'Audio', 129.99, '2.1 gaming speaker system with subwoofer', '2024-03-15', 'active', 'https://www.amazon.com/dp/B07GL6CLTT'),
('Monitor Arm', 'Accessories', 79.99, 'Adjustable monitor mount for dual setup', '2024-03-15', 'active', 'https://www.amazon.com/dp/B07BF76ZG1'),
('Gaming Backpack', 'Accessories', 89.99, 'Water-resistant gaming laptop backpack', '2024-03-15', 'active', 'https://www.amazon.com/dp/B07YDGN59C'); 