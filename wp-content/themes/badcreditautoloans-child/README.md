# Bad Credit Auto Loans child theme

Local homepage for the existing Twenty Twenty-Five installation. The design uses an off-white canvas, forest green, lime accents, locally served Manrope and Literata fonts, and an original coastal car image.

## Files and editing

- `templates/front-page.html`: homepage shell, header, database page content, and footer.
- `parts/header.html` and `parts/footer.html`: shared template parts.
- `patterns/homepage.php`: starter page content, mostly native WordPress blocks, plus the calculator and hero image composition as HTML blocks.
- `patterns/site-header.php` and `patterns/site-footer.php`: navigation and footer markup with installation-aware links.
- `style.css`: child-theme metadata and responsive styling.
- `theme.json`: theme colors, layout, and editor settings.
- `functions.php`: stylesheet/script loading and homepage description.
- `assets/js/homepage.js`: mobile navigation and browser-only payment calculations.
- `assets/images/coastal-drive.webp`: locally stored generated hero image.

The setup script inserts the starter content into the **Home page in the WordPress database**. After installation, edit its text under Pages → Home. Re-running setup preserves subsequent database content edits; editing the starter pattern alone does not overwrite an existing page.

The calculator uses a standard fixed-rate amortization calculation, with a separate zero-interest calculation. It does not send, store, or collect personal data and is not connected to an application or lender service. Default numbers are illustrative. Native FAQ details remain usable without JavaScript.

## Local installation

From `C:\badcreditautoloans\wordpress`, copy the child-theme directory into:

`C:\wamp64\www\badcreditautoloans\wordpress\wp-content\themes\badcreditautoloans-child`

Then run:

```powershell
php -d extension=mysqli scripts/setup-local-homepage.php
```

The command checks for a localhost site, saves previous theme/homepage settings in ignored `.local/homepage-before.json`, activates the child theme, creates the Home page if needed, selects it as the static homepage, and exports its content to `content/home.json`. The export uses `{{theme_url}}` for its image URL; the installation script generates a fresh page from the PHP pattern, so it resolves the current theme path automatically. Existing pages are preserved.

The parent theme must remain installed. Font files are served from its bundled Manrope and Literata directories; attribution and font-license details are in the parent's `readme.txt`.

This setup changes only the local WAMP site. Production deployment and Elementor integration are separate work.

## Asset provenance and references

`assets/images/coastal-drive.webp` was generated with the built-in image generation tool and converted from its PNG output to WebP for the website. It is illustrative imagery, not a vehicle listing. Exact generation prompt:

> Use case: photorealistic-natural. Asset type: homepage hero photograph for Bad Credit Auto Loans. Create a premium editorial automotive photograph, landscape 3:2 composition. A clean, unbranded pearl-silver compact family crossover SUV in front three-quarter view, parked on a quiet paved coastal overlook. The vehicle faces toward the left of frame, fully visible and occupying the central lower two thirds. Coastal grass, a low dark-green headland and calm pale ocean in the background, soft hazy overcast daylight and a warm off-white sky. Authentic accessible everyday vehicle, refined natural colors, realistic tires, glass and reflections, slightly low camera angle. Calm and optimistic, like a thoughtful independent automotive magazine. Crisp focus on the car, subtle film grain. No people, no other cars, no text, no logos, no watermark, no badges, no visible license-plate characters. Photograph only, no UI elements or mockup.

References consulted: [WordPress child themes](https://developer.wordpress.org/themes/advanced-topics/child-themes/) and [CFPB auto loan resources](https://www.consumerfinance.gov/consumer-tools/auto-loans/).
