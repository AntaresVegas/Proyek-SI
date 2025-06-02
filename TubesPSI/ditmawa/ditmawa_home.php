<?php
session_start();
if (!isset($_SESSION['username'])) {
    $_SESSION['username'] = "Admin Ditmawa";
}
$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Ditmawa UNPAR</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: url('../img/backgroundUnpar.jpeg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            color: white;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 165, 0, 0.95);
            padding: 15px 30px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            color: black;
            font-weight: bold;
        }

        .logo img {
            height: 50px;
        }

        nav a {
            margin: 0 15px;
            color: black;
            font-weight: bold;
            text-decoration: none;
        }

        nav a:hover {
            text-decoration: underline;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: black;
        }

        .avatar {
            width: 30px;
            height: 30px;
            background: #ddd;
            border-radius: 50%;
        }

        .calendar-container {
            background: white;
            color: black;
            max-width: 800px;
            margin: 60px auto;
            padding: 20px;
            border-radius: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .calendar-header button {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #004d40;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            width: 14.2%;
            height: 80px;
            text-align: center;
            border: 1px solid #ddd;
            vertical-align: top;
        }

        .footer-note {
            margin-top: 15px;
            color: red;
            font-weight: bold;
            text-align: center;
        }

        footer {
            text-align: center;
            margin-top: 60px;
            padding: 20px;
            background: #004d40;
            color: white;
        }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="logo">
            <img src='../img/logoUnpar.png' alt="UNPAR" />
            <span>Pengelolaan Event UNPAR</span>
        </div>
        <nav>
            <a href="ditmawa_home.php">Home</a>
            <a href="ditmawa_dataEvent.php">Data Event</a>
            <a href="#">Laporan</a>
        </nav>

        <div class="user-info">
            <span><?= htmlspecialchars($username); ?></span>
            <div class="avatar"></div>
            <a href="logout.php" title="Logout" style="color:black;">⎋</a>
        </div>
    </header>

    <main class="calendar-container">
        <div class="calendar-header">
            <button onclick="changeMonth(-1)">&#8592;</button>
            <div id="monthYear">April 2025</div>
            <button onclick="changeMonth(1)">&#8594;</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th>
                    <th>Thu</th><th>Fri</th><th>Sat</th>
                </tr>
            </thead>
            <tbody id="calendar-body">
                <!-- Diisi oleh JS -->
            </tbody>
        </table>
        <div class="footer-note">KLIK UNTUK DETAIL TANGGAL LEBIH LANJUT</div>
    </main>

    <footer>
        &copy; 2025 Direktorat Kemahasiswaan UNPAR
    </footer>

    <script>
        const monthNames = ["January", "February", "March", "April", "May", "June",
                            "July", "August", "September", "October", "November", "December"];

        let currentMonth = 3; // April (0-based)
        let currentYear = 2025;

        function generateCalendar(month, year) {
            const firstDay = new Date(year, month).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();

            const calendarBody = document.getElementById("calendar-body");
            const monthYearLabel = document.getElementById("monthYear");
            calendarBody.innerHTML = "";
            monthYearLabel.textContent = `${monthNames[month]} ${year}`;

            let date = 1;
            for (let i = 0; i < 6; i++) {
                const row = document.createElement("tr");

                for (let j = 0; j < 7; j++) {
                    const cell = document.createElement("td");
                    if (i === 0 && j < firstDay) {
                        cell.textContent = "";
                    } else if (date > daysInMonth) {
                        break;
                    } else {
                        cell.innerHTML = `<div>${date}</div>`;
                        date++;
                    }
                    row.appendChild(cell);
                }

                calendarBody.appendChild(row);
                if (date > daysInMonth) break;
            }
        }

        function changeMonth(offset) {
            currentMonth += offset;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            } else if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            generateCalendar(currentMonth, currentYear);
        }

        document.addEventListener("DOMContentLoaded", () => {
            generateCalendar(currentMonth, currentYear);
        });
    </script>
</body>
</html>
