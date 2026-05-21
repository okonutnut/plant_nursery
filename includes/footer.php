    </div>
    </div>
    <?php
    // Determine Bootstrap JS path
    $bootstrapJsPath = 'assets/dist/js/bootstrap.bundle.min.js';
    $scriptPath = $_SERVER['PHP_SELF'];
    if (strpos($scriptPath, '/customer/') !== false || strpos($scriptPath, '/supplier/') !== false || 
        strpos($scriptPath, '/plantcategory/') !== false || strpos($scriptPath, '/planttype/') !== false || 
        strpos($scriptPath, '/plant/') !== false || strpos($scriptPath, '/employee/') !== false || 
        strpos($scriptPath, '/order/') !== false || strpos($scriptPath, '/refund/') !== false) {
        $bootstrapJsPath = '../' . $bootstrapJsPath;
    }
    ?>
    <!-- Bootstrap 5 JS -->
    <script src="<?php echo $bootstrapJsPath; ?>"></script>
</body>
</html>

