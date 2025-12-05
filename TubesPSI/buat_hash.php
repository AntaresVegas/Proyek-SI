<?php

// Ganti "asp123" dengan password baru yang Anda inginkan
$password_baru = "asp123"; 

$hash = password_hash($password_baru, PASSWORD_DEFAULT);

echo "Password Anda: " . $password_baru . "<br>";
echo "Hash baru Anda: " . $hash;

?>