# telegram_tip_bot_xpr_network
Telegram Tip Bot for XPR Network. Defaults to UBITQUITY &amp; nDAO utility tokens.


# XPR Network Telegram Tip Bot (WebAuth)

A lightweight, non-custodial Telegram tipping bot for the [XPR Network](https://xprnetwork.org). Built in PHP and SQLite, this bot is specifically designed to run on shared hosting environments like **sPanel** or **cPanel** without requiring CLI access, continuous Node.js/Go processes, or MySQL database configurations.

Instead of holding user private keys on the server, this bot utilizes **WebAuth.com Native Transfer URLs**. When a user initiates a tip, the bot generates a secure universal link. Clicking the link opens the user's WebAuth wallet (mobile or desktop) to authorize and sign the transaction on-chain.

---

## ✨ Features

* **100% Non-Custodial:** Private keys are never touched or stored by the server. 
* **Zero Gas Fees:** Leverages XPR Network's feeless transactions.
* **Serverless / Webhook-Driven:** Runs only when pinged by Telegram, consuming zero idle server resources.
* **Zero-Config Database:** Uses a local SQLite file (`tipbot.sqlite`) that auto-generates on the first run.
* **DAO Support:** Includes a built-in `/donate` command to route funds directly to community wallets (e.g., `nftitledao`).

---

## 📋 Prerequisites

* **Hosting:** sPanel, cPanel, or any standard Apache/LiteSpeed web host.
* **PHP:** Version 7.4 or higher.
* **Extensions:** `PDO` and `pdo_sqlite` (enabled by default on almost all shared hosts).
* **SSL Certificate:** Your domain must have an active SSL certificate (HTTPS) for Telegram Webhooks to function.

---

## 🚀 Installation & Setup

### 1. Get a Telegram Bot Token
1. Open Telegram and message [@BotFather](https://t.me/BotFather).
2. Send `/newbot` and follow the prompts to create your bot.
3. Copy the **HTTP API Token** provided by BotFather.

### 2. Configure the Bot
1. Open `bot.php` in your text editor.
2. Replace `YOUR_TELEGRAM_BOT_TOKEN` on line 5 with your actual BotFather token.
3. *(Optional)* Change the `$daoWallet` variable on line 6 if you want the `/donate` command to go to a different XPR account.

### 3. Upload to sPanel/cPanel
1. Log into your hosting control panel and open the **File Manager**.
2. Navigate to your `public_html` directory and create a new folder for the bot (e.g., `public_html/tipbot/`).
3. Upload `bot.php` into this folder.
4. Set the folder permissions to `0755` so SQLite can generate its journal files.

### 4. Secure the Database
To prevent the public from downloading your SQLite database file, create a file named `.htaccess` in the same directory as `bot.php` and add the following lines:

```apache
<Files ~ "\.sqlite$">
    Order allow,deny
    Deny from all
</Files>
