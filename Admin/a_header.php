      <header class="header">
        <div class="header-left">
          <button class="menu-toggle icon-btn" id="menuToggle"><i class="fa-solid fa-bars"></i></button>
          <div class="left">
          </div>
        </div>

        <div class="header-right">
          <button class="icon-btn" id="themeToggle" title="Toggle theme"><i class="fa-solid fa-moon" id="themeIcon"></i></button>
          <button class="icon-btn" title="Notifications"><i class="fa-regular fa-bell"></i></button>

          <div class="profile" id="profileBtn">
            <div class="avatar"><?php echo substr($admin_data['first_name'], 0, 1) . substr($admin_data['last_name'], 0, 1); ?></div>
            <div class="header-profile-name"><span><?php echo $full_name; ?> <i class="fa fa-angle-down" aria-hidden="true"></i></span></div>
            <div class="dropdown" id="profileDropdown">
              <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
              <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </div>
          </div>
        </div>
      </header>