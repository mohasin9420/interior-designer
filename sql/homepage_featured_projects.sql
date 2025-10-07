-- Featured Projects for Homepage
CREATE TABLE IF NOT EXISTS homepage_featured_projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    portfolio_id INT NOT NULL,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_featured_portfolio FOREIGN KEY (portfolio_id) REFERENCES portfolio(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Section settings
CREATE TABLE IF NOT EXISTS homepage_portfolio_section (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_title VARCHAR(255) DEFAULT 'Our Featured Projects',
    section_description TEXT NULL,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default section data
INSERT INTO homepage_portfolio_section (section_title, section_description, is_active) 
VALUES ('Our Featured Projects', 'Explore our handpicked selection of stunning interior design projects that showcase our expertise and creativity.', 1);