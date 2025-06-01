<?php
// Define paths for the images
$logo_path = './img/logo.png';  // Update with your actual path
$background_path = './img/backgroundUnpar.jpeg';  // Update with your actual path
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sistem Pengelolaan Event Unpar</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('<?php echo $background_path; ?>') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            display: flex;
            gap: 20px;
            background: rgba(255, 255, 255, 0.8);  /* Semi-transparent background to make it look nice with the image */
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            align-items: center;
            max-width: 900px;
            width: 100%;
        }

        .product-item {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .product-item img {
            width: 350px;  /* Adjust size to fit the design better */
            height: auto;
            object-fit: cover;
            border-radius: 8px;
        }

        .login-box {
            flex: 2;
            max-width: 450px;
        }

        .header {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #3498db;
            outline: none;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background: #2980b9;
        }

        .form-links {
            text-align: center;
            margin-top: 15px;
        }

        .form-links a {
            color: #3498db;
            text-decoration: none;
        }

        .form-links a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                padding: 20px;
            }

            .product-item {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="product-item">
            <img src="<?php echo $logo_path; ?>" alt="Logo Unpar" />
        </div>

        <div class="login-box">
            <div class="header">Pengelolaan Event UNPAR</div>
            <!-- Form login -->
            <form action="process_login.php" method="POST" class="login-form">
                <div class="form-group">
                    <label for="user_type">Login Sebagai:</label>
                    <select name="user_type" id="user_type" required onchange="updateFormAction()">
                        <option value="mahasiswa">Mahasiswa</option>
                        <option value="ditmawa">Ditmawa</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="username">Email:</label>
                    <input type="text" id="email" name="email" required autocomplete="email" />
                </div>

                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password" />
                </div>

                <div class="form-group">
                    <button type="submit" class="btn">Login</button>
                </div>
                <!-- Link untuk ditmawa dan mahasiswa -->
                <div class="form-links" id="register-link">
                    <a href="./mahasiswa/register.php" id="register-mahasiswa" style="display:none;">Daftar Akun Baru Mahasiswa</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateFormAction() {
            const form = document.querySelector('.login-form');
            const userType = document.getElementById('user_type').value;
            const registerMahasiswa = document.getElementById('register-mahasiswa');

            if (userType === 'mahasiswa') {
                form.action = './mahasiswa/process_login.php';
                registerMahasiswa.style.display = 'block';
            } else {
                form.action = './ditmawa/process_login.php';
                registerMahasiswa.style.display = 'none';
            }
        }
        document.addEventListener('DOMContentLoaded', updateFormAction);
    </script>
</body>
</html>
