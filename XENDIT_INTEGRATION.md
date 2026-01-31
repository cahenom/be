# Xendit Integration for Deposit Functionality

## Overview
This implementation adds a deposit feature to the user's wallet using Xendit's Invoice API.

## Setup Instructions

### 1. Install Xendit SDK (optional, using Guzzle directly)
If you prefer to use the official Xendit SDK, install it via Composer:
```bash
composer require xendit/xendit-php
```

Currently, the implementation uses Guzzle HTTP client directly to interact with Xendit API.

### 2. Environment Configuration
Add these variables to your `.env` file:
```
XENDIT_API_KEY=your_xendit_secret_api_key
XENDIT_CALLBACK_SECRET=your_xendit_callback_secret
```

### 3. Database Migration
Run the migration to create the deposits table:
```bash
php artisan migrate
```

### 4. Webhook Configuration
Configure Xendit to send webhooks to your endpoint:
- URL: `https://yourdomain.com/api/xendit/webhook`
- Events to listen: `invoice.paid`

## API Endpoints

### Create Deposit Invoice
- Method: `POST`
- URL: `/api/user/deposit`
- Headers: `Authorization: Bearer {token}`, `Content-Type: application/json`
- Body:
```json
{
  "amount": 50000
}
```

### Get User Balance
- Method: `POST`
- URL: `/api/user/balance`
- Headers: `Authorization: Bearer {token}`, `Content-Type: application/json`

## How It Works

1. User initiates a deposit via the `/user/deposit` endpoint
2. System creates an invoice with Xendit
3. Xendit returns an invoice URL for the user to complete payment
4. When payment is completed, Xendit sends a webhook to our callback endpoint
5. Our system updates the deposit status and increases user's wallet balance

## Models
- `Deposit` model tracks all deposit transactions
- `User` model has a `saldo` field for wallet balance and a relationship to deposits

## Security
- Webhook signature verification is implemented
- All endpoints require authentication
- Input validation is performed on all requests