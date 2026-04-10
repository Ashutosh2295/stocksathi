# Stocksathi - Comprehensive Test Cases (Including AI Features)

This document contains a full test suite for the Stocksathi Inventory Management System to ensure robustness, security, and a seamless user experience.

---

## 1. Authentication & Integrity Testing
| Test ID | Test Scenario | Steps to Execute | Expected Result | Pass/Fail |
|---|---|---|---|---|
| AUTH-01 | Secure User Login | 1. Navigate to `/index.php` 2. Enter valid email and password 3. Click Login | Redirected to appropriate dashboard based on Role. | |
| AUTH-02 | Invalid User Login | 1. Enter valid email but wrong password 2. Click Login | Error: "Invalid credentials" displayed. Login fails. | |
| AUTH-03 | Two-Factor Registration OTP | 1. Fill registration form details 2. Submit form 3. Enter received email OTP | Registration completes and saves data securely in DB. | |
| AUTH-04 | User Password Reset | 1. Click "Forgot Password" 2. Enter email 3. Submit OTP and new password | Password updated in database Hash natively. | |

## 2. Organization & Multi-Tenancy Testing
| Test ID | Test Scenario | Steps to Execute | Expected Result | Pass/Fail |
|---|---|---|---|---|
| ORG-01 | Create New Organization | 1. Admin logs in 2. Navigates to Organization Setup 3. Fills details and submits | Organization created, unique Organization ID assigned. | |
| ORG-02 | Data Isolation Check (CRITICAL) | 1. Log in as User in Organization A 2. Attempt to view Invoice from Organization B via URL injection | Access Denied. System blocks viewing cross-tenant data. | |
| ORG-03 | Employee Role Creation | 1. Navigate to Users 2. Assign "Sales Executive" role | New user created with limited sidebar permissions. | |

## 3. Inventory & Stock Testing
| Test ID | Test Scenario | Steps to Execute | Expected Result | Pass/Fail |
|---|---|---|---|---|
| INV-01 | Add New Product | 1. Navigate to Inventory -> Add Product 2. Enter SKU, Price, Stock 3. Submit | Product appears in inventory listing immediately. | |
| INV-02 | Low Stock Trigger | 1. Create Invoice selling 90 items of a stock (Total: 100) | Dashboard displays a Low Stock warning (10 items left). | |
| INV-03 | Prevent Negative Stock | 1. Override HTML input and attempt to sell 50 items when stock is 10 | Transaction failed. Error: "Insufficient stock quantity." | |

## 4. Invoicing & Billing Testing
| Test ID | Test Scenario | Steps to Execute | Expected Result | Pass/Fail |
|---|---|---|---|---|
| INV-01 | Generate Sales Invoice | 1. Select 2 Products 2. Add customer details 3. Configure 18% GST 4. Submit | Invoice generated with accurate tax breakdown. | |
| INV-02 | PDF Export Functionality | 1. Open completed invoice 2. Click "Download PDF" | Clean, professional PDF downloaded with brand logo. | |

## 5. AI Assistant Chatbot Testing (NEW)
| Test ID | Test Scenario | Steps to Execute | Expected Result | Pass/Fail |
|---|---|---|---|---|
| AI-01 | Natural Language Query (NLP) | 1. Open AI Bot 2. Ask "What is the total revenue for this month?" | AI analyzes DB and responds with accurate total sum. | |
| AI-02 | Bug Fixing & Diagnostics | 1. Purposely trigger a PHP error 2. Ask AI "Why did my previous action fail?" | AI reads recent Apache/PHP error logs and suggests the code fix. | |
| AI-03 | Stock Prediction Analysis | 1. Ask AI "Analyze my inventory for next month." | AI identifies fast-moving items and suggests restocking quantities. | |

---
**Status:** All critical tests established for Q3 Development Cycle.
