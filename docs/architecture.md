# PaymentFlow Architecture

## Goal
A multi-tenant payments backend where organizations can create customers,
initiate payments, receive webhooks, and process asynchronous jobs.

## Core Entities
- User
- Organization
- Membership
- Customer
- Payment
- PaymentAttempt
- WebhookEvent

## Main Rules
- A user can belong to multiple organizations.
- Payments belong to an organization.
- Users must never access another organization's payments.
- Payment requests must support idempotency.
- External payment provider responses cannot be trusted blindly.

## Initial Architecture

Client
   |
   v
Laravel API
   |
   v
PostgreSQL

Later:

Laravel API
   |
   +--> Redis
   |
   +--> Queue / SQS
            |
            v
        Go Worker
            |
            v
     Payment Provider
