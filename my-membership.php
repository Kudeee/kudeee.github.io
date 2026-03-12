<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/my-membership.css" />
    <title>My Membership</title>
  </head>
  <body>
    <header class="header">
      <a href="homepage.php">
        <img src="assests/logo/society-fit.png" alt="society-fit logo" />
      </a>
    </header>

    <div class="container">
      <h1>Membership Plan</h1>

      <div class="card-container">
        <!-- Subscription cards are injected by subcriptionCards.js -->
      </div>
    </div>

    <script type="module">
      /**
       * Fetch the logged-in member's actual plan and billing cycle first,
       * then expose them as the globals that subcriptionCards.js reads
       * (window.userCurrentPlan / window.userCurrentBilling) before the
       * module is imported, so the correct card gets the "Current Plan"
       * button on first render.
       */
      async function initMembershipPage() {
        try {
          const res  = await fetch('api/user/membership/info.php');
          const data = await res.json();

          if (!data.success) {
            // Session expired or not logged in — redirect to login
            window.location.href = 'login-page.php';
            return;
          }

          const member = data.member;
          const sub    = data.subscription;

          // Prefer the active subscription's billing_cycle; fall back to the
          // value stored on the member row itself.
          window.userCurrentPlan    = member.plan                              || 'BASIC PLAN';
          window.userCurrentBilling = sub?.billing_cycle ?? member.billing_cycle ?? 'monthly';

        } catch (err) {
          console.error('Could not load membership info:', err);
          // Keep safe defaults so the page still renders
          window.userCurrentPlan    = window.userCurrentPlan    || 'BASIC PLAN';
          window.userCurrentBilling = window.userCurrentBilling || 'monthly';
        }

        // Import the cards module *after* the globals are set so the initial
        // render already knows which card is the current plan.
        await import('./components/subcriptionCards.js');
      }

      initMembershipPage();
    </script>
  </body>
</html>