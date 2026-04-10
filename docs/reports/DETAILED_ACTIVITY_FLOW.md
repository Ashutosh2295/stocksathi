# Detailed Activity Diagram & User Flow Documentation
**Project:** Stocksathi V2 (Inventory, ERP, AI Assistant)

This document maps out every micro-interaction a user goes through from starting the application to executing complex ERP tasks.

---

## Activity Flow 1: Complete Authentication & Registration (with OTP)

**Trigger:** User navigates to `/register.php` or `/index.php`

1. **Start:** User loads the web application.
2. **Action (User):** Clicks "Register New Organization".
3. **Action (User):** Fills in 10 input fields (Org Name, Org Email, GST, Admin Name, Password, etc.).
4. **Action (System - JS):** Frontend validation checks if passwords match and email is valid.
    *   *Alternative Path:* If validation fails, show inline red error text. User corrects data.
5. **Action (System - PHP):** Backend receives POST request via AJAX (`register-send-otp.php`).
6. **Condition (System):** Does email/username already exist in DB?
    *   *Yes:* Return JSON error "Email already exists". End flow.
    *   *No:* Proceed to next step.
7. **Action (System):** Generates a random 6-digit OTP code. Hashes the OTP. Stores form data in `$_SESSION['reg_pending']`.
8. **Action (System - Email):** Connects to SMTP server and dispatches an HTML formatted email containing the OTP to the Admin Email.
9. **UI State Change:** Registration form hides; OTP verification modal appears.
10. **Action (User):** Enters the 6-digit OTP received in their inbox.
11. **Condition (System):** Verifies OTP against Hash in Session.
    *   *Invalid:* Show "Incorrect OTP".
    *   *Expired ( > 15 mins):* Show "OTP Expired" -> Trigger Resend OTP -> Go back to Step 7.
    *   *Valid:* Proceed to final DB insertion.
12. **Action (System - DB):** 
    *   INSERT into `organizations` table. Get `org_id`.
    *   INSERT into `users` table with hashed password, assigned `org_id`, and `role = 'super_admin'`.
13. **End:** Display Success SweetAlert, redirect to `/index.php` for login.

---

## Activity Flow 2: POS Billing & Inventory Deduction (Core Engine)

**Trigger:** Sales Executive clicks "Create Invoice" on Dashboard.

1. **Start:** User lands on `create-invoice.php`.
2. **Action (System):** Queries DB for all active products where `stock_quantity > 0` for the user's `organization_id`.
3. **Action (User):** Scans a barcode OR types SKU in the search bar.
4. **Action (System - AJAX):** Fetches product details (Name, Price, Max Stock) instantly.
5. **Action (User):** Adds item to Cart. Modifies quantity (e.g., changes from 1 to 5).
6. **Condition (System - JS):** Is entered quantity <= available `stock_quantity`?
    *   *No:* Show warning: "Insufficient stock. Only X available." User adjusts quantity.
    *   *Yes:* Proceed.
7. **Action (System - JS):** Calculate Subtotal (`price * quantity`) + Tax (GST 18%) = Total Amount. Updates UI dynamically.
8. **Action (User):** Enters Customer Name and Phone Number. Clicks "Confirm & Generate Invoice".
9. **Action (System - DB Transaction Begins):**
    *   *Step A:* INSERT into `invoices` (customer info, total_amount, user_id). Get `invoice_id`.
    *   *Step B:* Loop through Cart. For each item: INSERT into `invoice_items` (invoice_id, product_id, qty, price).
    *   *Step C:* **Critical Update:** UPDATE `products` SET `stock_quantity = stock_quantity - qty` WHERE `id = product_id`.
10. **Condition (System - DB):** Did any SQL query fail?
    *   *Yes:* Rollback Transaction. Show Error.
    *   *No:* Commit Transaction.
11. **Action (System):** Checks if any deducted product has hit the `minimum_stock_alert` threshold. If yes, generate an alert payload for the Dashboard.
12. **End:** Display Invoice Summary page with a button to "Print/Download PDF".

---

## Activity Flow 3: AI Assistant Troubleshooting Execution

**Trigger:** User encounters a problem (e.g., "Export to Excel failed") and opens AI Chat.

1. **Start:** User clicks the floating AI icon in the bottom right corner.
2. **UI State:** Chat interface slides out. AI greets: "Hello [Name], how can I help you manage Stocksathi today?"
3. **Action (User):** Types: "Why is the Excel report not downloading? It gives an HTTP 500 error."
4. **Action (System - Backend):** The chat message is sent via API to the `AIAssistant` class.
5. **Action (System - AI Logic):**
    *   AI detects intent: `Troubleshooting / Error Analysis`.
    *   AI triggers internal script to read the last 50 lines of the Apache `php_error_log` or `error.log`.
6. **Action (System - Internal):** Log file is parsed. Finds string: `Fatal error: Uncaught Error: Class 'PhpOffice\PhpSpreadsheet\Spreadsheet' not found in file.php on line 42`.
7. **Action (System - AI Logic):** AI formulates a response based on the error.
8. **Action (System - Output):** AI replies to the user in the chat window:
    > "I found the issue. The system is trying to generate an Excel file, but the **PhpSpreadsheet library is missing** on your server. \n\n**To fix this:** Please run `composer require phpoffice/phpspreadsheet` in your terminal, or ask your server administrator to install it."
9. **End:** User is provided with a direct, accurate resolution without needing manual debugging. Flow terminates until next message.
