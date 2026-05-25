# XPR Network Telegram Tip Bot (WebAuth)

A lightweight, multi-token, non-custodial Telegram tipping bot for the [XPR Network](https://xprnetwork.org). This repository includes two versions of the bot (**Node.js** and **PHP**), specifically designed to run on shared hosting environments like **sPanel** or **cPanel** without requiring CLI access, complex background processes, or heavy MySQL database configurations.

Instead of holding user private keys on the server, this bot acts as an intent generator using **WebAuth.com Native Transfer URLs**. When a user initiates a tip, the bot generates a secure universal link. Clicking the link opens the user's WebAuth wallet (mobile or desktop) to authorize and sign the transaction securely on-chain.

---

## ✨ Features

* **100% Non-Custodial:** Private keys are never touched, requested, or stored by the server. 
* **Zero Gas Fees:** Leverages XPR Network's feeless transactions.
* **Two Deployment Options:** Choose between **Node.js** (long-polling) or **PHP** (webhooks) based on your hosting capabilities.
* **Zero-Config Database:** Both versions use a local SQLite file (`tipbot.sqlite`) that auto-generates on the first run.
* **Multi-Token Support:** Officially supports `$XPR`, `$UBQT`, `$UBQTX`, `$NDAO`, `$NDAOX`, `$NDAOXPR`, `$MESSAGE`, `$CIPHER`, `$NOTARY`, and `$SMART`.
* **DAO Support:** Includes a built-in `/donate` command to route funds directly to community wallets (`nftitledao` and `ubitquity1`).

---

## 🤖 Bot Commands

Both versions of the bot support the exact same commands:

### 1. Register Account
Links a user's Telegram handle to their XPR Network WebAuth account.
* **Usage:** `/register <myxprnetworkname>`
* **Example:** `/register myxprname`

### 2. Send a Tip
Generates a secure WebAuth link to tip another registered Telegram user in an allowed token.
* **Usage:** `/tip @telegram_user <amount> <TOKEN>`
* **Example:** `/tip @satoshi 50 UBQT`

### 3. Donate to DAOs
Generates a secure WebAuth link to donate directly to configured partner organizations. *(Supported Orgs: `ndao` -> nftitledao, `ubitquity` -> ubitquity1)*
* **Usage:** `/donate <organization> <amount> <TOKEN>`
* **Example:** `/donate ndao 1000 NDAOX`

---

## 🚀 Installation & Setup 

First, generate your bot token:
1. Open Telegram and message [@BotFather](https://t.me/BotFather).
2. Send `/newbot` and follow the prompts to create your bot.
3. Copy the **HTTP API Token** provided by BotFather.

Next, choose **one** of the deployment paths below.

---

### OPTION A: Node.js Deployment (Recommended)
*Best for hosts with the "Setup Node.js App" feature (like sPanel). Uses long-polling, meaning no webhooks or SSL configurations are required.*

1. **Configure:** Open `bot.js` and replace `YOUR_TELEGRAM_BOT_TOKEN` with your token.
2. **Upload:** In your hosting File Manager, create a new folder *outside* of your `public_html` directory (e.g., `~/tipbot/`). Upload `package.json` and `bot.js` to this folder.
3. **Create App:** In your sPanel/cPanel dashboard, click **Setup Node.js App**.
    * **Node.js Version:** 14.x or higher
    * **Application Mode:** Production
    * **Application Root:** The folder you created (e.g., `tipbot`)
    * **Startup File:** `bot.js`
4. **Install & Start:** Click **Create**. Scroll down to Modules/Dependencies and click **Run NPM Install**. Once finished, scroll up and click **Start App**. 

---

### OPTION B: PHP Deployment
*Best for basic shared hosting. Uses Telegram Webhooks and requires an active SSL certificate (HTTPS).*

1. **Configure:** Open `bot.php` and replace `YOUR_TELEGRAM_BOT_TOKEN` with your token.
2. **Upload:** In your hosting File Manager, create a folder inside your `public_html` directory (e.g., `public_html/tipbot/`). Upload `bot.php` here. Set the folder permissions to `0755`.
3. **Secure Database:** In the same folder, create a `.htaccess` file with the following code to protect the SQLite file from public download:
   ```apache
   <Files ~ "\.sqlite$">
       Order allow,deny
       Deny from all
   </Files>
