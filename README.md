# Fresh & Healthy Shop

A modern, full-featured online shop for fresh fruits and dry fruits, built with PHP and HTML/CSS. This project provides a complete e-commerce experience, including user registration, product browsing, cart management, checkout, order history, and secure payment integration.

## Features

- **User Registration & Login**: Secure account creation and authentication.
- **Product Catalog**: Browse a wide range of fresh fruits and dry fruits with detailed product pages.
- **Search & Wishlist**: Search for products and add favorites to your wishlist.
- **Shopping Cart**: Add, update, and remove items from your cart with a responsive cart interface.
- **Address Book**: Manage multiple delivery addresses.
- **Checkout & Orders**:
  - Select delivery address and review your order.
  - Place orders with Cash on Delivery or Razorpay online payment.
  - Apply first-order coupon (20% off) and get free shipping on orders above ₹499.
- **Order History**: View past orders, download invoices (PDF), and track order status.
- **Password Reset**: Secure, email-based password reset (no OTP/SMS).
- **Responsive Design**: Mobile-friendly, modern UI with Bootstrap and custom CSS.

## Tech Stack

- **Backend**: PHP 7.1+ (PDO, sessions)
- **Frontend**: HTML5, CSS3, Bootstrap 5, FontAwesome
- **Database**: MySQL (see `db.php` for connection details)
- **PDF Generation**: [dompdf/dompdf](https://github.com/dompdf/dompdf)
- **Email**: [PHPMailer](https://github.com/PHPMailer/PHPMailer)
- **Payment Gateway**: [Razorpay](https://github.com/razorpay/razorpay-php)
- **SMS/Notifications**: [Twilio SDK](https://github.com/twilio/twilio-php) (legacy, not required for password reset)

## Setup Instructions

1. **Clone the repository** and place it in your web server directory (e.g., XAMPP's `htdocs`).
2. **Install dependencies** using Composer:
   ```bash
   composer install
   ```
3. **Database Setup**:
   - Create a MySQL database named `myshop`.
   - Update `db.php` if your DB credentials differ (default: user `root`, no password, port `3307`).
   - Import the required tables (`users`, `orders`, `order_items`, `user_addresses`, `password_resets`, etc.).
4. **Configure Web Server**:
   - Ensure PHP 7.1+ is installed.
   - Point your web server to the project directory.
5. **Set up Email and Payment**:
   - Configure PHPMailer in `process_payment.php` and related files for your SMTP server.
   - Update Razorpay API keys in `process_payment.php` and `payment_success.php`.

## Usage

- Visit `home_page.html` to browse products.
- Register or log in to start shopping.
- Add items to your cart, manage your wishlist, and proceed to checkout.
- Select or add a delivery address, choose payment method, and place your order.
- View your order history and download invoices from the "My Orders" section.
- Use "Forgot Password" for secure email-based password reset.

## Project Structure

- `home_page.html` — Main landing page
- `fruits.html`, `dry-fruits.html`, `fruit-drinks.html` — Product listings
- `product-detail.html` — Product details
- `cart.html` — Shopping cart
- `checkout.php` — Address selection and order review
- `payment.php`, `process_payment.php`, `payment_success.php` — Payment and order processing
- `my_orders.php`, `order_history.html` — Order history
- `register.html`, `login.html`, `forgot_password.html` — User authentication
- `address.html`, `saved_addresses.php` — Address management
- `wishlist.html` — Wishlist
- `invoices/` — Generated PDF invoices
- `vendor/` — Composer dependencies

## Security & Best Practices

- All sensitive operations require user authentication (sessions).
- Password reset is email-based only (no SMS/OTP).
- Prepared statements (PDO) are used for all DB queries.
- Tokens for password reset expire after 1 hour and are securely generated.

## License

This project is for educational/demo purposes. See individual library licenses in the `vendor/` directory. 