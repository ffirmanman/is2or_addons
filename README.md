All addons build for is2or
- copy file sync_all_addons.sh to cs cart root directory
- adjust directory at sync_all_addons.sh
- run the sh script to make addons available in cs cart

## GitHub Actions deploy flow

This repo ships addons to CS-Cart servers via a GitHub Actions workflow:
- Push to `develop` deploys to the staging/server defined by the `SSH_*` secrets.
- Push to `main` deploys to production using the `PROD_*` secrets.

The workflow connects via SSH, runs `git fetch/checkout/pull`, and then runs the sync script.

### Required secrets (GitHub Actions)

Staging / develop:
- `SSH_PRIVATE_KEY` — private key for the server user
  - Example:
    ```
    -----BEGIN OPENSSH PRIVATE KEY-----
    (your key here)
    -----END OPENSSH PRIVATE KEY-----
    ```
- `SSH_HOST` — server hostname or IP
  - Example: `staging.example.com` or `203.0.113.10`
- `SSH_USER` — SSH user
  - Example: `deploy`
- `SSH_PORT` — optional, defaults to 22
  - Example: `22`
- `REPO_PATH` — absolute path to this repo on the server
  - Example: `/var/www/addons-cscart`
- `SYNC_SCRIPT_PATH` — absolute path to the sync script to run
  - Example: `/var/www/addons-cscart/sync_all_addons.sh`

Production / main:
- `PROD_SSH_PRIVATE_KEY`
  - Example:
    ```
    -----BEGIN OPENSSH PRIVATE KEY-----
    (your key here)
    -----END OPENSSH PRIVATE KEY-----
    ```
- `PROD_SSH_HOST`
  - Example: `prod.example.com` or `198.51.100.20`
- `PROD_SSH_USER`
  - Example: `deploy`
- `PROD_SSH_PORT` — optional, defaults to 22
  - Example: `22`
- `PROD_REPO_PATH`
  - Example: `/var/www/addons-cscart`
- `PROD_SYNC_SCRIPT_PATH`
  - Example: `/var/www/addons-cscart/sync_all_addons.sh`

## Deployment setup (detailed)

### 1. Configure SSH access (GitHub Actions -> Server)

GitHub Actions connects to your server via SSH using the private key stored in secrets.

1) Generate a deploy key pair (do this on your local machine or a secure admin host):

```bash
ssh-keygen -t ed25519 -C "addons-cscart-deploy"
```

2) Add the **public** key to the server user:

```bash
# on the server, as the deploy user
mkdir -p ~/.ssh
chmod 700 ~/.ssh
echo "ssh-ed25519 AAAA... your_public_key" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

3) Add the **private** key to GitHub Secrets:
- `SSH_PRIVATE_KEY` for staging
- `PROD_SSH_PRIVATE_KEY` for production

Note: The workflow does not handle a passphrase. Use a key without a passphrase, or update the workflow to support it.

### 2. Initial setup on the server

The server must have the repo cloned and the sync script configured.

1) Install dependencies:

```bash
sudo apt-get update
sudo apt-get install -y git
```

2) Clone this repo to the path you set in secrets:

```bash
git clone git@github.com:YOUR_ORG/YOUR_REPO.git /var/www/addons-cscart
cd /var/www/addons-cscart
git checkout develop
```

3) Create the sync script and update paths:

```bash
cp sync_all_addons.sh.bak sync_all_addons.sh
chmod +x sync_all_addons.sh
```

Edit the script and set the correct directories (or use a `.env`, see below):

```bash
BASE_SOURCE_DIR="/var/www/addons-cscart"
TARGET_DIR="/var/www/multivendor"
```

4) Run once to verify:

```bash
./sync_all_addons.sh
```

5) Ensure the deploy user can write to the CS-Cart directory (needed for symlinks).

### 3. Maintaining the script and installation

- When you change the script in this repo, it will be pulled by the workflow on deploy.
- Keep `BASE_SOURCE_DIR` pointing to the repo path and `TARGET_DIR` to your CS-Cart root.
- If you move the repo or CS-Cart path, update the secrets (`REPO_PATH`, `SYNC_SCRIPT_PATH`).
- To troubleshoot, check the GitHub Actions logs and verify that the server user can `git pull`.
- If `git pull` fails, fix the server repo access (deploy key or HTTPS token).

### 4. Permissions and sudo (required for GitHub Actions)

The workflow runs the sync script with `sudo -n`, so the deploy user must be allowed to run it **without a password**.

1) On the server, create a sudoers rule (as root):

```bash
sudo visudo -f /etc/sudoers.d/deployment
```

Add this line (use the exact absolute path to the script on your server):

```
deployment ALL=(root) NOPASSWD: /bin/bash /opt/cs-cart/development/addons-cscart/sync_all_addons.sh.bak
```

2) Test it as the deploy user:

```bash
sudo -u deployment -i
sudo -n /bin/bash /opt/cs-cart/development/addons-cscart/sync_all_addons.sh.bak
```

If this fails, the workflow will also fail.

### 5. .env configuration (recommended)

The script loads a `.env` file from the same directory as the script (fallback: current working directory).
Add these keys to `.env` to override defaults:

```env
BASE_SOURCE_DIR=/opt/cs-cart/development/addons-cscart
TARGET_DIR=/opt/cs-cart/development/multivendor
TARGET_OWNER=www-data
TARGET_GROUP=www-data
TARGET_PERMS=755
```

If `sudo` is used, keep the `.env` file next to the script so it can be found reliably.

### Optional: server -> GitHub access (for git pull)

The workflow runs `git pull` on the server, so the server must be able to authenticate to GitHub.

Option A (recommended): use a **deploy key** for the server repo
1) Generate a new key on the server:

```bash
ssh-keygen -t ed25519 -C "addons-cscart-server"
```

2) Add the public key as a **Deploy key** (read-only) in the GitHub repo settings.
3) Ensure the repo remote is SSH:

```bash
git remote set-url origin git@github.com:YOUR_ORG/YOUR_REPO.git
```

Option B: use HTTPS + token and a credential helper (less secure for shared servers).
