# PaymentFlow

PaymentFlow is a multi-tenant payments backend built with Laravel. It currently
supports multi-tenant payments, tenant isolation, idempotent payment creation,
explicit payment state transitions, payment attempts, asynchronous queue-based
payment processing, retryable provider failures, retry backoff, and failed-job
handling.

> This project is under active development. The current backend implements the core payment lifecycle, mock provider processing, payment attempts, asynchronous jobs, retryable failures, and retry backoff. Redis, real provider integrations, webhooks, and observability are planned next.

## Features

- Multi-tenant organization, membership, and customer models
- Organization-scoped payment creation
- Protection against cross-organization customer usage
- Idempotent payment requests through the `Idempotency-Key` header
- Unique payment references
- Integer-based monetary amounts
- Explicit payment lifecycle rules
- Payment attempt history
- Mock payment provider with success, decline, and timeout scenarios
- Asynchronous payment processing with Laravel queues
- Retryable provider failures with backoff
- Failed-job handling after retry exhaustion
- Feature tests covering payments, tenant isolation, idempotency, payment attempts, lifecycle transitions, processing, and queue jobs

## Payment lifecycle

Payments follow a one-way state machine:

```text
pending -> processing -> succeeded
                      -> failed
```

Terminal payments cannot transition back to an earlier state. Invalid
transitions throw a `DomainException`.

## Tech stack

- PHP 8.5
- Laravel
- PostgreSQL 16
- Laravel database queue
- PHPUnit
- Vite

## Getting started

### Prerequisites

- PHP 8.3 or newer
- Composer
- Node.js and npm
- The PHP SQLite extension, or another database supported by Laravel

### Installation

Clone the repository and run the Laravel setup script:

```bash
git clone git@github.com:miguelcalderons/paymentflow.git
cd paymentflow/backend
composer run setup
```

The setup command installs PHP and JavaScript dependencies, creates `.env`,
generates the application key, runs the migrations, and builds the frontend
assets.

Start the local development services:

```bash
composer run dev
```

The API is available at `http://localhost:8000` by default.

## API

### Create a payment

```http
POST /api/organizations/{organization}/customers/{customer}/payments
Content-Type: application/json
Idempotency-Key: checkout-123
```

Request body:

```json
{
  "amount": 25000,
  "currency": "USD",
  "description": "Website development payment"
}
```

`amount` is an integer expressed in the currency's smallest unit. For example,
`25000` represents USD 250.00.

Example request:

```bash
curl --request POST \
  http://localhost:8000/api/organizations/1/customers/1/payments \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Idempotency-Key: checkout-123' \
  --data '{
    "amount": 25000,
    "currency": "USD",
    "description": "Website development payment"
  }'
```

A new payment returns `201 Created`. Repeating the request with the same
organization and idempotency key returns the original payment with `200 OK`
instead of creating a duplicate.

## Testing

Run the complete test suite from `backend/`:

```bash
composer test
```

Run the formatter:

```bash
./vendor/bin/pint
```

## Project structure

```text
paymentflow/
├── backend/
│   ├── app/
│   │   ├── Exceptions/
│   │   ├── Jobs/
│   │   ├── Models/
│   │   ├── Providers/
│   │   └── Services/Payments/
│   ├── database/
│   ├── routes/api.php
│   └── tests/
└── docs/
    └── architecture.md
```

## Roadmap

- Authentication and authorization policies
- Provider-level idempotency for safe queue retries
- Real payment provider integration
- Signed webhook ingestion and event deduplication
- Redis-backed queues
- Retry classification and dead-letter handling
- Go payment worker
- Observability, structured logging, and metrics
- Expanded API resources
- Dockerized development environment
- AWS deployment

See [docs/architecture.md](docs/architecture.md) for the broader architecture
and design goals.

## License

This repository does not currently specify a license.
