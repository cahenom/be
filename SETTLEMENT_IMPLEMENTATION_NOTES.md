# Merchant Settlement Implementation Notes

## Overview
This document explains the implementation of the 3-day settlement period for merchant balances.

## Changes Made

### 1. Database Migration
- Created migration `2026_01_29_002207_add_settlement_fields_to_payment_requests_table.php`
- Added three new fields to the `payment_requests` table:
  - `settled_at`: Timestamp when the payment was settled to merchant (nullable)
  - `settlement_status`: Enum field with values ['pending_settlement', 'settled', 'cancelled'] (default: 'pending_settlement')
  - `settlement_due_date`: Date when settlement is due (3 days after approval) (nullable)

### 2. PaymentRequest Model Updates
- Added new fields to `$fillable` array
- Added casts for the new datetime fields
- Added `getSettlementStatusOptions()` method to define possible settlement statuses
- Added `isEligibleForSettlement()` method to check if a payment request is eligible for settlement

### 3. PaymentRequestController Updates
- Modified `approvePaymentRequest()` method to implement 3-day settlement delay
- When a payment request is approved, funds are deducted from user's balance immediately
- Merchant's balance is NOT updated immediately; instead, the payment request is marked as 'pending_settlement'
- The settlement due date is set to 3 days after approval
- A webhook notification is sent to the merchant with status 'completed_pending_settlement'

### 4. Settlement Processing Command
- Created `ProcessSettlements` Artisan command (`app:process-settlements`)
- Command finds all payment requests eligible for settlement (pending_settlement status and due date passed)
- Adds the payment amount to the merchant's balance
- Updates the payment request status to 'settled'
- Logs the settlement processing results

### 5. Scheduled Task
- Configured the settlement command to run daily using Laravel's task scheduler
- The command will automatically process all due settlements

### 6. Merchant Model Updates
- Added `getTotalPendingSettlementsAttribute()` accessor to calculate total pending settlements
- Added `getAvailableBalanceAttribute()` accessor to calculate available balance (current saldo minus pending settlements)

## How the Settlement Process Works

1. User approves a payment request
2. User's balance is immediately reduced (within database transaction for safety)
3. Payment request is marked with:
   - `settlement_status` = 'pending_settlement'
   - `settlement_due_date` = current date + 3 days
   - `status` = 'success'
4. Merchant does NOT receive funds immediately
5. Daily scheduled task runs and processes all payment requests where:
   - `settlement_status` = 'pending_settlement'
   - `settlement_due_date` <= current date
6. For each eligible payment request:
   - Database transaction ensures atomicity: merchant's balance is increased AND payment request status is updated together
   - If any part fails, the entire operation is rolled back
   - Payment request is updated to `settlement_status` = 'settled' and `settled_at` = current time

## Running the Implementation

### Step 1: Run the Migration
```bash
php artisan migrate
```

### Step 2: Test the Settlement Process
1. Create a payment request through the merchant API
2. Have a user approve the payment request
3. Verify that the user's balance is reduced but the merchant's balance is NOT increased
4. Wait 3 days or manually update the `settlement_due_date` to a past date
5. Run the settlement command:
   ```bash
   php artisan app:process-settlements
   ```
6. Verify that the merchant's balance is now increased with the payment amount

### Step 3: Enable the Scheduler
Add the following to your crontab to run Laravel's scheduler every minute:
```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Important Notes

- The system maintains data integrity using database transactions
- If a merchant cannot be found for a payment request during settlement, the settlement is marked as 'cancelled'
- The merchant's available balance calculation excludes pending settlements
- Webhook notifications are sent to merchants about pending settlements
- All settlement activities are logged for audit purposes
- Double spending prevention: System checks for recent transactions with same customer number and SKU within last 10 minutes
- Query optimization: Implemented eager loading to prevent N+1 problems and improve performance
- Database optimization: Added indexes to frequently queried columns for improved performance