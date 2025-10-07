-- Homepage Sections Database Tables

-- Hero/Lead Capture Section
CREATE TABLE IF NOT EXISTS homepage_hero (
    id INT AUTO_INCREMENT PRIMARY KEY,
    main_headline VARCHAR(255) NOT NULL,
    sub_headline VARCHAR(255) NOT NULL,
    tagline TEXT NOT NULL,
    cta_box_title VARCHAR(255) NOT NULL,
    cta_box_subtitle TEXT NOT NULL,
    form_name_label VARCHAR(100) DEFAULT 'Name',
    form_phone_label VARCHAR(100) DEFAULT 'Phone Number',
    form_email_label VARCHAR(100) DEFAULT 'Email',
    form_location_label VARCHAR(100) DEFAULT 'Property Location',
    button_text VARCHAR(100) DEFAULT 'Chat with Design Expert',
    background_image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Testimonials Section
CREATE TABLE IF NOT EXISTS homepage_testimonials_section (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_title VARCHAR(255) NOT NULL,
    section_subtitle TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS homepage_testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_id INT NOT NULL,
    customer_image VARCHAR(255),
    quote_title VARCHAR(255) NOT NULL,
    testimonial_text TEXT NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    design_expert_name VARCHAR(255),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES homepage_testimonials_section(id) ON DELETE CASCADE
);

-- Process/How It Works Section
CREATE TABLE IF NOT EXISTS homepage_process_section (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_title VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS homepage_process_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_id INT NOT NULL,
    step_number INT NOT NULL,
    step_title VARCHAR(255) NOT NULL,
    step_description TEXT NOT NULL,
    step_image VARCHAR(255),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES homepage_process_section(id) ON DELETE CASCADE
);

-- Company Info/Trust Signals Section
CREATE TABLE IF NOT EXISTS homepage_company_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_bio TEXT NOT NULL,
    homes_delivered VARCHAR(100) DEFAULT '10,000+',
    design_experts VARCHAR(100) DEFAULT '500+',
    cities_count VARCHAR(100) DEFAULT '12',
    cities_list TEXT,
    final_tagline TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS homepage_value_propositions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_info_id INT NOT NULL,
    proposition_text TEXT NOT NULL,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_info_id) REFERENCES homepage_company_info(id) ON DELETE CASCADE
);

-- Insert default data for Hero section
INSERT INTO homepage_hero (main_headline, sub_headline, tagline, cta_box_title, cta_box_subtitle)
VALUES (
    'Best Interior Designers',
    'In Chennai',
    'Home Interiors Within Your Budget - Unbeatable Quality @ Unbelievable Price!',
    'Explore 50,000+ Design Ideas',
    'Get Free Estimate and Interior Design Ideas in Minutes'
);

-- Insert default data for Testimonials section
INSERT INTO homepage_testimonials_section (section_title, section_subtitle)
VALUES (
    'Our Work, Their Words',
    'Designing Happy Decor Homes. Real Experiences, Stunning Interiors – Hear from Our Customers!'
);

-- Insert default data for Process section
INSERT INTO homepage_process_section (section_title)
VALUES ('How Does Our Interior Designers Work');

-- Insert default data for Company Info section
INSERT INTO homepage_company_info (company_bio, cities_list, final_tagline)
VALUES (
    'Decorpot was founded with a vision to transform living spaces into personalized havens of comfort and style. Since our inception, we have been dedicated to delivering premium interior design solutions that blend functionality with aesthetics. Our team of experienced designers works closely with clients to understand their unique needs and preferences, ensuring that every project reflects their personality and lifestyle.',
    'Bangalore, Chennai, Hyderabad, Mumbai, Delhi, Pune, Kolkata, Ahmedabad, Jaipur, Chandigarh, Lucknow, Kochi',
    'Decorpot - India\'s Best Premium & Luxury Home Interior Brand'
);

-- Insert default value propositions
INSERT INTO homepage_value_propositions (company_info_id, proposition_text, display_order)
VALUES 
(1, 'Explore 50,000+ design possibilities.', 1),
(1, '100% transparent pricing – no surprises!', 2),
(1, 'Quick interior installation with guaranteed quality.', 3);

-- Insert sample testimonials
INSERT INTO homepage_testimonials (section_id, quote_title, testimonial_text, customer_name, design_expert_name, display_order)
VALUES 
(1, 'That was delicate...', 'The attention to detail was remarkable. Every corner of our home reflects our personality and style. The team was professional and responsive throughout the process.', 'Mr. Narendra & Family', 'Lux Chu', 1),
(1, 'The experience was really amazing!', 'From concept to completion, Decorpot exceeded our expectations. The designers understood our vision perfectly and brought it to life beautifully.', 'Chetan & Anu', 'Priya Design Expert', 2);

-- Insert sample process steps
INSERT INTO homepage_process_steps (section_id, step_number, step_title, step_description, display_order)
VALUES 
(1, 1, 'Discussing Your Vision', 'We begin by understanding your requirements, preferences, and budget to create a personalized design plan.', 1),
(1, 2, 'On-site Visit', 'Our experts visit your property to take measurements and assess the space for optimal design solutions.', 2),
(1, 3, 'Design Presentation', 'We present detailed 3D visualizations of your space, allowing you to see and approve the final design.', 3),
(1, 4, 'Execution & Installation', 'Our skilled craftsmen bring the designs to life with precision and quality workmanship.', 4);