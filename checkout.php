<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$userId]);
$addresses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Select Delivery Address - Fresh & Healthy</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    :root {
        --primary-color: #2e7d32;
        --primary-light: #66bb6a;
        --primary-dark: #1b5e20;
        --accent-color: #ffa726;
        --accent-dark: #f57c00;
        --text-primary: #2c3e50;
        --text-secondary: #546e7a;
        --background: linear-gradient(135deg, #f8faf9 0%, #e8f5e9 100%);
        --card-background: #ffffff;
        --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.06);
        --shadow-md: 0 4px 20px rgba(0, 0, 0, 0.08);
        --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.12);
        --transition-base: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        --border-radius: 12px;
        --border-radius-lg: 16px;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        background: var(--background);
        color: var(--text-primary);
        line-height: 1.6;
        padding: 2rem;
        min-height: 100vh;
    }

    .checkout-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 3rem;
        background: rgba(255, 255, 255, 0.95);
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    /* Responsive Design for Checkout */
    @media (max-width: 1024px) {
        .checkout-container {
            max-width: 95%;
            padding: 2rem;
        }
        
        h2 {
            font-size: 2.4rem;
        }
        
        .address-grid {
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
    }

    @media (max-width: 768px) {
        body {
            padding: 1rem;
        }
        
        .checkout-container {
            padding: 1.5rem;
            border-radius: 12px;
        }
        
        h2 {
            font-size: 2rem;
        }
        
        .page-subtitle {
            font-size: 1rem;
        }
        
        .address-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .address-card {
            padding: 1.2rem;
        }
        
        .address-header {
            flex-direction: column;
            gap: 0.8rem;
            align-items: flex-start;
        }
        
        .address-name {
            font-size: 1.1rem;
        }
        
        .address-phone {
            font-size: 0.9rem;
        }
        
        .address-details {
            font-size: 0.95rem;
        }
        
        .select-btn {
            padding: 0.8rem 1.5rem;
            font-size: 0.9rem;
        }
        
        .add-new-btn {
            padding: 1rem 1.5rem;
            font-size: 1rem;
        }
    }

    @media (max-width: 480px) {
        body {
            padding: 0.5rem;
        }
        
        .checkout-container {
            padding: 1rem;
            border-radius: 8px;
        }
        
        h2 {
            font-size: 1.8rem;
        }
        
        .page-subtitle {
            font-size: 0.9rem;
        }
        
        .address-card {
            padding: 1rem;
        }
        
        .address-name {
            font-size: 1rem;
        }
        
        .address-phone {
            font-size: 0.85rem;
        }
        
        .address-details {
            font-size: 0.9rem;
        }
        
        .select-btn {
            padding: 0.7rem 1.2rem;
            font-size: 0.85rem;
        }
        
        .add-new-btn {
            padding: 0.8rem 1.2rem;
            font-size: 0.9rem;
        }
    }

    .page-header {
        text-align: center;
        margin-bottom: 3rem;
        position: relative;
    }

    .page-header::after {
        content: '';
        position: absolute;
        bottom: -1rem;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 3px;
        background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
        border-radius: 2px;
    }

    h2 {
        color: var(--primary-color);
        font-size: 2.8rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        letter-spacing: -0.5px;
    }

    .page-subtitle {
        color: var(--text-secondary);
        font-size: 1.1rem;
        font-weight: 400;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 2rem;
        padding: 0.8rem 1.5rem;
        background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
        color: white;
        text-decoration: none;
        border-radius: var(--border-radius);
        font-weight: 600;
        transition: var(--transition-base);
        box-shadow: var(--shadow-sm);
        border: none;
        cursor: pointer;
    }

    .back-btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    }

    form {
        max-width: 100%;
    }

    .addresses-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .addr {
        display: flex;
        gap: 1rem;
        border: 2px solid #e8f5e9;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        margin-bottom: 0;
        background: white;
        transition: var(--transition-base);
        position: relative;
        overflow: hidden;
        cursor: pointer;
    }

    .addr::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-light), var(--primary-color));
        transform: scaleX(0);
        transition: var(--transition-base);
    }

    .addr:hover::before {
        transform: scaleX(1);
    }

    .addr:hover {
        border-color: var(--primary-light);
        transform: translateY(-3px);
        box-shadow: var(--shadow-lg);
        background: #f8faf9;
    }

    .addr input[type="radio"] {
        margin-top: 0.5rem;
        transform: scale(1.3);
        accent-color: var(--primary-color);
        cursor: pointer;
    }

    .addr input[type="radio"]:checked + .info {
        color: var(--primary-dark);
    }

    .info {
        line-height: 1.7;
        flex: 1;
        padding: 0.5rem 0;
    }

    .info strong {
        font-size: 1.1rem;
        color: var(--primary-color);
        font-weight: 700;
        margin-bottom: 0.6rem;
        display: block;
    }

    .info .address-details {
        color: var(--text-secondary);
        font-size: 0.9rem;
        background: #f8f9fa;
        padding: 0.8rem;
        border-radius: 8px;
        border-left: 4px solid var(--primary-light);
        margin-top: 0.4rem;
    }

    .btn {
        padding: 1rem 2rem;
        background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
        color: white;
        border: none;
        border-radius: var(--border-radius);
        cursor: pointer;
        font-weight: 600;
        font-size: 1.1rem;
        transition: var(--transition-base);
        box-shadow: var(--shadow-sm);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn:hover {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .btn:disabled {
        background: #ccc;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .add-link {
        margin-top: 2rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 600;
        padding: 1rem 1.5rem;
        border: 2px dashed var(--primary-light);
        border-radius: var(--border-radius);
        background: linear-gradient(135deg, rgba(102, 187, 106, 0.05), rgba(46, 125, 50, 0.05));
        transition: var(--transition-base);
        position: relative;
        overflow: hidden;
    }

    .add-link::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
        transition: left 0.5s;
    }

    .add-link:hover::before {
        left: 100%;
    }

    .add-link:hover {
        background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
        color: white;
        border-color: var(--primary-color);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: var(--text-secondary);
        background: linear-gradient(135deg, #f8faf9 0%, #e8f5e9 100%);
        border-radius: var(--border-radius);
        border: 2px dashed var(--primary-light);
        margin-bottom: 2rem;
    }

    .empty-state h3 {
        font-size: 1.8rem;
        margin-bottom: 1rem;
        color: var(--text-primary);
        font-weight: 600;
    }

    .empty-state p {
        font-size: 1.1rem;
        margin-bottom: 2rem;
    }

    .empty-state-icon {
        font-size: 4rem;
        color: var(--primary-light);
        margin-bottom: 1rem;
        opacity: 0.7;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
        flex-wrap: wrap;
    }

    @media (max-width: 768px) {
        body {
            padding: 1rem;
        }

        .checkout-container {
            padding: 2rem 1.5rem;
        }

        h2 {
            font-size: 2.2rem;
        }

        .addr {
            padding: 1.2rem;
            flex-direction: column;
            gap: 0.8rem;
        }

        .addresses-list {
            grid-template-columns: 1fr;
        }

        .addr input[type="radio"] {
            align-self: flex-start;
        }

        .form-actions {
            flex-direction: column;
        }

        .btn {
            width: 100%;
            justify-content: center;
        }

        .add-link {
            width: 100%;
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .checkout-container {
            padding: 1.5rem 1rem;
        }

        h2 {
            font-size: 1.8rem;
        }

        .page-subtitle {
            font-size: 1rem;
        }

        .addr {
            padding: 1rem;
        }

        .info strong {
            font-size: 1.1rem;
        }

        .info .address-details {
            font-size: 0.9rem;
            padding: 0.8rem;
        }
    }
</style>
</head>
<body>
    <!-- Fixed Stepper Start -->
    <div class="fixed-stepper">
      <div class="step">CART</div>
      <div class="step-separator"></div>
      <div class="step active">ADDRESS</div>
      <div class="step-separator"></div>
      <div class="step">PAYMENT</div>
    </div>
    <style>
      .fixed-stepper {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 1000;
        background: #fff;
        box-shadow: 0 2px 16px rgba(44,62,80,0.07);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0.7rem 0 0.7rem 0;
      }
      .fixed-stepper .step {
        color: #546e7a;
        font-size: 1.15rem;
        font-weight: 600;
        letter-spacing: 0.25em;
        text-transform: uppercase;
        position: relative;
        background: none;
        border: none;
        padding: 0 0.5rem 0.5rem 0.5rem;
        transition: color 0.2s;
      }
      .fixed-stepper .step.active {
        color: #2ec492;
      }
      .fixed-stepper .step.active::after {
        content: '';
        display: block;
        margin: 0 auto;
        width: 32px;
        height: 3px;
        background: #2ec492;
        border-radius: 2px;
        margin-top: 6px;
      }
      .fixed-stepper .step-separator {
        flex: 1 1 0;
        border-bottom: 2px dotted #bdbdbd;
        margin: 0 0.5rem 0.2rem 0.5rem;
        height: 0;
        min-width: 40px;
        max-width: 120px;
      }
      @media (max-width: 700px) {
        .fixed-stepper {
          font-size: 0.98rem;
          padding: 0.4rem 0 0.4rem 0;
        }
        .fixed-stepper .step {
          font-size: 0.98rem;
          padding: 0 0.3rem 0.3rem 0.3rem;
        }
        .fixed-stepper .step-separator {
          min-width: 18px;
        }
      }
      body, .checkout-container {
        padding-top: 70px !important;
      }
    </style>
    <!-- Fixed Stepper End -->

<div class="checkout-container">
    <a href="cart.html" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Cart
    </a>

    <div class="page-header">
        <h2>Select Delivery Address</h2>
        <p class="page-subtitle">Choose your preferred delivery location</p>
    </div>

<?php if ($addresses): ?>
<form action="select_address.php" method="POST">
        <div class="addresses-list">
    <?php foreach ($addresses as $addr): ?>
        <label class="addr">
            <input type="radio" name="address_id" value="<?= $addr['id'] ?>" required>
            <div class="info">
                        <strong><?= htmlspecialchars($addr['full_name']) ?></strong>
                        <div class="address-details">
                            <strong>Address:</strong> <?= htmlspecialchars($addr['street_name']) ?>, 
                <?= htmlspecialchars($addr['city']) ?>,
                            <?= htmlspecialchars($addr['state']) ?> – <?= htmlspecialchars($addr['postal_code']) ?>
                <?php if (!empty($addr['mobile_number'])): ?>
                                <br><strong>Phone:</strong> <?= htmlspecialchars($addr['mobile_number']) ?>
                <?php endif; ?>
                        </div>
            </div>
        </label>
    <?php endforeach; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">
                <i class="fas fa-check"></i> Use Selected Address
            </button>
        </div>
</form>
<?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">🏠</div>
            <h3>No saved addresses yet</h3>
            <p>Add your first delivery address to continue with checkout</p>
        </div>
<?php endif; ?>

    <a class="add-link" href="address.html">
        <i class="fas fa-plus-circle"></i> Add a New Address
    </a>
</div>

<script>
    // Add visual feedback when selecting addresses
    document.querySelectorAll('.addr input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            // Remove selected class from all addresses
            document.querySelectorAll('.addr').forEach(addr => {
                addr.style.borderColor = '#e8f5e9';
                addr.style.background = 'white';
            });
            
            // Add selected class to current address
            if (this.checked) {
                this.closest('.addr').style.borderColor = 'var(--primary-color)';
                this.closest('.addr').style.background = 'linear-gradient(135deg, #f8faf9 0%, #e8f5e9 100%)';
            }
        });
    });

    // Form validation and cart sync - consolidated event listener
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.querySelector('form[action="select_address.php"]');
        if (!form) return;
        
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Check if address is selected
            const selectedAddress = document.querySelector('input[name="address_id"]:checked');
            if (!selectedAddress) {
                alert('Please select a delivery address to continue.');
                return;
            }
            
            // Get cart using the correct key
            const userId = localStorage.getItem('currentUserId');
            const cartKey = userId ? `cartItems_${userId}` : 'cartItems_guest';
            const cart = JSON.parse(localStorage.getItem(cartKey) || '[]');
            
            // Check if cart is empty
            if (!cart.length) {
                alert('Cart is empty! Please add items to your cart before proceeding.');
                return;
            }
            
            // Send cart to server
            try {
                const response = await fetch('set_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cart: cart })
                });
                
                if (response.ok) {
                    // Submit the form to proceed to payment
                    form.submit();
                } else {
                    alert('Error syncing cart data. Please try again.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error syncing cart data. Please try again.');
            }
        });
    });
</script>

</body>
</html>
