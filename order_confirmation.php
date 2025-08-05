<?php
$order_id = $_GET['order_id'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: #f4f8fb; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh;
            margin: 0;
            padding: 1rem;
        }
        .confirmation { 
            background: #fff; 
            border-radius: 16px; 
            box-shadow: 0 4px 18px rgba(44,62,80,0.08); 
            padding: 3rem 2.5rem; 
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        .confirmation h1 { 
            color: #2e7d32; 
            margin-bottom: 1rem;
            font-size: 2rem;
        }
        .confirmation p { 
            color: #333; 
            font-size: 1.15rem;
            margin-bottom: 0.5rem;
        }
        .confirmation .order-id { 
            color: #388e3c; 
            font-weight: bold; 
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        .confirmation a { 
            display: inline-block; 
            margin-top: 2rem; 
            background: #2e7d32; 
            color: #fff; 
            padding: 0.8rem 2rem; 
            border-radius: 8px; 
            text-decoration: none; 
            font-weight: 600;
            transition: background 0.3s ease;
        }
        .confirmation a:hover { 
            background: #1b5e20; 
        }
        .checkmark {
            width: 90px;
            height: 90px;
            display: block;
            margin: 0 auto 1.5rem auto;
        }
        @keyframes stroke {
            100% { stroke-dashoffset: 0; }
        }
        @keyframes scale {
            0%, 100% { transform: none; }
            50% { transform: scale(1.08); }
        }
        .checkmark__circle {
            stroke: #43a047;
            stroke-width: 6;
            fill: none;
            stroke-dasharray: 285;
            stroke-dashoffset: 285;
            animation: stroke 0.6s cubic-bezier(0.65,0,0.45,1) forwards;
        }
        .checkmark__check {
            stroke: #43a047;
            stroke-width: 6;
            stroke-linecap: round;
            stroke-linejoin: round;
            fill: none;
            stroke-dasharray: 50;
            stroke-dashoffset: 50;
            animation: stroke 0.4s 0.6s cubic-bezier(0.65,0,0.45,1) forwards, scale 0.3s 1s cubic-bezier(0.65,0,0.45,1) forwards;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
                align-items: center;
                justify-content: center;
            }
            
            .confirmation {
                padding: 2rem 1.5rem;
                border-radius: 12px;
                margin: 0 auto;
            }
            
            .confirmation h1 {
                font-size: 1.8rem;
                margin-bottom: 0.8rem;
            }
            
            .confirmation p {
                font-size: 1.1rem;
                margin-bottom: 0.4rem;
            }
            
            .confirmation .order-id {
                font-size: 1.1rem;
                margin-bottom: 0.8rem;
            }
            
            .confirmation a {
                margin-top: 1.5rem;
                padding: 0.7rem 1.8rem;
                font-size: 1rem;
            }
            
            .checkmark {
                width: 80px;
                height: 80px;
                margin-bottom: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 0.8rem;
                align-items: center;
                justify-content: center;
            }
            
            .confirmation {
                padding: 1.5rem 1rem;
                border-radius: 10px;
            }
            
            .confirmation h1 {
                font-size: 1.6rem;
                margin-bottom: 0.6rem;
            }
            
            .confirmation p {
                font-size: 1rem;
                margin-bottom: 0.3rem;
            }
            
            .confirmation .order-id {
                font-size: 1rem;
                margin-bottom: 0.6rem;
            }
            
            .confirmation a {
                margin-top: 1.2rem;
                padding: 0.6rem 1.5rem;
                font-size: 0.95rem;
                width: 100%;
                max-width: 200px;
            }
            
            .checkmark {
                width: 70px;
                height: 70px;
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 375px) {
            body {
                padding: 0.5rem;
                align-items: center;
                justify-content: center;
            }
            
            .confirmation {
                padding: 1.2rem 0.8rem;
                border-radius: 8px;
            }
            
            .confirmation h1 {
                font-size: 1.4rem;
                margin-bottom: 0.5rem;
            }
            
            .confirmation p {
                font-size: 0.9rem;
                margin-bottom: 0.3rem;
            }
            
            .confirmation .order-id {
                font-size: 0.9rem;
                margin-bottom: 0.5rem;
            }
            
            .confirmation a {
                margin-top: 1rem;
                padding: 0.5rem 1.2rem;
                font-size: 0.9rem;
                width: 100%;
                max-width: 180px;
            }
            
            .checkmark {
                width: 60px;
                height: 60px;
                margin-bottom: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="confirmation">
        <svg class="checkmark" viewBox="0 0 52 52">
            <circle class="checkmark__circle" cx="26" cy="26" r="23"/>
            <path class="checkmark__check" d="M16 27l8 8 12-14"/>
        </svg>
        <h1>Thank You for Your Order!</h1>
        <p>Your order has been placed successfully.</p>
        <?php if ($order_id): ?>
            <p class="order-id">Order ID: <?= htmlspecialchars($order_id) ?></p>
        <?php endif; ?>
        <a href="my_orders.php">View My Orders</a>
    </div>
    
    <script>
        // Ensure cart is cleared for COD orders
        function clearCart() {
            localStorage.removeItem("cartItems_guest");
            localStorage.removeItem("cartItems");
            var userId = localStorage.getItem("currentUserId");
            if (userId) {
                localStorage.removeItem("cartItems_" + userId);
            }
            localStorage.setItem("orderJustPlaced", "1");
        }
        
        // Clear cart immediately when page loads
        clearCart();
        
        // Prevent browser back button from showing cart items
        window.history.pushState(null, null, window.location.href);
        window.onpopstate = function() {
            window.history.pushState(null, null, window.location.href);
        };
    </script>
</body>
</html> 