# Bad Credit Auto Loans

WordPress development project for badcreditautoloans.net

The WordPress team manages pages, posts, and Elementor layouts in the SiteGround WordPress CMS. Localhost, VS Code, and AI support content retrieval, design work, writing, and local testing. GitHub versions custom code and structured content exports.

See [PROJECT_OVERVIEW.md](PROJECT_OVERVIEW.md) for this workflow, the project structure, request flow, configuration, and scan findings. Live CMS access and content synchronization have not been established.

## Local homepage

The custom homepage is active at <http://localhost/badcreditautoloans/wordpress/>.

- Theme source: `wp-content/themes/badcreditautoloans-child/`.
- Main layout: `wp-content/themes/badcreditautoloans-child/templates/front-page.html`.
- Running theme copy: `C:\wamp64\www\badcreditautoloans\wordpress\wp-content\themes\badcreditautoloans-child\`.
- Page content: **Pages → Home** in the local WordPress database.
- Portable content snapshot: `content/home.json`.
- Local setup: `php -d extension=mysqli scripts/setup-local-homepage.php` after copying the theme to the WAMP installation.

The homepage includes responsive navigation, a browser-only payment calculator, and native FAQ disclosures. Setup preserves the existing pages and saves previous homepage/theme settings under ignored `.local/`. See the [theme README](wp-content/themes/badcreditautoloans-child/README.md) for editing details and asset provenance.
