# Nitro K-9 Contest Plugin

A WordPress plugin that adds the "Art of the Leash" Clip Contest entry form to any page via a shortcode: `[nitro_k9_contest]`. It handles entry validation, uploads video clips directly to a Dropbox folder, emails a notification to Nitro K-9 on every submission, stores every entry for winner selection, and displays the full Official Rules for both the Clip Contest and the Referral Program Drawing.

## 1. Install on SiteGround / WordPress

1. Zip the `nitro-k9-contest` folder (the folder itself, so `nitro-k9-contest.php` sits at the top level of the zip).
2. In WP Admin: **Plugins → Add New → Upload Plugin**, choose the zip, install, then **Activate**.
   (Alternatively, upload the unzipped folder to `wp-content/plugins/` via SiteGround Site Tools → File Manager or SFTP.)
3. Activating the plugin automatically creates the `wp_nk9_contest_entries` database table.

## 2. Add the logo and confirm fonts

- Drop your Nitro K-9 logo file into `assets/images/` and name it `logo.png` (or update the path in `includes/templates/contest-page.php`, the `$logo_url` line, if you'd rather reference the WordPress Media Library).
- Headings use **Bebas Neue** loaded from Google Fonts, since "Beana's Neue" isn't a font name I could find on file — Bebas Neue is a very close match stylistically (bold, condensed, all-caps) and is free to use. **If Nitro K-9 has a specific licensed font by a different exact name**, send me the font files (`.woff2`) and I'll swap the `@font-face` / enqueue in `includes/class-nk9-plugin.php` and `assets/css/contest.css` (`--nk9-font-heading`).
- Body font is **Inter**, also loaded from Google Fonts.

## 3. Create the contest page

Create a new WordPress Page (e.g. "Art of the Leash Clip Contest") and add a **Shortcode** block (or Custom HTML block) containing:

```
[nitro_k9_contest]
```

That's the entire page — hero, how-to-enter, prizes, the entry form, the referral program summary, and the full Official Rules accordion are all rendered by the shortcode.

## 4. Configure settings

Go to **Contest Entries → Settings** in the WP admin sidebar:

- **Notification email** — defaults to `remorrison93@gmail.com`. Every entry submission sends here.
- **Entry period start/end** — defaults to Jul 24, 2026 – Aug 19, 2026 11:59 PM Pacific, matching the Official Rules. Entries outside this window are rejected automatically.
- **Max entries per email + dog name** — defaults to 3, matching the entry limit. (Note: this can only key off email + dog name, since there's no reliable way to detect "household" online. Sponsor should still manually review entries before finalizing winners, as the rules already reserve that right.)
- **Max video upload size (MB)** — defaults to 500MB. Also see the PHP upload limits note below.
- **Instagram handle / hashtag** — shown in the instructions on the page.
- **Telegram group URL** — optional, shown as a button in the Referral Program section.
- **Dropbox App key / secret / refresh token / folder path** — see the walkthrough below.

## 5. Set up Dropbox (one-time)

Video uploads go straight to a Dropbox folder you control. Dropbox's API requires an "App" with a **refresh token** (so the connection doesn't expire every few hours). Here's how to generate one:

1. **Create the folder** in your own Dropbox account, e.g. `/Nitro K9 Contest Entries`.
2. Go to **[dropbox.com/developers/apps](https://www.dropbox.com/developers/apps)** → **Create app**.
   - Choose **Scoped access**.
   - Choose **App folder** access (recommended — the app can only see the one folder it creates, not your whole Dropbox) or **Full Dropbox** if you'd rather point it at a folder you already made.
   - Name it something like `NitroK9ContestUploads`.
3. Open the new app, go to the **Permissions** tab, check:
   - `files.content.write`
   - `files.content.read`
   - `sharing.write`
   Click **Submit**.
4. Go to the **Settings** tab and copy the **App key** and **App secret** — paste both into the plugin's Settings page.
5. Generate a refresh token (one-time, from your own computer, not the server):
   - Visit this URL in your browser, replacing `APP_KEY` with your App key:
     ```
     https://www.dropbox.com/oauth2/authorize?client_id=APP_KEY&token_access_type=offline&response_type=code
     ```
   - Log in / approve access. Dropbox will show you an **authorization code** — copy it.
   - Run this command (Terminal on Mac, or any machine with `curl` — replace the placeholders):
     ```
     curl https://api.dropboxapi.com/oauth2/token \
       -d code=AUTHORIZATION_CODE \
       -d grant_type=authorization_code \
       -d client_id=APP_KEY \
       -d client_secret=APP_SECRET
     ```
   - The JSON response includes a `"refresh_token"` value — paste that into the plugin's Settings page.
6. Set the **Dropbox upload folder path** in Settings to match the folder from step 1 (e.g. `/Nitro K9 Contest Entries`). If you chose "App folder" access in step 2, Dropbox already scopes everything to a single app folder, so this can just be `/`.

Once all four Dropbox fields are filled in and saved, video uploads will start working. You can test it by submitting a real entry through the page.

## 6. Make sure notification emails actually arrive

Shared hosting (including SiteGround) frequently has WordPress's built-in `wp_mail()` land in spam or get silently dropped, because it doesn't authenticate as coming from your domain. Strongly recommended: install the free **WP Mail SMTP** plugin and connect it to a real mailbox (Google Workspace, SiteGround email, etc.) so notification emails reliably reach `remorrison93@gmail.com`.

## 7. Raise upload limits for video entries

Shared hosting often caps file uploads well below what a phone video needs. In **SiteGround Site Tools → Devs → PHP Manager**, increase:
- `upload_max_filesize` (e.g. 512M)
- `post_max_size` (should be a bit larger than `upload_max_filesize`, e.g. 550M)
- `max_execution_time` (e.g. 300)
- `memory_limit` (e.g. 256M)

If you'd rather not touch server limits, entrants can always use the Instagram submission option instead, which doesn't upload anything to your server.

## 8. Reviewing entries and picking winners

Go to **Contest Entries** in the WP admin sidebar to see every submission (name, dog, email, method, a link to the Instagram post or the Dropbox video, and submission time). Use **Export all entries (CSV)** to download the full list for the Grand Prize judging and the Second Place random drawing.

## What's intentionally not automated

- **Grand Prize winner selection** is a subjective, merit-based call per the Official Rules — the plugin doesn't pick this for you, it just gives you a clean list to judge from.
- **Second Place random drawing** — pick using the exported CSV and any impartial random-selection method (e.g. a random row picker); the plugin records bonus-entry eligibility isn't automated for reposts, since that depends on Sponsor's own social media activity, not the entry form.
- **Client-status verification and household de-duplication** — flagged in the rules as Sponsor's right to verify; the form only technically limits by email + dog name.
