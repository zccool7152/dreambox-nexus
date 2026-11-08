# Dreambox Nexus // Core v7.0

> **Zero-Dependency, Security-First PHP Framework Engineered for High-Integrity & Regulatory-Compliant Workloads.**

Dreambox Nexus is a 100% custom, ground-up PHP framework written with zero external third-party Composer packages. Designed for defense-in-depth, cryptographic auditing, and regulatory compliance (PCI-DSS v4.0, HIPAA Security Rule, NIST CSF 2.0, and EU GDPR).

---

## 🛡️ Core Highlights

* **Zero External Composer Dependencies:** Strict native PHP 8.2+ engine eliminating third-party supply-chain vulnerabilities.
* **Dual Datastore Architecture:** Native support for both relational databases (MariaDB / MySQL / SQLite with PDO) and NoSQL document stores (**MongoDB v7.0** via `MongoDB\Driver\Manager`).
* **Interactive Cyber-Noir Observability HUD:** Real-time system telemetry cockpit at `/nexus/monitor` gated behind loopback IP allowlists and administrative RBAC.
* **Cryptographic Tamper-Evident Audit Trail:** SHA-256 hash-chained compliance audit log ensuring non-repudiation and immutable transaction records.
* **Military-Grade Cryptography:** AES-256-GCM AEAD authenticated encryption, Argon2id password hashing, and dynamic CSP Nonce lifecycle injection.
* **Built-in Resilience Subsystems:** Sliding-window rate limiting, 5-strike account lockouts, SSRF-blocked outbound HTTP client, transactional migration runner, and CLI tooling via `php bin/nexus`.

---

## 🚀 Quick Start

### 1. Environment Configuration
Create your local `.env` configuration file in the project root:

```env
APP_NAME="Dreambox Nexus"
APP_ENV=development
APP_KEY=base64:your_32_byte_base64_encryption_key
APP_DEBUG=true
APP_URL=http://localhost
```

### 2. Database Connection
Dreambox Nexus supports MySQL, SQLite, and MongoDB. Configure your preferred connection in `.env`:

```env
# MongoDB NoSQL Datastore
DB_CONNECTION=mongodb
MONGO_HOST=127.0.0.1
MONGO_PORT=27017
MONGO_DATABASE=dreambox_nexus
MONGO_USERNAME=nexus_app
MONGO_PASSWORD=your_secure_password
MONGO_AUTH_SOURCE=dreambox_nexus
```

### 3. Run Automated Tests
Execute the native 42-check test suite:

```bash
php tests/run_tests.php
```

---

## 📖 Documentation & Architecture

* **[User Manual & Plain-English Guide](FRAMEWORK_GUIDE.md):** Complete guide explaining every subsystem in plain English.
* **[Execution Blueprint & DAG Specification](BLUEPRINT.md):** Comprehensive engineering architecture and regulatory compliance matrices.
* **[OpenAPI 3.0 Contract](docs/openapi.yaml):** Formal API contract specifying all endpoints and uniform `{data, meta, message}` JSON envelopes.

---

## ⚖️ Regulatory Compliance
* **PCI-DSS v4.0:** 5-strike account lockouts, 15-minute inactivity timeouts, pan/token masking.
* **HIPAA Security Rule:** AES-256-GCM encrypted data at rest and in transit, strict access control.
* **NIST CSF 2.0:** Cryptographic hash chains, real-time anomaly detection radar.
* **EU GDPR:** Native consent ledger (Art. 7), right to be forgotten data shredder (Art. 17), data portability bundles (Art. 20).

---

## License
Proprietary & Confidential // Engineered by Timothy Dunn
