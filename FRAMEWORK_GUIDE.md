# Dreambox Nexus: The Plain-English User Guide

Welcome to **Dreambox Nexus** (also known as **Nexus Core**). 

This guide is written in plain, friendly English. You do not need to be a computer scientist to understand how to use this framework, how it protects you, or how to manage your website or web application with it.

---

## The Big Picture: What is Nexus Core?

Think of Nexus Core like a **custom-built, bulletproof house**:
* **The Foundation & Walls:** Everything is built from scratch without any fragile third-party spare parts. It cannot be easily broken or hijacked.
* **The Security Guards:** Automatic bodyguards stand at every door checking badges, inspecting packages for dangerous items, and keeping trespassers out.
* **The Cockpit / Dashboard:** A high-tech glass dashboard that shows you everything happening inside the house in real time—who is visiting, how the electricity is running, and what chores are being completed.
* **The Conveyor Belt:** A background system that does heavy chores (like sending hundreds of emails or resizing photos) quietly in the basement so visitors to your website never experience lag or slowdowns.

---

## Table of Contents
1. [Starting Up & Accessing the System](#1-starting-up--accessing-the-system)
2. [The Cockpit: Your Full-Feature Monitor Dashboard](#2-the-cockpit-your-full-feature-monitor-dashboard)
3. [Guiding Visitors: How Web Addresses (Routes) Work](#3-guiding-visitors-how-web-addresses-routes-work)
4. [Receiving Information: Forms, Checks & Safety](#4-receiving-information-forms-checks--safety)
5. [Sending Answers Back: The Standard Delivery Box](#5-sending-answers-back-the-standard-delivery-box)
6. [Saving & Finding Information: The Database](#6-saving--finding-information-the-database)
7. [Logins, Passwords & Secret Keys](#7-logins-passwords--secret-keys)
8. [The Conveyor Belt: Doing Heavy Chores in the Background](#8-the-conveyor-belt-doing-heavy-chores-in-the-background)
9. [The Automatic Calendar: Running Chores on a Schedule](#9-the-automatic-calendar-running-chores-on-a-schedule)
10. [The File Locker: Safe Uploads & Downloads](#10-the-file-locker-safe-uploads--downloads)
11. [Sending Emails Without Getting Blocked](#11-sending-emails-without-getting-blocked)
12. [Your Handy Assistant: Terminal Commands Made Simple](#12-your-handy-assistant-terminal-commands-made-simple)
13. [Meeting the Big Four: PCI, HIPAA, NIST & GDPR Compliance in Plain English](#13-meeting-the-big-four-pci-hipaa-nist--gdpr-compliance-in-plain-english)
14. [The Secure Courier: Calling External APIs Without Risk](#14-the-secure-courier-calling-external-apis-without-risk)
15. [Speaking Multiple Languages (Localization & Translations)](#15-speaking-multiple-languages-localization--translations)
16. [Instant Scaffolding & One-Click Compliance Audits](#16-instant-scaffolding--one-click-compliance-audits)

---

## 1. Starting Up & Accessing the System

Everything runs locally on your computer inside a secure workspace.

* **Your Public Front Door:** Open any web browser and type `http://localhost`. This shows the clean, secure Application Gateway.
* **The Restricted Monitoring Cockpit:** Open `http://localhost/nexus/monitor`. This is your administrative control center. It is strictly locked down so outside visitors can never see your private server stats or security logs.
* **Where Your Files Live:** On your computer's drive at `D:\Projects\Dreambox Nexus` (or `/mnt/d/Projects/Dreambox Nexus` in your system terminal).
* **The Front Door Guard:** Whenever someone visits your website, they enter through `public/index.php`. That file automatically wakes up the engine, checks security, and sends them to the right room.

---

## 2. The Cockpit: Your Full-Feature Monitor Dashboard

When you open `http://localhost/nexus/monitor`, you enter the **Cyber-Noir Monitoring Cockpit**. This is your private administrative control room. 

> [!IMPORTANT]
> **Why is the Cockpit Private?**  
> In a security-first framework, you never let the public see your house blueprints or your security guard logs. The dashboard lives behind a strict security checkpoint (`MonitorAccessMiddleware`). It only allows access to your local machine (`127.0.0.1`) or logged-in administrators. Anyone else attempting to view it receives a firm **403 Forbidden** rejection.

Here is what each dial on the dashboard tells you:

| Cockpit Panel | What It Monitors (In Plain English) | Why You Care |
| :--- | :--- | :--- |
| **1. Engine Vitals** | Shows how much computer memory and CPU power the website is using. | Tells you if your server is running fast, smooth, and relaxed, or if it's getting tired. |
| **2. Security Shield** | Shows any bad actors who tried to break in, blocked hackers, and rate limits. | If someone tries guessing passwords 50 times in a row, you'll see them get blocked here. |
| **3. Traffic Radar** | Counts how many people are visiting per second and how fast pages load. | You can see at a glance if your website is busy and whether pages open in the blink of an eye. |
| **4. Database Garage** | Shows all the records stored in your database and how fast questions are answered. | Tells you if database searches are lightning fast or if any query took longer than expected. |
| **5. Conveyor Belt (Queue)** | Shows background jobs currently waiting, running, or finished. | If an email fails to send, it shows up here with a red light and a simple **"Retry"** button. |
| **6. The Clock (Scheduler)** | Shows automatic tasks on a calendar (e.g., daily cleanups at midnight). | You can see exactly when the next cleanup will run and what happened during the last one. |
| **7. The Memory Vault (Cache)** | Shows frequently used data saved in super-fast memory. | Gives your website a speed boost. Includes a one-click button to **"Clear Memory"** anytime. |
| **8. The Black Box (Audit Trail)** | A diary that records every important change made to the website. | If a record was modified, this tells you *who* changed it, *what* it looked like before, and *when*. |
| **9. The Road Map (Routes)** | A clean directory of all pages and addresses available on your website. | You never have to guess what web addresses exist; they are all listed right here. |

---

## 3. Guiding Visitors: How Web Addresses (Routes) Work

When a user visits a link like `http://localhost/about` or `http://localhost/products`, the **Router** acts like a helpful receptionist.

* You tell the framework where to send people in a simple file called `config/routes.php`.
* You can think of it like this:
  * *"When someone asks for `/profile`, check their ID badge first, then show them their account."*
  * *"When someone clicks `/contact`, show them the contact form."*
* If someone types an address that doesn't exist, the router politely hands them a standard **"Page Not Found"** package without crashing or looking broken.

---

## 4. Receiving Information: Forms, Checks & Safety

Whenever a visitor sends information to your website (like filling out a signup form or typing a search):

1. **The Request Glove:** The framework receives the visitor's package inside a special protective wrapper (`Request`). 
2. **The Inspector (Sanitizer):** It scrubs off any nasty tricks, like invisible hacker scripts hidden inside their name or comment.
3. **The Rule Checker (Validator):** Before accepting the data, it checks your rules:
   * Is the email actually an email?
   * Is the password long enough?
   * Did they leave a required field empty?
4. **Polite Feedback:** If anything is wrong, the framework automatically sends back a clear, friendly checklist of what they need to fix (for example: *"Please enter a valid email address"*).

---

## 5. Sending Answers Back: The Standard Delivery Box

Whenever your website responds to a mobile app, browser, or visitor, it wraps the answer in a uniform **Delivery Box** (JSON Envelope).

Think of it like an official company parcel that always looks identical on the outside:
* **The Parcel Contents (`data`):** The exact information requested (like a user's name or product list).
* **The Stamp & Barcode (`meta`):** Includes a unique tracking number (`trace_id`) and the exact time it was sent. If anything ever goes wrong, that tracking number lets you find the exact second and log entry where the issue occurred.
* **The Friendly Message (`message`):** A human-readable note, such as *"Profile updated successfully!"* or *"Password was incorrect."*

Because every single response uses the same clean delivery box, building frontends, mobile apps, or connecting with other computers is effortless.

---

## 6. Saving & Finding Information: The Database

Saving information to a database used to be risky because attackers could trick the system using a trick called "SQL Injection". 

Nexus Core completely eliminates this danger:
* **The Translation Shield (Query Builder):** You never have to write raw, dangerous database queries. You just write simple instructions like: *"Find all users who are active, sorted by the newest first."*
* **Automatic Safeguards (Prepared Statements):** The database treats user data like pure text, never executable code. It is impossible for an attacker's input to hijack your database commands.
* **Accidental Deletion Protection (Soft Deletes):** If you delete a customer or document, the system doesn't immediately obliterate it. It marks it with a "trash" tag so you can restore it if it was deleted by mistake.
* **Automatic Change Tracker (Migrations):** When you want to add a new column to a table (like adding a phone number field), the system keeps track of the change in a versioned history log so your team never breaks the database.

---

## 7. Logins, Passwords & Secret Keys

Security is built into every layer of user accounts:

* **Unbreakable Passwords (`Argon2id`):** Passwords are never saved in plain text. They are scrambled using one of the world's most advanced military-grade hashing algorithms (`Argon2id`). Even if someone stole the database file, they could not read the passwords.
* **Smart Upgrading:** As computers get faster over the years, the system automatically strengthens old password scrambles whenever a user logs in.
* **Two Front Doors (Web & API):**
  * **Web Browsers:** Protected by secure, tamper-proof session cookies that lock out impersonators.
  * **Mobile Apps & APIs:** Protected by personal access keys that are stored safely as scrambled hashes in the database.
* **The Bouncer (Gates & Permissions):** Every time a user clicks "Edit" or "Delete", the system checks their permissions: *"Does this person actually own this document?"* This prevents sneaky visitors from changing the number in the web address to look at another person's private files.

---

## 8. The Conveyor Belt: Doing Heavy Chores in the Background

Imagine you run a pizza shop. If the cashier had to stop taking orders, walk into the kitchen, bake the pizza, and deliver it to the customer's house before greeting the next person in line, the line would stretch down the block!

That's why Nexus Core has **The Background Conveyor Belt (Queue)**:
* When a visitor clicks **"Sign Up"**, your website immediately creates their account, says *"Welcome!"*, and hands the chore of sending the welcome email to the conveyor belt in the background.
* The visitor never waits even an extra split-second.
* In the basement, a worker assistant (`php bin/nexus queue:work`) quietly picks up the email chore and sends it.
* **What if the internet drops?** The assistant doesn't give up. It tries again a few moments later. If it fails repeatedly, it places the job in a special **"Needs Attention"** box on your Dashboard so you can see why and click **"Retry"** with one tap.

---

## 9. The Automatic Calendar: Running Chores on a Schedule

Instead of setting up messy alarm clocks across different computers, Nexus Core has an **Automatic Calendar (The Scheduler)**:

* You write down your recurring chores directly in your project code:
  * *"Clean out expired temporary files every night at midnight."*
  * *"Archive old activity logs once a week on Sunday."*
  * *"Check the health of our background workers every 10 minutes."*
* The framework automatically runs those chores on time, logs whether they succeeded, and displays a clean timeline on your **Monitor Dashboard**.

---

## 10. The File Locker: Safe Uploads & Downloads

Letting users upload profile pictures, PDFs, or spreadsheets is one of the most common ways hackers try to sneak viruses onto a server. 

Nexus Core guards file uploads like an airport security checkpoint:
* **X-Ray Inspection:** It doesn't trust the file name or file extension. It opens the inside of the file and inspects the binary bytes to confirm: *"Is this really a picture, or is it a disguised virus?"*
* **Name Randomization:** It renames every file to a completely random scrambled code (a UUID) so bad actors cannot overwrite important system files.
* **Private Vault Storage:** Uploaded files are placed in a locked vault outside the public web folder. Nobody can directly trigger or execute them. When a user wants to view their file, the framework securely fetches it and hands it to them.

---

## 11. Sending Emails Without Getting Blocked

Sending emails from a website can be tricky. Hackers often try to hijack email forms to send spam to thousands of strangers.

* **Anti-Hijacking Shield (CRLF Protection):** Nexus Core inspects every email address and subject line, stripping out hidden trick characters that spammers use to turn your website into a spam bot.
* **Queued Delivery:** Outgoing emails are automatically routed through the background conveyor belt so page loads remain lightning fast.

---

## 12. Your Handy Assistant: Terminal Commands Made Simple

Whenever you need to perform maintenance, you have a command-line assistant called `nexus`. 

You simply open your terminal and run these straightforward commands:

| Command | What It Does (In Plain English) |
| :--- | :--- |
| `php bin/nexus migrate` | Sets up or updates your database tables automatically. |
| `php bin/nexus migrate:rollback` | Undoes the last database change if you changed your mind. |
| `php bin/nexus db:seed` | Populates your database with initial data (admin users, default roles). |
| `php bin/nexus compliance:verify` | Runs an automated 10-point audit verifying PCI, HIPAA, NIST & GDPR rules. |
| `php bin/nexus make:model <Name>` | Generates a new database model with mass-assignment safety. |
| `php bin/nexus make:controller <Name>` | Generates a new web controller with standard JSON response envelopes. |
| `php bin/nexus make:migration <Name>` | Creates a timestamped database migration file. |
| `php bin/nexus routes:list` | Prints a neat map of all web pages and addresses in your project. |
| `php bin/nexus queue:work` | Starts the background assistant to process chores and send emails. |
| `php bin/nexus schedule:run` | Checks the calendar and runs any chores scheduled for right now. |
| `php bin/nexus cache:clear` | Empties the temporary memory and cache to give the system a fresh start. |

---

## 13. Meeting the Big Four: PCI, HIPAA, NIST & GDPR Compliance in Plain English

If your website handles **payments** (credit cards), **healthcare data** (medical records), **European user privacy** (GDPR), or needs to meet **government-grade security standards**, you usually have to spend months configuring compliance tools. 

Nexus Core is engineered so that your software architecture satisfies these rules right out of the box.

### A. What is PCI-DSS? (Credit Card & Payment Security)
* **The Rule:** If you touch or store credit card details, you must protect them with extreme care.
* **How Nexus Core Complies:**
  * **Card Number Masking:** The framework automatically masks credit cards so only the first 6 and last 4 digits are ever visible (`4111 11** **** 1111`). Even if an administrator views the system, the full number is never displayed.
  * **No Forbidden Data:** Security codes (CVV) and PIN numbers are strictly blocked from ever touching database storage.
  * **Five-Strike Lockout:** If an attacker or bot tries guessing a user's password 5 times in a row, the account is automatically locked for 30 minutes.
  * **Military Encryption at Rest:** Cardholder records are scrambled using `AES-256-GCM` with cryptographic key rotation.

### B. What is HIPAA? (Medical & Patient Privacy)
* **The Rule:** Any patient health information (ePHI) must be strictly confidential, accessible only by authorized doctors or staff, and logged whenever viewed.
* **How Nexus Core Complies:**
  * **15-Minute Inactivity Auto-Logoff:** If a clinic receptionist or doctor walks away from their desk for 15 minutes, the system automatically logs them out to prevent unauthorized eyes from seeing patient records.
  * **Tamper-Evident Black Box (Audit Trail):** Every time patient data is viewed, changed, or exported, an immutable audit entry is written.
  * **Cryptographic Hash Chain:** Each log entry is mathematically linked to the previous one (like a blockchain). If someone hacks the database and tries to delete or alter a record to hide their tracks, the mathematical chain breaks, and your Monitor Cockpit lights up with a red alert.

### C. What is NIST CSF 2.0? (National Cybersecurity Standard)
* **The Rule:** A comprehensive framework created by the US government to Govern, Protect, Detect, Respond, and Recover from cyber attacks.
* **How Nexus Core Complies:**
  * **Two-Factor Authentication (MFA / TOTP):** Native support for 6-digit authenticator apps (Google Authenticator, YubiKey, 1Password) with single-use backup recovery codes.
  * **Automatic Anomaly Radar:** The system continuously monitors for suspicious behavior (like someone trying to export 10,000 patient records in 5 seconds) and trips an automatic circuit breaker to halt the request.
  * **Disaster Recovery & Clean Purging:** Automated calendar tools securely archive old compliance logs after the mandated retention period (e.g. 1 year for PCI, 6 years for HIPAA) and cryptographically shred expired sensitive records.

### D. What is GDPR? (European Union Privacy & Data Rights)
* **The Rule:** Any user from Europe has strict legal rights over their personal information: they must explicitly consent to data collection, they can download all their data at any time, and they can demand to be completely erased.
* **How Nexus Core Complies:**
  * **The "Right to be Forgotten" (Digital Shredder):** When a user asks to delete their account, the framework triggers an automatic digital shredder (`GdprManager.php`). It permanently removes their personal details across all tables or scrambles their identifiers with irreversible salts so old tax/financial records remain valid without identifying the person.
  * **The "Right to Download Your Data" (Portability):** With one click, the system bundles every piece of information belonging to that user (profile, orders, comments, preferences) into a neat, machine-readable JSON download box.
  * **No Sneaky Cookies (Granular Consent):** The framework strictly blocks any tracking or non-essential cookies until the visitor explicitly clicks "Accept". It logs the exact second, IP address, and wording version the visitor agreed to.
  * **The Privacy Dashboard:** The Monitor Cockpit includes a dedicated GDPR HUD displaying all pending data requests and user consent stats in real time.

---

## 14. The Secure Courier: Calling External APIs Without Risk

When your website needs to talk to other services on the internet (like charging a credit card with Stripe, sending text messages with Twilio, or checking Google logins):

* **The Danger (SSRF):** In other frameworks, developers often accidentally let clever hackers trick their server into calling internal private company addresses or cloud metadata servers to steal master keys.
* **The Secure Courier (`HttpClient.php`):** Nexus Core includes an armored courier. Whenever your code asks it to make an external web request, it automatically checks the destination address:
  * If the address is trying to contact a private local network (`192.168.x.x`, `10.x.x.x`, `127.0.0.1`) or cloud metadata endpoints (`169.254.169.254`), the courier slams the door shut and aborts the request.
  * It forces encrypted transmission (TLS 1.2 or TLS 1.3) and prevents server freezing by enforcing strict timeout limits.

---

## 15. Speaking Multiple Languages (Localization & Translations)

Because privacy laws like GDPR cover European countries speaking over 24 different languages, your website must be able to communicate with visitors in their native tongue:

* **Language Dictionaries (`lang/`):** Clean text files (like `lang/en/validation.php` or `lang/es/validation.php`) store your sentences and labels.
* **Automatic Language Detection:** Nexus Core detects the visitor's preferred language from their browser settings and displays error messages, notices, and buttons in their language automatically.
* **Fallback Safety:** If a specific phrase isn't translated into French or Spanish yet, the system seamlessly defaults to English so the visitor never sees a blank box or broken code.

---

## 16. Instant Scaffolding & One-Click Compliance Audits

### A. Robot Assistant for Building Features
Instead of writing boilerplate files by hand, you can use built-in code generators:
* `php bin/nexus make:model Customer` creates a secure database model with built-in GDPR shredding hooks and mass-assignment protection.
* `php bin/nexus make:controller CheckoutController` creates a web controller with standard delivery boxes already set up.
* `php bin/nexus make:migration add_avatar_to_users` creates a database update file ready to run.

### B. The 10-Point One-Click Compliance Audit
Before you show your website to a compliance auditor or launch a payment portal, run:
```bash
php bin/nexus compliance:verify
```
In 2 seconds, it checks your entire system:
1. Verifies that session folders are locked with private permissions (`0700`).
2. Checks that the cryptographic audit log chain has not been tampered with.
3. Tests credit card masking so full card numbers are never stored.
4. Confirms database connections use secure UTF-8 formatting.
5. Verifies military-grade Argon2id password encryption is active.
6. Confirms that automatic 15-minute logoff and 5-strike lockouts are armed and ready.

---

## Summary

With **Dreambox Nexus**, you have:
1. **Zero External Headaches:** No bloated third-party code that breaks when someone updates a library.
2. **Invisible Bodyguards:** Automatic defense against hackers, scrapers, and data thieves.
3. **The 360° Cockpit:** Complete visibility over your server, speed, traffic, database, and background tasks in real time.
4. **Rock-Solid Speed:** Everything is built to be feather-light, lightning-fast, and easy to maintain.

