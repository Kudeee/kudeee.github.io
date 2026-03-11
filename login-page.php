<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/login-page.css" />
    <title>Login</title>
  </head>
  <body>
    <div class="container">
      <div class="login-container">
        <div class="logo">
          <a href="index.php">
            <img src="assests/logo/society-fit.png" alt="society-fit logo" />
          </a>
        </div>

        <h1>Welcome Back!</h1>

        <!-- POST to PHP login handler -->
        <form id="loginForm" method="POST" action="/api/auth/login.php">

          <label for="email">Email</label>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="Enter your Email"
            autocomplete="email"
            required
          />

          <label for="password">Password</label>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter Password"
            autocomplete="current-password"
            required
          />

          <div class="forgot-password">
            <a href="forgot-password.php">Forgot Password?</a>
          </div>

          <div class="form-btn">
            <button class="button" type="submit">Log In</button>
          </div>

          <div class="dont-have-acc">
            Don't have an account? <a href="sign-up-page.php">Sign up here</a>
          </div>
        </form>
      </div>

      <div class="image">
        <div class="carousel-track">
          <div class="slide">
            <img src="assests/images/ca2.jpg" alt="" />
            <div class="slide-text">
              <h2>Welcome Back</h2>
              <p>Log in to continue your fitness journey</p>
            </div>
          </div>

          <div class="slide">
            <img src="assests/images/car1.jpg" alt="" />
            <div class="slide-text">
              <h2>Back for More?</h2>
              <p>Let's keep pushing your limits</p>
            </div>
          </div>

          <div class="slide">
            <img src="assests/images/car3.jpg" alt="" />
            <div class="slide-text">
              <h2>Stay Consistent</h2>
              <p>Results Will Follow</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div id="loading"></div>
    <div id="pop-up"></div>

    <script src="components/loading.js"></script>
    <script type="module" src="js/login.js"></script>
    <script type="module" src="components/pop-up.js"></script>
    <script src="js/carousel.js"></script>
  </body>
</html>