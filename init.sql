CREATE TABLE companies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    country VARCHAR(100) NOT NULL,
    currency_code VARCHAR(10) NOT NULL,
    currency_symbol VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Manager', 'Employee') DEFAULT 'Employee',
    manager_id INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE expense_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

CREATE TABLE expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    company_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency_code VARCHAR(10) NOT NULL,
    converted_amount DECIMAL(10, 2),
    category_id INT,
    description TEXT,
    expense_date DATE NOT NULL,
    receipt_path VARCHAR(255),
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    current_approver_id INT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (current_approver_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE approval_rules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    rule_name VARCHAR(255) NOT NULL,
    is_manager_approver TINYINT(1) DEFAULT 1,
    approval_type ENUM('Sequential', 'Percentage', 'Specific', 'Hybrid') DEFAULT 'Sequential',
    percentage_required DECIMAL(5, 2) NULL,
    specific_approver_id INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (specific_approver_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE approval_steps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rule_id INT NOT NULL,
    step_number INT NOT NULL,
    approver_id INT NOT NULL,
    role_required ENUM('Manager', 'Admin', 'Finance', 'Director') NULL,
    FOREIGN KEY (rule_id) REFERENCES approval_rules(id) ON DELETE CASCADE,
    FOREIGN KEY (approver_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE approval_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    expense_id INT NOT NULL,
    approver_id INT NOT NULL,
    action ENUM('Approved', 'Rejected', 'Pending') DEFAULT 'Pending',
    comments TEXT,
    step_number INT,
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expense_id) REFERENCES expenses(id) ON DELETE CASCADE,
    FOREIGN KEY (approver_id) REFERENCES users(id) ON DELETE CASCADE
);