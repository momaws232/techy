</div><!-- end .container my-4 (opened in header.php) -->
    
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>About Us</h5>
                    <p>Your comprehensive tech forum for discussions, news, and product information.</p>
                </div>
                <div class="col-md-3">
                    <h5>Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="forums.php" class="text-white">Forums</a></li>
                        <li><a href="news.php" class="text-white">News</a></li>
                        <li><a href="products.php" class="text-white">Products</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contact</h5>
                    <ul class="list-unstyled">
                        <li><a href="contact.php" class="text-white">Contact Us</a></li>
                        <li><a href="privacy.php" class="text-white">Privacy Policy</a></li>
                        <li><a href="terms.php" class="text-white">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-12 text-center">
                    <p>&copy; <?= date('Y') ?> <?= htmlspecialchars(get_site_title($conn)) ?>. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script src="js/animations.js"></script>
    <script src="js/posts-realtime.js"></script>
    <script src="js/notifications.js"></script>
    <script src="js/attachment-handler.js"></script>
    
    <style>
    .post-footer {
        padding-top: 10px;
        border-top: 1px solid #eee;
    }
    
    .like-button {
        transition: all 0.3s ease;
    }
    
    .like-button:hover {
        transform: scale(1.05);
    }
    
    #new-posts-notification {
        animation: fadeIn 0.5s;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        z-index: 100;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    </style>
</body>
</html>
