<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    if (file_exists('includes/system_config.php')) {
        include 'includes/system_config.php';
        echo generateFaviconTags($conn);
    }
    ?>
  <meta charset="UTF-8">
  <title>ADLOR - Student Access</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f0f2f5;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .container {
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      width: 90%;
      max-width: 400px;
      overflow: hidden;
    }

    .tabs {
      display: flex;
      background: #3498db;
    }

    .tab {
      flex: 1;
      padding: 15px;
      color: white;
      text-align: center;
      cursor: pointer;
      font-weight: bold;
    }

    .tab.active {
      background: #2980b9;
    }

    .form-container {
      padding: 20px;
    }

    form {
      display: none;
    }

    form.active {
      display: block;
    }

    input {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
    }

    input[type="submit"] {
      background: #3498db;
      color: white;
      border: none;
      cursor: pointer;
    }

    input[type="submit"]:hover {
      background: #2980b9;
    }

    label {
      font-weight: bold;
    }
  </style>
</head>
<body>

<div class="container">
  <div class="tabs">
    <div class="tab active" onclick="switchTab('register')">Register</div>
    <div class="tab" onclick="switchTab('login')">Login</div>
  </div>

  <div class="form-container">
    <!-- Registration Form -->
    <form id="register-form" class="active" method="POST" action="student_register.php" enctype="multipart/form-data">
      <label>Full Name</label>
      <input type="text" name="full_name" required>

      <label>Student ID</label>
      <input type="text" name="school_id" required>

      <label>Course</label>
      <input type="text" name="course" required>

      <label>Section</label>
      <input type="text" name="section" required>

      <label>Password</label>
      <input type="password" name="password" required>

      <label>Confirm Password</label>
      <input type="password" name="confirm_password" required>

      <label>Upload ID Photo</label>
      <input type="file" name="photo" accept="image/*" required>

      <input type="submit" value="Register">
    </form>

    <!-- Login Form -->
    <form id="login-form" method="POST" action="student_login.php">
      <label>Student ID</label>
      <input type="text" name="school_id" required>

      <label>Password</label>
      <input type="password" name="password" required>

      <input type="submit" value="Login">
    </form>
  </div>
</div>

<script>
  function switchTab(tab) {
    const tabs = document.querySelectorAll(".tab");
    const forms = document.querySelectorAll("form");

    tabs.forEach(t => t.classList.remove("active"));
    forms.forEach(f => f.classList.remove("active"));

    if (tab === "register") {
      document.querySelector("#register-form").classList.add("active");
      tabs[0].classList.add("active");
    } else {
      document.querySelector("#login-form").classList.add("active");
      tabs[1].classList.add("active");
    }
  }

  // ✅ Auto-switch if redirected from registration
  window.onload = function () {
    const params = new URLSearchParams(window.location.search);
    if (params.get("registered") === "success") {
      switchTab('login');
    }
  };
</script>



<!-- ADLOR Animation System -->
<script src="assets/js/adlor-animations.js"></script>

</body>
</html>
