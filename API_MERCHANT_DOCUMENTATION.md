# Merchant Payment API Documentation

This document describes the API endpoints for the merchant payment system that allows merchants to send payment requests to users and enables users to approve/reject these requests.

## Public API Endpoints (No Authentication Required)

### Send Payment Request to User
- **Endpoint**: `POST /api/merchant`
- **Description**: Allows merchants to send a payment request to a user. When called, this endpoint will:
  1. Validate the payment request data
  2. Find the user by email
  3. Store the payment request in the database
  4. Send an FCM notification to the user
- **Request Body**:
  ```json
  {
    "name": "string (required)",           // Name of the payment/service
    "id": "string (required)",             // External ID from merchant system
    "destination": "string (required)",    // Destination or recipient
    "price": "number (required)",          // Price amount
    "email": "string (required, email)"    // Email of the user to notify
  }
  ```
- **Response** (Success - 200):
  ```json
  {
    "message": "Payment request sent successfully",
    "payment_request_id": 1,
    "payment_details": {
      "name": "string",
      "id": "string",
      "destination": "string",
      "price": "number",
      "email": "string"
    }
  }
  ```
- **Response** (Error - 400, 404, 500):
  ```json
  {
    "error": "string",
    "messages": {} // Validation errors if any
  }
  ```

## Protected API Endpoints (Authentication Required)

### Get User's Pending Payment Requests
- **Endpoint**: `GET /api/payment-requests/pending`
- **Description**: Retrieves all pending payment requests for the authenticated user
- **Headers**: 
  - `Authorization: Bearer {token}`
- **Response** (Success - 200):
  ```json
  {
    "payment_requests": [
      {
        "id": 1,
        "external_id": "string",
        "name": "string",
        "destination": "string",
        "price": "decimal",
        "email": "string",
        "status": "pending",
        "created_at": "datetime"
      }
    ]
  }
  ```

### Get Specific Payment Request
- **Endpoint**: `GET /api/payment-requests/{id}`
- **Description**: Retrieves a specific payment request for the authenticated user
- **Headers**: 
  - `Authorization: Bearer {token}`
- **Parameters**:
  - `id` (path): Payment request ID
- **Response** (Success - 200):
  ```json
  {
    "payment_request": {
      "id": 1,
      "external_id": "string",
      "name": "string",
      "destination": "string",
      "price": "decimal",
      "email": "string",
      "status": "string",
      "created_at": "datetime"
    }
  }
  ```

### Approve Payment Request
- **Endpoint**: `POST /api/payment-requests/{id}/approve`
- **Description**: Approves a pending payment request
- **Headers**: 
  - `Authorization: Bearer {token}`
- **Parameters**:
  - `id` (path): Payment request ID
- **Response** (Success - 200):
  ```json
  {
    "message": "Payment request approved successfully",
    "payment_request": { /* payment request object */ }
  }
  ```

### Reject Payment Request
- **Endpoint**: `POST /api/payment-requests/{id}/reject`
- **Description**: Rejects a pending payment request
- **Headers**: 
  - `Authorization: Bearer {token}`
- **Parameters**:
  - `id` (path): Payment request ID
- **Response** (Success - 200):
  ```json
  {
    "message": "Payment request rejected successfully",
    "payment_request": { /* payment request object */ }
  }
  ```

## Flow Description

1. **Merchant sends payment request**: A merchant calls `POST /api/merchant` with payment details
2. **System validates and stores**: The system validates the data and creates a payment request record
3. **Notification sent**: An FCM notification is sent to the user with the payment details
4. **User receives notification**: The user sees the notification on their device
5. **User views request**: The user can call `GET /api/payment-requests/pending` to see pending requests
6. **User acts on request**: The user can approve or reject the request using the respective endpoints
7. **Status updated**: The system updates the payment request status accordingly

## Database Schema

The system uses a `payment_requests` table with the following columns:
- `id`: Primary key
- `external_id`: Unique identifier from the merchant system
- `name`: Name of the payment/service
- `destination`: Destination or recipient
- `price`: Amount to be paid
- `email`: Email of the user to notify
- `user_id`: Foreign key linking to the user (nullable)
- `status`: Current status (pending, approved, rejected, cancelled, completed)
- `expires_at`: Expiration time (nullable)
- `metadata`: Additional data as JSON (nullable)
- `created_at`, `updated_at`: Timestamps

## Security Considerations

- The merchant endpoint is public but should be secured with rate limiting
- Payment requests are tied to user accounts via email
- Users can only access their own payment requests
- Sensitive operations require authentication