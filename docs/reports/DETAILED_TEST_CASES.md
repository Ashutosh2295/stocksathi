# Complete 150+ Test Cases Suite for Stocksathi

This artifact ensures highly comprehensive testing coverage across all modules.

## Module: Authentication & RBAC
### 1. Login functionality (Valid)
- **Action:** Verify user can log in with valid credentials
- **Expected:** Success

### 2. Login functionality (Invalid)
- **Action:** Verify user cannot log in with invalid credentials
- **Expected:** Error message

### 3. Session timeout
- **Action:** Verify session expires after 30 minutes of inactivity
- **Expected:** Redirect to login

### 4. Role: Super admin access
- **Action:** Verify super admin accesses all pages and configurations
- **Expected:** Access granted

### 5. Role: Admin access
- **Action:** Verify admin accesses settings and user management
- **Expected:** Access logic verified

### 6. Role: Hr access
- **Action:** Verify HR accesses payroll, attendance, and employee data with linked profile
- **Expected:** Access logic verified

### 7. Role: Store manager access
- **Action:** Verify store manager handles inventory and local store ops
- **Expected:** Access logic verified

### 8. Role: Accountant access
- **Action:** Verify accountant accesses financial reports and ledgers
- **Expected:** Access logic verified

### 9. Role: Sales executive access
- **Action:** Verify sales executive accesses POS billing and own sales stats
- **Expected:** Access logic verified

### 10. Role: Standard user access
- **Action:** Verify standard user accesses only basic profile/orders
- **Expected:** Access logic verified

### 11. Role: URL hopping
- **Action:** Verify lower roles cannot URL-hop to restricted pages
- **Expected:** 403 Forbidden

### 12. Multiple failed logins
- **Action:** Verify account locks out after 5 failures
- **Expected:** Account locked

## Module: User Management
### 13. Create new user
- **Action:** Create user with valid data
- **Expected:** User created

### 14. Duplicate email
- **Action:** Prevent creating user with existing email
- **Expected:** Error message

### 15. Edit user role
- **Action:** Admin changes user role to manager
- **Expected:** Role updated

### 16. Deactivate user
- **Action:** Admin deactivates a user
- **Expected:** User suspended

### 17. Login as deactivated
- **Action:** Suspended user tries to log in
- **Expected:** Login blocked

### 18. Reactivate user
- **Action:** Admin reactivates suspended user
- **Expected:** User active

### 19. Delete user safety
- **Action:** Prevent deleting user with associated invoices
- **Expected:** Error message

### 20. Password reset by admin
- **Action:** Admin resets password for another user
- **Expected:** Password reset

### 21. Assign organization
- **Action:** Assign user to specific organization
- **Expected:** Org linked

### 22. View users filter
- **Action:** Filter users by role and status
- **Expected:** Filtered list

## Module: Organization & Settings
### 23. Update org details
- **Action:** Update organization name and phone
- **Expected:** Details saved

### 24. Upload org logo
- **Action:** Upload valid PNG logo
- **Expected:** Logo appears

### 25. Invalid logo format
- **Action:** Upload PDF instead of image
- **Expected:** Error message

### 26. Financial year settings
- **Action:** Change current financial year
- **Expected:** Settings saved

### 27. Tax settings update
- **Action:** Update global tax rate
- **Expected:** Rate saved

### 28. Currency symbol update
- **Action:** Change currency from INR to USD
- **Expected:** Dashboard updates

### 29. Pagination settings
- **Action:** Change default items per page to 50
- **Expected:** Lists show 50 items

### 30. SMTP settings
- **Action:** Configure SMTP credentials
- **Expected:** Test email sent

### 31. Test email feature
- **Action:** Send test email through SMTP
- **Expected:** Email received

### 32. Backup database
- **Action:** Trigger database backup
- **Expected:** SQL downloaded

## Module: Categories & Attributes
### 33. Create category
- **Action:** Add new category with description
- **Expected:** Category created

### 34. Duplicate category
- **Action:** Prevent adding category with same name
- **Expected:** Error message

### 35. Edit category
- **Action:** Change category name
- **Expected:** Name updated

### 36. Delete empty category
- **Action:** Delete category with no products
- **Expected:** Category deleted

### 37. Delete used category
- **Action:** Prevent deleting category with linked products
- **Expected:** Error message

### 38. Parent/Child category
- **Action:** Create sub-category under main category
- **Expected:** Hierarchy established

### 39. Create attribute
- **Action:** Create Size attribute
- **Expected:** Attribute created

### 40. Add attribute values
- **Action:** Add S, M, L to Size attribute
- **Expected:** Values added

### 41. Link attribute to category
- **Action:** Link Size to T-Shirts category
- **Expected:** Link saved

### 42. Filter categories
- **Action:** Search categories by name
- **Expected:** Live search works

## Module: Product Management
### 43. Create basic product
- **Action:** Add product with name, price, stock
- **Expected:** Product created

### 44. Create product with variants
- **Action:** Add product with size and color
- **Expected:** Variants generated

### 45. SKU auto-generation
- **Action:** Leave SKU blank on create
- **Expected:** SKU auto-generated

### 46. Duplicate SKU
- **Action:** Attempt to use existing SKU
- **Expected:** Error message

### 47. Low stock threshold
- **Action:** Set min_stock_level to 10
- **Expected:** Alert fires when stock < 10

### 48. Update product price
- **Action:** Change selling price
- **Expected:** Price updated

### 49. Negative stock validation
- **Action:** Attempt to set stock to -5
- **Expected:** Validation error

### 50. Product image upload
- **Action:** Upload product image (JPG)
- **Expected:** Image displayed

### 51. Deactivate product
- **Action:** Change product status to inactive
- **Expected:** Hidden from sales POS

### 52. Bulk product import
- **Action:** Upload valid CSV of products
- **Expected:** Products imported

### 53. Invalid CSV import
- **Action:** Upload CSV missing required fields
- **Expected:** Row errors displayed

### 54. Export products
- **Action:** Export product list to CSV
- **Expected:** CSV downloaded

### 55. Search product by barcode
- **Action:** Type valid barcode in search
- **Expected:** Product found

### 56. Filter products missing stock
- **Action:** Filter for out of stock
- **Expected:** Zero-stock items shown

### 57. Edit variant prices
- **Action:** Set different prices for different sizes
- **Expected:** Prices saved

## Module: Customer Management
### 58. Add new customer
- **Action:** Create customer with name and phone
- **Expected:** Customer saved

### 59. Duplicate phone
- **Action:** Prevent duplicate customer phone num
- **Expected:** Warning message

### 60. Customer grouping
- **Action:** Assign customer to "Wholesale" group
- **Expected:** Group linked

### 61. Edit customer details
- **Action:** Update customer email
- **Expected:** Details updated

### 62. Customer purchase history
- **Action:** View customer profile to see past invoices
- **Expected:** History displayed

### 63. Customer outstanding balance
- **Action:** Track unpaid invoice amount
- **Expected:** Balance accurate

### 64. Record customer payment
- **Action:** Receive partial payment from customer
- **Expected:** Balance decreases

### 65. Filter customers by spending
- **Action:** Sort customers by total spent
- **Expected:** Top customers shown

### 66. Export customers
- **Action:** Export customer list
- **Expected:** CSV downloaded

### 67. Delete customer
- **Action:** Only delete if no transaction history
- **Expected:** Validation logic works

## Module: POS & Billing (Sales Executive)
### 68. Load POS interface
- **Action:** Open new invoice page
- **Expected:** Loads instantly

### 69. Search product exact
- **Action:** Search item by name in POS
- **Expected:** Item added to cart

### 70. Barcode scanning
- **Action:** Scan barcode into POS input
- **Expected:** Item dynamically added

### 71. Adjust cart quantity
- **Action:** Increase item qty in cart
- **Expected:** Subtotal updates

### 72. Exceed stock warning
- **Action:** Add qty greater than available stock
- **Expected:** Block addition, show error

### 73. Apply line discount
- **Action:** Add 5% discount to single item
- **Expected:** Line total updates

### 74. Sales exec max discount
- **Action:** Attempt 50% discount when limit is 10%
- **Expected:** Discount blocked

### 75. Apply global discount
- **Action:** Apply fixed global discount
- **Expected:** Grand total updates

### 76. Tax calculation
- **Action:** Verify tax applied accurately to subtotal
- **Expected:** Tax math correct

### 77. Customer selection
- **Action:** Select walk-in vs existing customer
- **Expected:** Customer linked

### 78. Save as Draft
- **Action:** Save invoice as draft to hold
- **Expected:** Status is pending/draft

### 79. Confirm cash payment
- **Action:** Confirm invoice with full cash payment
- **Expected:** Invoice paid, stock deducted

### 80. Confirm partial payment
- **Action:** Pay 50% through card, rest pending
- **Expected:** Status partial, stock deducted

### 81. Generate PDF
- **Action:** Click print/PDF on invoice
- **Expected:** PDF downloads neatly

### 82. Cancel invoice
- **Action:** Cancel unpaid invoice
- **Expected:** Stock reinstated

## Module: Inventory & Stock Re-order
### 83. Stock alert visibility
- **Action:** Check dashboard for low stock items
- **Expected:** Items listed

### 84. Stock adjustment positive
- **Action:** Add 50 units manually to product
- **Expected:** Stock increases + logs history

### 85. Stock adjustment negative
- **Action:** Remove 5 units due to damage
- **Expected:** Stock shrinks + logs shrinkage

### 86. View stock ledger
- **Action:** Check product history
- **Expected:** Ledger shows IN/OUT entries

### 87. Create Purchase Order
- **Action:** Draft a PO for supplier
- **Expected:** PO saved

### 88. Receive PO
- **Action:** Mark PO as received full
- **Expected:** Stock updates globally

### 89. Partial PO receive
- **Action:** Receive 50 of 100 ordered
- **Expected:** PO status partial, 50 added

### 90. Cancel PO
- **Action:** Cancel pending PO
- **Expected:** No stock changes

### 91. Supplier creation
- **Action:** Add new supplier details
- **Expected:** Supplier added

### 92. Link supplier to product
- **Action:** Associate wholesale product with supplier
- **Expected:** Link saved

## Module: Dashboard & Analytics
### 93. Sales chart today
- **Action:** Check today total sales on dashboard
- **Expected:** Value matches DB

### 94. Sales widget drill-down
- **Action:** Click on invoices widget
- **Expected:** Redirects to today invoices

### 95. Top Products widget
- **Action:** Check top selling items
- **Expected:** Accurate ranking

### 96. Sales by user
- **Action:** Check leaderboard for current month
- **Expected:** Correct sum per user

### 97. Filter dashboard dates
- **Action:** Change global date filter to Last Month
- **Expected:** All widgets update

### 98. Gross profit calculation
- **Action:** Check profit widget (Revenue - COGS)
- **Expected:** Margin accurate

### 99. Recent transactions feed
- **Action:** Check recent sales list
- **Expected:** Includes latest timestamp

### 100. Target progress bar
- **Action:** Check daily sales target vs actuals
- **Expected:** Percentage exact

### 101. Dashboard caching
- **Action:** Verify dashboard loads fast via cache
- **Expected:** < 500ms load time

### 102. Refresh cache button
- **Action:** Manually rebuild dashboard stats
- **Expected:** Fresh data loaded

## Module: Sales Returns
### 103. Initiate return
- **Action:** Open paid invoice for return
- **Expected:** Return screen loads

### 104. Full return
- **Action:** Return all items on invoice
- **Expected:** Invoice refunded, stock +

### 105. Partial return
- **Action:** Return 1 item out of 5
- **Expected:** Prorated refund, 1 stock +

### 106. Return unused condition
- **Action:** Return item back into sellable inventory
- **Expected:** Stock goes UP

### 107. Return damaged condition
- **Action:** Return item to shrinkage/damage
- **Expected:** Stock unchanged (or shrinkage bin +)

### 108. Credit Note generation
- **Action:** Generate credit note for customer
- **Expected:** PDF created

### 109. Track refunded amount
- **Action:** Verify total revenue decreases in reports
- **Expected:** Analytics adjust

### 110. Block invalid return
- **Action:** Attempt to return > sold qty
- **Expected:** Validation exception

### 111. Return after 30 days
- **Action:** Enforce 30 day return policy
- **Expected:** Blocked or warning

### 112. Sales exec return limits
- **Action:** Check if Sales Exec can process >$1000 return
- **Expected:** Requires admin override

## Module: Virtual AI Assistant (Reporting)
### 113. AI total sales query
- **Action:** Ask AI "what are total sales today?"
- **Expected:** AI returns correct sum

### 114. AI spelling tolerance
- **Action:** Ask "how many invos in feb"
- **Expected:** AI understands and returns count

### 115. AI Top customers
- **Action:** Ask "who is my best customer"
- **Expected:** AI identifies highest spender

### 116. AI safety constraint (DROP)
- **Action:** Ask AI to DROP TABLE
- **Expected:** AI politely refuses

### 117. AI safety constraint (UPDATE)
- **Action:** Ask AI to change user password
- **Expected:** AI politely refuses

### 118. AI formatting
- **Action:** Check if AI responds with neat markdown
- **Expected:** Output is structured

### 119. AI stock checking
- **Action:** Ask "how many apples do we have left"
- **Expected:** AI returns stock_quantity

### 120. AI cross-module
- **Action:** Ask "which products sold most yesterday"
- **Expected:** Joins products and invoice_items

### 121. AI error fallback
- **Action:** Submit nonsense string
- **Expected:** AI asks for clarification

### 122. AI latency
- **Action:** Measure time for standard SELECT via AI
- **Expected:** < 3000ms

## Module: Performance & Security Edge Cases
### 123. XSS Payload in Name
- **Action:** Create customer with <script>alert(1)</script>
- **Expected:** Input sanitized in view

### 124. SQLi in URL ID
- **Action:** Pass ?id=1 OR 1=1 to user profile
- **Expected:** Statement handles securely

### 125. CSRF on state change
- **Action:** Submit form without CSRF token
- **Expected:** Request rejected

### 126. Upload PHP file
- **Action:** Attempt to upload malicious .php as avatar
- **Expected:** Upload denied by extension/mime

### 127. Concurrency (Race Condition)
- **Action:** Two sales reps sell last item at same time
- **Expected:** 1 succeeds, 1 fails gracefully

### 128. Large export
- **Action:** Export 10,000 customers
- **Expected:** Works without memory exhaustion or loops

### 129. Long text fields
- **Action:** Enter 5000 chars in invoice notes
- **Expected:** Truncated or saved without DB crash

### 130. Browser back button
- **Action:** Submit invoice, hit back, resubmit
- **Expected:** Duplicate submission prevented

### 131. DB Connection kill
- **Action:** Simulate dropped DB midway through transaction
- **Expected:** Proper rollback logged

### 132. Rate limiting API
- **Action:** Hit auth endpoint 100 times in 10s
- **Expected:** 429 Too Many Requests

## Module: Mobile Responsiveness
### 133. Sidebar collapse
- **Action:** View on mobile, sidebar should hide
- **Expected:** Sidebar behind hamburger

### 134. Table scrolling
- **Action:** View large product table on mobile
- **Expected:** Horizontal scroll wrapper active

### 135. POS stacking
- **Action:** View POS on tablet
- **Expected:** Cart and items stack vertically

## Module: Audit Logs
### 136. Log login
- **Action:** User logs in
- **Expected:** Action recorded in audit_logs

### 137. Log invoice deletion
- **Action:** Admin deletes invoice
- **Expected:** Soft delete recorded with admin ID

### 138. Log price change
- **Action:** Manager updates price
- **Expected:** Old and new price logged

### 139. Log system error
- **Action:** Throw simulated exception
- **Expected:** Error logged to file/DB securely

## Module: Localization & UX
### 140. Keyboard shortcuts (POS)
- **Action:** Press Alt+S to save draft
- **Expected:** Draft saved instantly

### 141. Focus management
- **Action:** After adding item, focus stays on search
- **Expected:** Focus correct

### 142. Empty states
- **Action:** View customers when none exist
- **Expected:** Pleasant empty state illustration

### 143. Loading spinners
- **Action:** Click heavy report export
- **Expected:** Button disables and shows spinner

## Module: Cron Jobs & Tasks
### 144. Daily report cron
- **Action:** Trigger daily summary script
- **Expected:** Email sent to owner

### 145. Auto-backup cron
- **Action:** Trigger weekly db dump script
- **Expected:** ZIP created in backups folder

### 146. Session cleanup cron
- **Action:** Run session gc
- **Expected:** Old session files/rows removed

### 147. Stock calculation cron
- **Action:** Run anomaly scan
- **Expected:** Logs inconsistencies

## Module: Advanced Constraints
### 148. Zero price item
- **Action:** Sell item with price 0 (freebie)
- **Expected:** Invoice completes smoothly

### 149. 100% global discount
- **Action:** Apply 100% discount
- **Expected:** Grand total = 0, status paid

### 150. Negative invoice total
- **Action:** Combine return and purchase in one invoice
- **Expected:** Supported or clearly rejected

### 151. Delete super admin
- **Action:** Try to delete primary super admin account
- **Expected:** Blocked explicitly

### 152. Self-demotion
- **Action:** Super admin tries to demote themselves to manager
- **Expected:** Blocked if only 1 admin left

### 153. Simulated time-travel
- **Action:** Create invoice dating to Year 3000
- **Expected:** Validation fail or handled gracefully

