<!DOCTYPE html>
<html>
<head>
    <title>Button Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .btn-primary {
            background: linear-gradient(90deg, #43a047 0%, #388e3c 100%);
            color: #fff;
            border: none;
            border-radius: 24px;
            padding: 0.85rem 2.2rem;
            font-size: 1.08rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(44,62,80,0.08);
            transition: background 0.18s, box-shadow 0.18s;
            position: relative;
            z-index: 10;
            display: inline-block;
            text-align: center;
            line-height: 1.2;
            min-height: 48px;
            width: auto;
            min-width: 140px;
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, #388e3c 0%, #43a047 100%);
            box-shadow: 0 4px 16px rgba(44,62,80,0.13);
        }
        .form-actions {
            display: flex;
            gap: 1.2rem;
            margin-top: 2.2rem;
            justify-content: center;
            position: relative;
            z-index: 5;
        }
        .test-area {
            border: 2px solid red;
            padding: 20px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>Button Test</h1>
    
    <div class="test-area">
        <h3>Test 1: Simple Button</h3>
        <button type="button" class="btn-primary" onclick="alert('Button 1 clicked!')">
            <i class="fas fa-save"></i> Save Changes
        </button>
    </div>
    
    <div class="test-area">
        <h3>Test 2: Form Button</h3>
        <form method="POST" action="">
            <div class="form-actions">
                <button type="submit" class="btn-primary" id="testButton">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
    
    <div class="test-area">
        <h3>Test 3: Button with Debug</h3>
        <button type="button" class="btn-primary" id="debugButton">
            <i class="fas fa-save"></i> Save Changes
        </button>
    </div>
    
    <div id="debugOutput" style="background: #f0f0f0; padding: 10px; margin: 10px 0; font-family: monospace;"></div>
    
    <script>
        // Test button click detection
        document.getElementById('debugButton').addEventListener('click', function(e) {
            const output = document.getElementById('debugOutput');
            output.innerHTML += '<br>Button clicked at: ' + e.offsetX + ',' + e.offsetY;
            output.innerHTML += '<br>Button size: ' + this.offsetWidth + 'x' + this.offsetHeight;
            output.innerHTML += '<br>Time: ' + new Date().toLocaleTimeString();
        });
        
        // Test form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            const output = document.getElementById('debugOutput');
            output.innerHTML += '<br>Form submitted!';
        });
        
        // Test button hover
        document.getElementById('debugButton').addEventListener('mouseenter', function() {
            this.style.border = '2px solid blue';
        });
        
        document.getElementById('debugButton').addEventListener('mouseleave', function() {
            this.style.border = 'none';
        });
    </script>
    
    <p><a href="edited_profile.php">Back to Edit Profile</a></p>
</body>
</html> 