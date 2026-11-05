<?php
/**
 * Title: A fresh start — homepage
 * Slug: bc-auto/homepage
 * Categories: featured
 * Block Types: core/post-content
 * Description: Homepage content with a working payment calculator and native WordPress text blocks.
 */
?>
<!-- wp:group {"tagName":"section","className":"bc-hero bc-shell","layout":{"type":"default"}} -->
<section class="wp-block-group bc-hero bc-shell">
  <!-- wp:group {"className":"bc-hero-copy","layout":{"type":"default"}} -->
  <div class="wp-block-group bc-hero-copy">
    <!-- wp:paragraph {"className":"bc-eyebrow"} --><p class="bc-eyebrow"><span class="bc-status-dot" aria-hidden="true"></span> YOUR NEXT CHAPTER STARTS HERE</p><!-- /wp:paragraph -->
    <!-- wp:heading {"level":1,"className":"bc-hero-title"} --><h1 class="wp-block-heading bc-hero-title">A fresh start.<br><em>A better drive.</em></h1><!-- /wp:heading -->
    <!-- wp:paragraph {"className":"bc-hero-intro"} --><p class="bc-hero-intro">Your credit history is only part of your story. Get clear on your budget, understand the basics, and move toward your next car with a little more confidence.</p><!-- /wp:paragraph -->
    <!-- wp:buttons {"className":"bc-hero-actions"} -->
    <div class="wp-block-buttons bc-hero-actions"><!-- wp:button {"className":"bc-button bc-button-lime"} --><div class="wp-block-button bc-button bc-button-lime"><a class="wp-block-button__link wp-element-button" href="#calculator">Find your starting point <span aria-hidden="true">↗</span></a></div><!-- /wp:button --></div>
    <!-- /wp:buttons -->
    <!-- wp:paragraph {"className":"bc-quiet-note"} --><p class="bc-quiet-note">No application. No personal details. Just a place to start.</p><!-- /wp:paragraph -->
    <!-- wp:html --><div class="bc-hero-footnote"><span class="bc-mini-road" aria-hidden="true">↗</span><p>A clearer picture.<br><strong>A step in your direction.</strong></p></div><!-- /wp:html -->
  </div>
  <!-- /wp:group -->
  <!-- wp:html -->
  <div class="bc-hero-visual">
    <div class="bc-photo-label"><span aria-hidden="true">↗</span> THE ROAD AHEAD LOOKS DIFFERENT</div>
    <figure class="bc-hero-photo"><img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/coastal-drive.webp' ); ?>" alt="A silver crossover parked at a quiet coastal overlook" width="1536" height="1024" fetchpriority="high" decoding="async"><figcaption><span>A fresh perspective.</span><span>Room for what comes next.</span></figcaption></figure>
    <div class="bc-photo-sticker"><span class="bc-sticker-icon" aria-hidden="true">↗</span><span>A little planning.<br><strong>A lot of possibility.</strong></span></div>
    <span class="bc-visual-index" aria-hidden="true">01 / YOUR ROAD FORWARD</span>
  </div>
  <!-- /wp:html -->
</section>
<!-- /wp:group -->

<!-- wp:html -->
<div class="bc-principles bc-shell" aria-label="Our approach"><span><span aria-hidden="true">↗</span> Clarity before commitment</span><span><span aria-hidden="true">◎</span> Your budget comes first</span><span><span aria-hidden="true">＋</span> Progress at your own pace</span></div>
<!-- /wp:html -->

<!-- wp:group {"tagName":"section","anchor":"how-it-works","className":"bc-process bc-shell bc-section","layout":{"type":"default"}} -->
<section id="how-it-works" class="wp-block-group bc-process bc-shell bc-section">
  <!-- wp:group {"className":"bc-section-heading","layout":{"type":"default"}} -->
  <div class="wp-block-group bc-section-heading">
    <!-- wp:group {"layout":{"type":"default"}} --><div class="wp-block-group"><!-- wp:paragraph {"className":"bc-eyebrow"} --><p class="bc-eyebrow">A LITTLE CLARITY. A REAL NEXT STEP.</p><!-- /wp:paragraph --><!-- wp:heading {"className":"bc-section-title"} --><h2 class="wp-block-heading bc-section-title">Less guesswork.<br>More forward.</h2><!-- /wp:heading --></div><!-- /wp:group -->
    <!-- wp:paragraph {"className":"bc-section-intro"} --><p class="bc-section-intro">You don’t need to have everything figured out today. Start with the basics, one manageable step at a time.</p><!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->
  <!-- wp:columns {"className":"bc-steps"} -->
  <div class="wp-block-columns bc-steps">
    <!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph {"className":"bc-step-number"} --><p class="bc-step-number">01 <span aria-hidden="true">↗</span></p><!-- /wp:paragraph --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Find your comfort zone.</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Try a payment estimate. Leave room in your budget for insurance, maintenance, and the everyday things that matter.</p><!-- /wp:paragraph --></div><!-- /wp:column -->
    <!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph {"className":"bc-step-number"} --><p class="bc-step-number">02 <span aria-hidden="true">↗</span></p><!-- /wp:paragraph --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Look at the whole picture.</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Compare the interest rate, loan length, fees, and total cost. A monthly payment is just one part of the decision.</p><!-- /wp:paragraph --></div><!-- /wp:column -->
    <!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph {"className":"bc-step-number"} --><p class="bc-step-number">03 <span aria-hidden="true">↗</span></p><!-- /wp:paragraph --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Take your next step.</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Bring your questions when you speak with a lender. Understand the terms before deciding what works for you.</p><!-- /wp:paragraph --></div><!-- /wp:column -->
  </div>
  <!-- /wp:columns -->
</section>
<!-- /wp:group -->

<!-- wp:html -->
<section id="calculator" class="bc-calculator-section">
  <div class="bc-shell bc-calculator-layout">
    <div class="bc-calculator-copy">
      <p class="bc-eyebrow">LET’S PUT A NUMBER TO IT</p>
      <h2 class="bc-section-title">A payment you<br>can plan around.</h2>
      <p>A little “what if” can go a long way. Adjust the numbers to see how a different amount, rate, or loan length changes the picture.</p>
      <div class="bc-calculator-aside"><span aria-hidden="true">↗</span><p>Your estimate stays right here.<br><strong>No sign-up. No credit check.</strong></p></div>
      <a class="bc-text-link bc-text-link-light" href="https://www.consumerfinance.gov/consumer-tools/auto-loans/">Explore the CFPB’s auto loan guide <span aria-hidden="true">↗</span></a>
    </div>
    <form class="bc-calculator" data-bc-calculator aria-label="Illustrative car payment calculator">
      <div class="bc-calculator-topline"><span>YOUR PAYMENT, AT A GLANCE</span><span aria-hidden="true">↗</span></div>
      <div class="bc-amount-heading"><label for="bc-loan-amount">Amount financed</label><output id="bc-amount-display" for="bc-loan-amount">$20,000</output></div>
      <input class="bc-range" id="bc-loan-amount" name="amount" type="range" min="3000" max="60000" step="500" value="20000" aria-describedby="bc-amount-help">
      <div class="bc-range-labels" id="bc-amount-help"><span>$3,000</span><span>$60,000</span></div>
      <div class="bc-calculator-fields">
        <div><label for="bc-interest-rate">Annual interest rate</label><div class="bc-number-field"><input id="bc-interest-rate" name="interest" type="number" min="0" max="36" step="0.1" value="10" inputmode="decimal" required><span aria-hidden="true">%</span></div></div>
        <div><label for="bc-loan-term">Loan length</label><select id="bc-loan-term" name="term"><option value="36">36 months</option><option value="48">48 months</option><option value="60" selected>60 months</option><option value="72">72 months</option></select></div>
      </div>
      <p class="bc-calculator-error" data-bc-calculator-error hidden>Please enter an interest rate between 0% and 36%.</p>
      <div class="bc-calculator-result" aria-live="polite" aria-atomic="true">
        <span>Estimated monthly payment</span>
        <div><output id="bc-monthly-payment">$425</output><span class="bc-month-suffix">/ month</span></div>
        <div class="bc-repayment-details"><span>Total interest <strong id="bc-total-interest">$5,496</strong></span><span>Total repaid <strong id="bc-total-payment">$25,496</strong></span></div>
      </div>
      <p class="bc-calculator-disclaimer">Illustration only, using a fixed annual interest rate. Excludes taxes, fees, and insurance. The example rate is not a quoted offer. Actual payments and terms may differ.</p>
      <noscript><p class="bc-noscript">The example above is for $20,000 at 10% over 60 months. Enable JavaScript to change the estimate.</p></noscript>
    </form>
  </div>
</section>
<!-- /wp:html -->

<!-- wp:group {"tagName":"section","anchor":"faqs","className":"bc-faq bc-shell bc-section","layout":{"type":"default"}} -->
<section id="faqs" class="wp-block-group bc-faq bc-shell bc-section">
  <!-- wp:group {"className":"bc-faq-intro","layout":{"type":"default"}} --><div class="wp-block-group bc-faq-intro"><!-- wp:paragraph {"className":"bc-eyebrow"} --><p class="bc-eyebrow">GOOD QUESTIONS. CLEAR ANSWERS.</p><!-- /wp:paragraph --><!-- wp:heading {"className":"bc-section-title"} --><h2 class="wp-block-heading bc-section-title">A few things<br>you might<br><em>be wondering.</em></h2><!-- /wp:heading --><!-- wp:paragraph --><p>Knowing a little more can make the next step feel a little easier.</p><!-- /wp:paragraph --></div><!-- /wp:group -->
  <!-- wp:group {"className":"bc-faq-list","layout":{"type":"default"}} --><div class="wp-block-group bc-faq-list">
    <!-- wp:details {"className":"bc-faq-item"} --><details class="wp-block-details bc-faq-item"><summary>Will using the calculator affect my credit?</summary><!-- wp:paragraph --><p>No. The calculator uses only the numbers you enter and runs in your browser. It does not request a credit report, collect personal details, or submit a loan application.</p><!-- /wp:paragraph --></details><!-- /wp:details -->
    <!-- wp:details {"className":"bc-faq-item"} --><details class="wp-block-details bc-faq-item"><summary>Does an estimate mean I’m approved?</summary><!-- wp:paragraph --><p>No. This is a planning estimate, not a credit decision or loan offer. Any approval, interest rate, or loan terms would be determined by a lender after reviewing an application.</p><!-- /wp:paragraph --></details><!-- /wp:details -->
    <!-- wp:details {"className":"bc-faq-item"} --><details class="wp-block-details bc-faq-item"><summary>What is included in the monthly estimate?</summary><!-- wp:paragraph --><p>The estimate includes principal and interest using a fixed rate and equal monthly payments. It does not include taxes, lender or dealer fees, insurance, or maintenance.</p><!-- /wp:paragraph --></details><!-- /wp:details -->
    <!-- wp:details {"className":"bc-faq-item"} --><details class="wp-block-details bc-faq-item"><summary>What should I compare when shopping for a loan?</summary><!-- wp:paragraph --><p>Look at the rate, fees, repayment period, and total amount repaid alongside the monthly payment. The CFPB’s auto loan resources are a useful place to prepare questions for lenders.</p><!-- /wp:paragraph --></details><!-- /wp:details -->
    <!-- wp:details {"className":"bc-faq-item"} --><details class="wp-block-details bc-faq-item"><summary>Can I apply for financing here?</summary><!-- wp:paragraph --><p>This page currently offers educational information and a payment calculator. It does not accept financing applications or connect you to a lender.</p><!-- /wp:paragraph --></details><!-- /wp:details -->
  </div><!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"bc-final-cta bc-shell","layout":{"type":"default"}} -->
<section class="wp-block-group bc-final-cta bc-shell">
  <!-- wp:group {"layout":{"type":"default"}} --><div class="wp-block-group"><!-- wp:paragraph {"className":"bc-eyebrow"} --><p class="bc-eyebrow">ONE SMALL STEP. A NEW DIRECTION.</p><!-- /wp:paragraph --><!-- wp:heading {"className":"bc-section-title"} --><h2 class="wp-block-heading bc-section-title">Your next chapter.<br>Your own pace.</h2><!-- /wp:heading --></div><!-- /wp:group -->
  <!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button {"className":"bc-button bc-button-dark"} --><div class="wp-block-button bc-button bc-button-dark"><a class="wp-block-button__link wp-element-button" href="#calculator">Start with your budget <span aria-hidden="true">↗</span></a></div><!-- /wp:button --></div><!-- /wp:buttons -->
</section>
<!-- /wp:group -->
