# Expense Management System

## Tech Stack

- **Backend**: PHP 8.2, MySQL
- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript
- **OCR**: Tesseract.js
- **APIs**: 
  - REST Countries API (for country/currency data)
  - ExchangeRate API (for currency conversion)

## Installation

### Prerequisites
- XAMPP/WAMP/LAMP (Apache + MySQL + PHP 8+)
- Modern web browser

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/tankishani18/expense-management.git
   cd expense-management
   ```

2. **Import Database**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create database: `expense_management`
   - Import `database_schema.sql`

3. **Configure Database**
   - Update `config.php` if needed (default: localhost, root, no password)

4. **Start Application**
   - Place project in `htdocs` folder
   - Navigate to: `http://localhost/expense-management-2/`

## Screenshots

### 1. Login Page
![Login](Readme_Screenshots/Screenshot%202025-10-04%20154042.png)
*Secure login interface for all users*

### 2. Sign Up / Company Creation
![Sign Up](Readme_Screenshots/Screenshot%202025-10-04%20154042.png)
*Create your company account with automatic currency setup based on country selection*

### 3. Dashboard - Admin View
![Admin Dashboard](Readme_Screenshots/Screenshot%202025-10-04%20154241.png)
*Admin dashboard showing total expenses, pending approvals, and quick action buttons*

### 4. User Management (Admin)
![Manage Users](Readme_Screenshots/Screenshot%202025-10-04%20154302.png)
*Admin can create employees, managers, assign roles and set manager relationships*

### 5. Add New User
![Add New User](Readme_Screenshots/Screenshot%202025-10-04%20154434.png)
*Admin can add new user and assign thrm roles as well*

### 6. Updated User List (Admin)
![Updated User List](Readme_Screenshots/Screenshot%202025-10-04%20154503.png)
*Admin can see the list of all the users*

### 7. Approval Roles
![Approval Roles](Readme_Screenshots/Screenshot%202025-10-04%20154858.png)
*Configure complex approval workflows - sequential, percentage-based, or specific approver rules*

### 8. Add Approval Rule
![Add Approval Rule](Readme_Screenshots/Screenshot%202025-10-04%20155002.png)
*Admin can update Approval rules*

### 9. Updated Approval Rule
![Updated Approval Rule](Readme_Screenshots/Screenshot%202025-10-04%20155014.png)
*Admin can see all the updated Approval rules*

### 10. View All Expenses 
![ View All Expenses](Readme_Screenshots/Screenshot%202025-10-04%20155032.png)
*Admin can view and override all company expenses with detailed information*

### 11. Pending Approvals 
![Pending Approvals](Readme_Screenshots/Screenshot%202025-10-04%20155106.png)
*Managers can approve or reject expenses assigned to them with comments*

### 12. Expense Details
![Expense Details](Readme_Screenshots/Screenshot%202025-10-04%20155134.png)
*Add comments while approving or rejecting expenses*

### 13. Updated Pending Approvals
![Updated Pending Approvals](Readme_Screenshots/Screenshot%202025-10-04%20155149.png)
*Real-time status updates and notifications for approved/rejected expenses*

### 14. New Expenses
![ New Expenses](Readme_Screenshots/Screenshot%202025-10-04%20155210.png)
*Automatic currency conversion with original and converted amounts displayed*

### 15. Submitting a New Expense
![Submitting a New Expense](Readme_Screenshots/Screenshot%202025-10-04%20155322.png)
*Submitting a new expense into the website*

### 16. Updated Expense List
![ Updated Expense List](Readme_Screenshots/Screenshot%202025-10-04%20155336.png)
*Updated expense list after adding or approving*

### 17. OCR Receipt Scanner
![OCR Receipt Scanner](Readme_Screenshots/Screenshot%202025-10-04%20155355.png)
*AI-powered OCR automatically extracts amount, date, merchant, and category from receipt images*

## User Roles & Permissions

### Admin
- Create and manage users
- Configure approval rules
- View all company expenses
- Override any approval
- Manage company settings

### Manager
- Approve/reject expenses assigned to them
- View team expenses
- Escalate expenses per approval rules
- Add comments on approvals

### Employee
- Submit expense claims
- Upload receipts
- Use OCR scanner
- Track expense status
- View personal expense history

## Key Features Explained

### 1. Conditional Approval Workflow
- **Sequential**: Step-by-step approval (Manager → Finance → Director)
- **Percentage**: Approval when X% of approvers approve
- **Specific Approver**: Auto-approve if specific person (e.g., CFO) approves
- **Hybrid**: Combination of percentage OR specific approver

### 2. OCR Receipt Processing
- Upload receipt image (JPG/PNG)
- AI extracts: Amount, Date, Merchant, Currency
- Auto-categorizes based on keywords
- Manual review and edit before submission

### 3. Multi-Currency Support
- Select from multiple currencies
- Real-time conversion to company base currency
- Display both original and converted amounts
- Uses live exchange rate API

## Database Schema

- **companies**: Company information and currency settings
- **users**: User accounts with roles and manager relationships
- **expenses**: Expense records with amounts and status
- **expense_categories**: Predefined expense categories
- **approval_rules**: Complex approval workflow rules
- **approval_steps**: Sequential approval step definitions
- **approval_history**: Complete audit trail of all approvals

## API Integration

### 1. REST Countries API
```
https://restcountries.com/v3.1/all?fields=name,currencies
```
Used for: Country selection and automatic currency detection during signup

### 2. ExchangeRate API
```
https://api.exchangerate-api.com/v4/latest/{BASE_CURRENCY}
```
Used for: Real-time currency conversion

## Project Structure

```
expense-management-2/
├── config.php                 # Database configuration
├── index.php                  # Login page
├── signup.php                 # Company registration
├── dashboard.php              # Main dashboard
├── logout.php                 # Logout handler
├── database_schema.sql        # Database structure
├── admin/
│   ├── manage_users.php      # User management
│   ├── approval_rules.php    # Approval rule configuration
│   └── view_expenses.php     # All expenses view
├── employee/
│   ├── submit_expense.php    # Submit new expense
│   ├── my_expenses.php       # Personal expense history
│   └── ocr_scan.php          # OCR receipt scanner
├── manager/
│   ├── pending_approvals.php # Pending approvals list
│   └── approve_expense.php   # Approval handler
└── uploads/                   # Receipt storage
```

## Future Enhancements

- Email notifications for approvals
- Excel/PDF export of expense reports
- Advanced analytics dashboard
- Budget tracking and alerts
- Mobile app (React Native)
- Integration with accounting software

## Contributing

This project was developed as part of a hackathon. Contributions are welcome!

## License

MIT License - Feel free to use for educational purposes

## Developer

Developed by [Tankishani18](https://github.com/tankishani18) & [Jinal Mevada](https://github.com/jinalmevada)

## Contact

For queries or support, please open an issue on GitHub.

---

**Note**: This is a student hackathon project demonstrating expense management workflow concepts.
