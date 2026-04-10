# Stocksathi V2 - Activity Diagram Flow Documentation

This document provides a detailed step-by-step textual explanation of the Activity Diagram for the Stocksathi Inventory Management System, including the newly integrated AI Chatbot features.

## 1. System Entry & Authentication Flow
The initial flow ensures secure access to the application using a Two-Factor Authentication (OTP) mechanism.

*   **Step 1: User Enters Credentials**
    The user (Super Admin, Manager, or Employee) opens the login page and submits their Email address and Password.
*   **Step 2: Validate Credentials (Decision Node)**
    The system checks the database to verify if the email exists and the password hash matches.
    *   **If Invalid (No):** The system displays an error message ("Invalid email or password") and stops the process, asking the user to try again.
    *   **If Valid (Yes):** The system proceeds to generate a unique 6-digit OTP.
*   **Step 3: Generate & Send OTP**
    The system securely emails the generated OTP to the user's registered email address and redirects them to the OTP Verification screen.
*   **Step 4: User Enters OTP**
    The user checks their email and inputs the 6-digit code into the system.
*   **Step 5: Verify OTP (Decision Node)**
    The system checks if the OTP matches the database record and has not expired (usually valid for 10-15 minutes).
    *   **If Invalid/Expired (No):** The system displays an error and provides an option to resend the OTP.
    *   **If Valid (Yes):** Authentication is complete.
*   **Step 6: Login Successful**
    The system creates a secure session and routes the user to their respective Role-Based Dashboard (e.g., Super Admin sees all data, Sales Executive sees only POS/Invoices).

## 2. Main Dashboard & Routing Flow
Once securely logged in, the user interacts with the core ERP features.

*   **Step 7: Dashboard Overview**
    The system loads key metrics: Total Sales, Low Stock Alerts, Active Users, and Revenue Charts.
*   **Step 8: User Action (Branching Node)**
    The user decides which module to interact with based on their current task and role permissions.

### Branch A: Manage Inventory
*   **Step 9A:** User navigates to the Inventory/Products section.
*   **Step 10A:** User performs an action (Add new product, Update stock quantity, or Edit pricing).
*   **Step 11A:** The system successfully updates the MySQL database and returns back to the Dashboard (Step 7).

### Branch B: Generate Invoice (Billing)
*   **Step 9B:** User navigates to the POS/Billing section.
*   **Step 10B:** User selects products, adjust quantities, adds customer details, and applies GST/taxes.
*   **Step 11B:** System verifies sufficient stock levels.
*   **Step 12B:** The system generates a final PDF invoice and deducts the sold items from the main inventory. Flow returns to the Dashboard (Step 7).

## 3. Dedicated AI Assistant Flow (Next-Gen Feature)
This flow outlines how users interact with the built-in AI for troubleshooting or data analysis.

*   **Step 9C: Open AI Chatbot**
    The user clicks on the floating AI Assistant widget on their screen or navigates to the dedicated AI page.
*   **Step 10C: Submit Natural Language Query (Ask Question/Report Bug)**
    The user types a request in plain English. Examples:
    *   *Analytic Query:* "What is my total sales revenue for this week?"
    *   *Bug/Fix Query:* "Why did my last invoice fail to generate?"
*   **Step 11C: AI Analyzes Context**
    The AI system receives the query. Depending on the request, it will:
    *   Translate the text into a safe SQL query to gather data.
    *   Scan recent PHP/Apache error logs to find the root cause of the reported bug.
*   **Step 12C: AI Provides Intelligence/Fixes Code**
    The AI responds directly in the chat window with accurate data, a step-by-step solution, or even executable code fixes.
*   **Step 13C: Action Complete**
    The user applies the information or fix, and the flow returns to the Dashboard (Step 7) for continued operations.

---
**Document Status:** Finalized  
**Applies To:** Stocksathi V2 (AI Integrated ERP Architecture)
