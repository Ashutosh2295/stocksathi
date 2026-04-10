# Detailed Class Diagram Documentation
**Project:** Stocksathi V2 (Inventory, ERP, AI Assistant)

This document provides a highly granular breakdown of the Class Diagram, including every entity, its attributes (with data types), relationships, and methods.

---

## 1. System Entities (Core Classes)

### 1.1 `User` Class
Handles authentication, session management, and role-based access.

*   **Attributes:**
    *   `+ int id`: Primary Key, Auto Increment.
    *   `+ int organization_id`: Foreign Key linking to Organization.
    *   `+ string name`: Full name of the user.
    *   `+ string email`: Unique email address for login.
    *   `+ string password`: BCrypt hashed password.
    *   `+ string phone`: Contact number.
    *   `+ string role`: Enum (`super_admin`, `admin`, `hr`, `store_manager`, `sales_executive`, `accountant`).
    *   `+ string otp_code`: 6-digit 2FA code.
    *   `+ datetime otp_expiry`: Timestamp for OTP expiration.
    *   `+ boolean is_active`: Status flag (1 = Active, 0 = Suspended).
*   **Methods:**
    *   `+ boolean login(email, password)`: Verifies credentials.
    *   `+ boolean verifyOTP(otp_code)`: Checks if the OTP is correct and unexpired.
    *   `+ void resetPassword(new_password)`: Hashes and updates password.
    *   `+ boolean checkPermission(module_name)`: Verifies if the user's role allows access to a specific module.

### 1.2 `Organization` Class
Handles multi-tenancy. Every piece of data in the system belongs to an organization.

*   **Attributes:**
    *   `+ int id`: Primary Key.
    *   `+ string name`: Organization/Company name.
    *   `+ string email`: Contact email for the organization.
    *   `+ string phone`: Contact phone number.
    *   `+ string address`: Physical billing/shipping address.
    *   `+ string gst_number`: Tax identification number.
    *   `+ string logo_path`: URL/Path to the UI logo.
    *   `+ datetime created_at`: Registration timestamp.
*   **Methods:**
    *   `+ Organization getDetails(org_id)`: Fetches complete organizational info.
    *   `+ boolean updateSettings(data)`: Updates GST, Logo, or contact info.

### 1.3 `Product` (Inventory) Class
Manages the stock, pricing, and categorization of items.

*   **Attributes:**
    *   `+ int id`: Primary Key.
    *   `+ int organization_id`: Foreign Key.
    *   `+ int category_id`: Foreign Key linking to Categories.
    *   `+ string name`: Product description.
    *   `+ string sku`: Stock Keeping Unit (Unique Identifier).
    *   `+ float purchase_price`: Cost to the organization.
    *   `+ float selling_price`: Cost to the customer.
    *   `+ int stock_quantity`: Current available units.
    *   `+ int minimum_stock_alert`: Threshold for low-stock warning.
    *   `+ string unit`: Measurement unit (kg, pcs, boxes).
*   **Methods:**
    *   `+ boolean addStock(quantity)`: Increases `stock_quantity`.
    *   `+ boolean deductStock(quantity)`: Decreases `stock_quantity` during sales.
    *   `+ boolean checkLowStock()`: Returns true if `stock_quantity <= minimum_stock_alert`.
    *   `+ float calculateProfitMargin()`: Returns `selling_price - purchase_price`.

### 1.4 `Invoice` Class
Handles the POS billing engine and PDF generation.

*   **Attributes:**
    *   `+ int id`: Primary Key.
    *   `+ int organization_id`: Foreign Key.
    *   `+ int user_id`: Foreign Key (Sales Executive who generated it).
    *   `+ string invoice_number`: Formatted unique string (e.g., INV-2026-001).
    *   `+ string customer_name`: Buyer's name.
    *   `+ string customer_phone`: Buyer's contact.
    *   `+ float subtotal`: Amount before tax.
    *   `+ float tax_amount`: Calculated GST.
    *   `+ float total_amount`: Final payable sum (`subtotal + tax_amount`).
    *   `+ string status`: Enum (`Paid`, `Pending`, `Cancelled`).
    *   `+ datetime created_at`: Date of sale.
*   **Methods:**
    *   `+ Invoice createInvoice(cart_data, customer_data)`: Generates a new bill.
    *   `+ void calculateTax(gst_percentage)`: Computes the `tax_amount`.
    *   `+ file generatePDF()`: Compiles HTML/CSS into a downloadable PDF document.

### 1.5 `InvoiceItem` Class
Stores the individual line-items inside an invoice.

*   **Attributes:**
    *   `+ int id`: Primary Key.
    *   `+ int invoice_id`: Foreign Key linking to Invoice.
    *   `+ int product_id`: Foreign Key linking to Product.
    *   `+ int quantity`: Number of units sold.
    *   `+ float unit_price`: Price per unit at the time of sale.
    *   `+ float total_price`: `quantity * unit_price`.

### 1.6 `AIAssistant` Class (New Feature)
The virtual assistant that interacts with users for support and queries.

*   **Attributes:**
    *   `+ string bot_name`: Identifier for the AI.
    *   `+ string llm_model`: The API model in use (e.g., GPT-4 / Gemini).
    *   `+ array system_prompt`: Context instructing the AI on its ERP capabilities.
*   **Methods:**
    *   `+ string parseNaturalLanguage(user_input)`: Converts text to actionable intents.
    *   `+ dataset generateSQLQuery(parsed_intent)`: Constructs safe SQL `SELECT` queries for reporting.
    *   `+ string analyzeErrorLogs(file_path)`: Scans `php_error_log` for stack traces.
    *   `+ string provideSolution(log_data)`: Returns formatted code fixes based on logs.
    *   `+ array predictStockTrends(product_id)`: Analyzes historical sales data to forecast future stock needs.

---

## 2. Relationships (Multiplicities)

*   **Organization to User:** 1 to Many (`1..*`). An organization has multiple employees.
*   **Organization to Product:** 1 to Many (`1..*`). An organization owns multiple products.
*   **Organization to Invoice:** 1 to Many (`1..*`).
*   **User to Invoice:** 1 to Many (`1..*`). A sales executive generates multiple invoices.
*   **Invoice to InvoiceItem:** 1 to Many (`1..*`). One invoice contains multiple products.
*   **Product to InvoiceItem:** 1 to Many (`1..*`). A specific product can appear across multiple invoices.
*   **User to AIAssistant:** Many to 1 (`*..1`). Many users can interact with the single AI Assistant concurrently.
