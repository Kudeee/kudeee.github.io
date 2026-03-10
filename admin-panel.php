<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/admin-css.css" />
    <title>Admin</title>
  </head>
  <body>
    <div class="sidebar">
      <h2>Admin Panel</h2>
      <nav class="nav">
        <a class="active" data-page="dashboard">Dashboard</a>
        <a data-page="members">Members</a>
        <a data-page="subscriptions">Subscriptions</a>
        <a data-page="payments">Payments</a>
        <a data-page="classes">Classes</a>
        <a data-page="trainers">Trainers</a>
        <a data-page="events">Events</a>
        <a data-page="revenue">Revenue</a>
        <a data-page="roles">Roles</a>
      </nav>
    </div>

    <div class="main" id="content"></div>
  </body>

  <script src="js/admin-js.js"></script>
</html>
