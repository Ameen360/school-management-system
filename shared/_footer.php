<footer>
  <div class="container">
    <div class="row">

      <!-- Column 1: Logo & Address -->
      <div class="col-md-4">
        <img src="images/company-logo.jpg" alt="Website Logo">
        <p>SCHOOL MANAGEMENT SYSTEM</p>
        <p>Q9P3+75H, My Town, My City, My Country</p>
      </div>

      <!-- Column 2: Timezone -->
      <div class="col-md-4">
        <?php
        date_default_timezone_set('Africa/Lagos');
        $current_time = date('D M d Y H:i:s \G\M\TO (T)');
        ?>
        <p>Time Zone: <?php echo $current_time; ?></p>
      </div>
      <div class="col-md-4">
        <div class="footer-links">

          <p>follow us on</p>
        </div>
        <div class="social-icons">
          <a href="#"><i class="fab fa-facebook-f facebook"></i></a>
          <a href="#"><i class="fa-brands fa-x-twitter twitter"></i></a>
          <a href="#"><i class="fab fa-instagram instagram"></i></a>
          <a href="#"><i class="fab fa-linkedin-in linked-in"></i></a>
        </div>
      </div>
    </div>
    <div class="row mt-4">
      <div class="col-md-12">
        <p>&copy; <?php echo date('Y'); ?> By <a href="https://github.com/Ameen360/school-management-system" target="_blank">RayTech</a>. All rights reserved.</p>
      </div>
    </div>
  </div>
</footer>



<script src="https://kit.fontawesome.com/a81368914c.js"></script>
<script src="js/bootstrap.bundle.js"></script>
<script src="./shared/app.js"></script>
</body>

</html>