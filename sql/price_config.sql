-- Price configuration table for interior design calculator
CREATE TABLE IF NOT EXISTS `price_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_type` varchar(50) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `design_style` varchar(50) NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `price_per_sqft` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default price configurations
INSERT INTO `price_config` (`property_type`, `room_type`, `design_style`, `base_price`, `price_per_sqft`) VALUES
('Apartment', 'Living Room', 'Modern', 25000.00, 150.00),
('Apartment', 'Living Room', 'Contemporary', 30000.00, 180.00),
('Apartment', 'Living Room', 'Traditional', 28000.00, 160.00),
('Apartment', 'Bedroom', 'Modern', 18000.00, 120.00),
('Apartment', 'Bedroom', 'Contemporary', 22000.00, 140.00),
('Apartment', 'Bedroom', 'Traditional', 20000.00, 130.00),
('Apartment', 'Kitchen', 'Modern', 35000.00, 200.00),
('Apartment', 'Kitchen', 'Contemporary', 40000.00, 220.00),
('Apartment', 'Kitchen', 'Traditional', 38000.00, 210.00),
('Villa', 'Living Room', 'Modern', 40000.00, 200.00),
('Villa', 'Living Room', 'Contemporary', 45000.00, 230.00),
('Villa', 'Living Room', 'Traditional', 42000.00, 210.00),
('Villa', 'Bedroom', 'Modern', 30000.00, 180.00),
('Villa', 'Bedroom', 'Contemporary', 35000.00, 200.00),
('Villa', 'Bedroom', 'Traditional', 32000.00, 190.00),
('Villa', 'Kitchen', 'Modern', 50000.00, 250.00),
('Villa', 'Kitchen', 'Contemporary', 55000.00, 270.00),
('Villa', 'Kitchen', 'Traditional', 52000.00, 260.00);