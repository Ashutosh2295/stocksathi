# StockSathi - Panel Architecture & Role Access Documentation

## 1. Overview
StockSathi utilizes a **Multi-Panel Architecture** designed to separate concerns and enforce strict security boundaries. The system is divided into **five distinct panels**, each tailored to a specific operational role within the organization. This ensures that users only see data and options relevant to their job functions.

---

## 2. Defined Panels and Roles
The system defines the following five primary panels. Access is strictly controlled via the `PermissionMiddleware`.

### 1. Super Admin Panel
*   **Role Identifier:** `super_admin`
*   **Target User:** Business Owners, System Administrators.
*   **Purpose:** The "God-mode" of the system. Complete control over every aspect of the application.
*   **Key Capabilities:**
    *   **User Management:** Create/Delete Admins, Store Managers, and Staff.
    *   **System Configuration:** Change global settings, logic, and backup data.
    *   **Financial Oversight:** View profit/loss, revenue, and all financial reports.
    *   **Full Access:** Can perform any action that any other role can do.

### 2. Admin Panel
*   **Role Identifier:** `admin`
*   **Target User:** General Managers, Head Office Staff.
*   **Purpose:** Operational management without the danger of destroying system settings or deleting critical users.
*   **Key Capabilities:**
    *   **Inventory Control:** Add/Edit products, brands, and categories.
    *   **Stock Monitoring:** View stock levels across all branches/warehouses.
    *   **Sales Reporting:** Analyze sales performance.
    *   **Restriction:** Cannot delete other Admin accounts or change system core settings.

### 3. Store Manager Panel
*   **Role Identifier:** `store_manager`
*   **Target User:** Branch Managers, Warehouse Supervisors.
*   **Purpose:** Manage the day-to-day operations of a specific physical location.
*   **Key Capabilities:**
    *   **Stock Operations:** Manage Stock In (GRN) and Stock Adjustments (for breakage/theft).
    *   **Store Sales:** Monitor sales specific to their assigned store.
    *   **Staff Oversight:** View basic performance metrics of sales executives in their store.
    *   **Expense Submission:** Submit store-related expenses (electricity, rent) for approval.

### 4. Sales Executive Panel
*   **Role Identifier:** `sales_executive`
*   **Target User:** Counter Staff, Cashiers, Sales Reps.
*   **Purpose:** High-speed transaction processing. Purely functional for daily sales.
*   **Key Capabilities:**
    *   **Billing/Invoicing:** Create new invoices and quotations.
    *   **Customer Management:** Add new customers or update contact details.
    *   **Stock Check:** View "Available Quantity" only (cannot see cost prices).
    *   **Restriction:** No access to reports, expenses, or settings.

### 5. Accountant Panel
*   **Role Identifier:** `accountant`
*   **Target User:** Finance Officers, Chartered Accountants.
*   **Purpose:** Manage money flow, taxes, and approvals.
*   **Key Capabilities:**
    *   **Expense Approval:** Review, Approve, or Reject expenses submitted by Store Managers.
    *   **GST Reporting:** Generate GSTR-1 and GSTR-3B compatible reports.
    *   **Ledgers:** View supplier and customer ledgers (Credits/Debits).
    *   **Profitability Analysis:** View margins and net profit.

---

## 3. Module Connectivity & Data Flow
This section explains how these panels interact with the underlying database modules.

### A. The "Sales to Stock" Connection
*   **Actors:** Sales Executive, Store Manager, Admin.
*   **Flow:**
    1.  **Sales Executive** creates an `Invoice`.
    2.  System automatically deducts quantity from `Stock`.
    3.  **Store Manager** sees the valid stock level decrease in real-time.
    4.  **Admin** sees the revenue increase in the Dashboard.

### B. The "Expense Approval" Connection
*   **Actors:** Store Manager, Accountant, Super Admin.
*   **Flow:**
    1.  **Store Manager** posts an `Expense` (e.g., "Office Decor - $500").
    2.  Status is set to `PENDING`.
    3.  **Accountant** gets a notification/sees it in their "Pending Approvals" list.
    4.  **Accountant** validates the receipt and clicks **APPROVE**.
    5.  The amount is deducted from the company's financial overview (visible to **Super Admin**).

### C. The "Replenishment" Connection
*   **Actors:** Store Manager, Admin.
*   **Flow:**
    1.  **Store Manager** notices an item is low stock and requests stock.
    2.  **Admin** creates a `Purchase Order` or `Stock Transfer`.
    3.  Stock arrives, and **Store Manager** creates a `Stock In` entry.
    4.  Inventory levels update immediately across the system.

---

## 4. Role Permission Matrix (Access Control)
Detailed breakdown of who can do what.

| Feature / Action | Super Admin | Admin | Store Manager | Sales Exec | Accountant |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **DASHBOARD ACCESS** | | | | | |
| View Exec Dashboard | ✅ | ✅ | ✅ | ✅ | ❌ |
| View Financial Stats | ✅ | ✅ | ❌ | ❌ | ✅ |
| **INVENTORY** | | | | | |
| View Products | ✅ | ✅ | ✅ | ✅ | ✅ |
| View Cost Price | ✅ | ✅ | ❌ | ❌ | ✅ |
| Create/Edit Products | ✅ | ✅ | ❌ | ❌ | ❌ |
| Delete Products | ✅ | ❌ | ❌ | ❌ | ❌ |
| **STOCK** | | | | | |
| Stock In (Purchase) | ✅ | ✅ | ✅ | ❌ | ❌ |
| Stock Adjustment | ✅ | ✅ | ✅ | ❌ | ❌ |
| View Stock Levels | ✅ | ✅ | ✅ | ✅ | ❌ |
| **SALES** | | | | | |
| Create Invoice | ✅ | ✅ | ✅ | ✅ | ❌ |
| Edit/Cancel Invoice | ✅ | ✅ | ❌ | ❌ | ❌ |
| View Sales Reports | ✅ | ✅ | ✅ (Own) | ✅ (Own) | ✅ |
| **FINANCE** | | | | | |
| View Expenses | ✅ | ✅ | ✅ (Own) | ❌ | ✅ |
| Create Expenses | ✅ | ✅ | ✅ | ❌ | ❌ |
| Approve Expenses | ✅ | ✅ | ❌ | ❌ | ✅ |
| View Net Profit | ✅ | ❌ | ❌ | ❌ | ✅ |
| **SYSTEM** | | | | | |
| Manage Users | ✅ | ✅ | ❌ | ❌ | ❌ |
| System Settings | ✅ | ❌ | ❌ | ❌ | ❌ |

---

## 5. Technical Implementation Note
All access control is managed via the **`PermissionMiddleware.php`** class.
*   **Sessions:** When a user logs in, their `role` is stored in the PHP Session.
*   **Check:** Before loading any page, the system runs `PermissionMiddleware::check('required_permission')`.
*   **Redirect:** If the user lacks the role/permission, they are redirected to a `403 Access Denied` page.

This ensures security is enforced at the server level, not just by hiding buttons in the UI.
