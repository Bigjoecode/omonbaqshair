# Deployment — GitHub → Live Server (cPanel)

This repo auto-deploys to the live server on every push to `main` via
`.github/workflows/deploy.yml` (it SSHes into the server and runs `git reset --hard origin/main`).

`config/config.php` is **gitignored** — it lives only on the server, so deploys never
touch your live DB credentials, SMTP password, or payment keys. Server-uploaded images
(in `assets/uploads/`) are untracked and are preserved on every deploy.

---

## ⚡ Quick start — PRIVATE repo, in-place (recommended, no downtime)

This adopts git **inside your existing live folder**, so `config/config.php` and all
uploaded images are kept (git only overwrites tracked code files).

```bash
# --- 0. SSH into the server (cPanel → Terminal) ---
cd /home/davidwil/omonblaqshair.com

# --- 1. Safety backup ---
cd ~ && tar -czf omonblaqshair-backup-$(date +%F).tgz omonblaqshair.com && cd omonblaqshair.com

# --- 2. Create a server deploy key and register it with GitHub ---
ssh-keygen -t ed25519 -C "omonblaq-deploy" -f ~/.ssh/omonblaq_deploy -N ""
cat ~/.ssh/omonblaq_deploy.pub
#   ^ copy this whole line → GitHub → repo → Settings → Deploy keys → Add deploy key
#     (title: "live server"; leave "Allow write access" UNCHECKED — read-only is enough)

# --- 3. Tell git to use that key for github.com ---
printf 'Host github.com\n  HostName github.com\n  User git\n  IdentityFile ~/.ssh/omonblaq_deploy\n  IdentitiesOnly yes\n' >> ~/.ssh/config
chmod 600 ~/.ssh/config ~/.ssh/omonblaq_deploy
ssh -T git@github.com   # type "yes" to trust the host; "successfully authenticated" = good

# --- 4. Adopt the repo in-place and sync to it ---
git init
git remote add origin git@github.com:Bigjoecode/omonbaqshair.git
git fetch origin
git reset --hard origin/main          # overwrites tracked CODE only; keeps config.php + uploads
git branch --set-upstream-to=origin/main main

# --- 5. Make sure config + folders are right ---
[ -f config/config.php ] || cp config/config.sample.php config/config.php   # then edit it
mkdir -p assets/uploads/{products,pages,categories} storage
chmod -R 755 assets/uploads storage
```

After this, finish **section B** (add the 5 GitHub Actions secrets) and pushes to `main`
will auto-deploy. To deploy manually any time: `cd /home/davidwil/omonblaqshair.com && git pull`.

> `git reset --hard origin/main` makes the repo the source of truth — any code edited
> *only* on the live server (not committed) is replaced. From now on, change code via git.
> `config/config.php` is gitignored and untracked → never touched. Your server-uploaded
> images are untracked → preserved.

---

## A. (Alternative) One-time server setup — fresh clone

SSH into the server (cPanel → *Terminal*, or an SSH client):

```bash
# 1. Go ABOVE the web root and clone (replace path with your actual web dir)
cd ~                         # /home/davidwil
# Back up the current site first!
cp -r omonblaqshair.com omonblaqshair.com.bak

# 2. Clone the repo INTO the web directory.
#    Public repo  -> HTTPS works with no auth:
git clone https://github.com/Bigjoecode/omonbaqshair.git omonblaqshair.com
#    Private repo -> add a GitHub *deploy key* first (see section C), then:
#    git clone git@github.com:Bigjoecode/omonbaqshair.git omonblaqshair.com

cd omonblaqshair.com

# 3. Create the live config from the template and fill in real values
cp config/config.sample.php config/config.php
nano config/config.php       # set APP_ENV=production, DB_*, BASE_URL, SMTP_PASS, Stripe/Paystack keys

# 4. Restore your uploaded images + logs from the backup (if the clone replaced them)
cp -rn ../omonblaqshair.com.bak/assets/uploads/* assets/uploads/ 2>/dev/null || true

# 5. Permissions for uploads + storage
mkdir -p assets/uploads/products assets/uploads/pages assets/uploads/categories storage
chmod -R 755 assets/uploads storage

# 6. First-time DB import + seed (skip if the DB already exists)
#    mysql -u DBUSER -p DBNAME < sql/schema.sql
#    ADMIN_PASSWORD='YourStrongPass' php scripts/seed.php
```

> If your web root **is** `public_html`, clone into a sibling folder and point the
> domain's document root at it, or clone directly into `public_html`.

---

## B. Add GitHub Actions secrets

Repo → **Settings → Secrets and variables → Actions → New repository secret**:

| Secret             | Value (example)                              |
|--------------------|----------------------------------------------|
| `SSH_HOST`         | `omonblaqshair.com` or the server IP         |
| `SSH_USER`         | `davidwil`                                   |
| `SSH_PORT`         | `22` (cPanel sometimes uses a custom port)   |
| `DEPLOY_PATH`      | `/home/davidwil/omonblaqshair.com`           |
| `SSH_PRIVATE_KEY`  | the **private** key (full contents, see C)   |
| `SSH_PASSPHRASE`   | the private key passphrase, if the key has one |

---

## C. SSH keys

**Runner → server** (lets GitHub Actions log into the server):

```bash
# On your computer (or in cPanel Terminal):
ssh-keygen -t ed25519 -C "github-actions" -f deploy_key -N ""
#   deploy_key      -> paste into the GitHub secret SSH_PRIVATE_KEY (private)
#   deploy_key.pub  -> add on the server:  cPanel → SSH Access → Manage Keys → Import,
#                      then **Authorize** it (or append to ~/.ssh/authorized_keys)
```

**Server → GitHub** (only needed if the repo is *private*, so the server can pull):

```bash
# On the server:
ssh-keygen -t ed25519 -C "server-deploy" -f ~/.ssh/github_deploy -N ""
cat ~/.ssh/github_deploy.pub
#   Add that public key in GitHub → repo → Settings → Deploy keys → Add deploy key
# Then tell git to use it (or add a host alias in ~/.ssh/config).
```

After this, every `git push` to `main` redeploys automatically. You can also trigger it
manually: **Actions → Deploy to live server → Run workflow**.

---

## D. Alternative — cPanel's built-in Git™ Version Control (no Actions)

If you'd rather not use GitHub Actions:

1. cPanel → **Git™ Version Control → Create**.
2. Clone URL: `https://github.com/Bigjoecode/omonbaqshair.git`, repository path =
   your web directory.
3. The included **`.cpanel.yml`** copies the files into place on each pull.
4. Pull updates from cPanel (or automate with a GitHub webhook → cPanel "deploy").

---

## E. After every deploy
- Hard-refresh (Ctrl/Cmd + Shift + R) — bump asset `?v=` if you changed CSS/JS.
- `config/config.php`, uploaded images, and logs are never overwritten.
