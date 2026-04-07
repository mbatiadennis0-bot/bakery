<?php
include 'db.php';

echo "<h2>Bakery System Connection Test</h2>";

if ($conn->connect_error) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
    echo "❌ Connection Failed: " . $conn->connect_error;
    echo "</div>";
    echo "<p><strong>Checklist:</strong></p>";
    echo "<ul>
            <li>Is the password <strong>MLYWTSpYP5</strong>?</li>
            <li>Is the hostname <strong>sql308.infinityfree.com</strong>?</li>
            <li>Is the database name <strong>if0_41459113_bakerysystem</strong>?</li>
          </ul>";
} else {
    echo "<div style='color: green; padding: 10px; border: 1px solid green;'>";
    echo "✅ Success! Connected to the database.";
    echo "</div>";

    // Test if the 'products' table exists and check column names
    $result = $conn->query("SHOW COLUMNS FROM products");
    if ($result) {
        echo "<h3>Products Table Structure:</h3><ul>";
        while($row = $result->fetch_assoc()){
            echo "<li>Field: <strong>" . $row['Field'] . "</strong></li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠️ Table 'products' not found. Did you import your SQL file in phpMyAdmin?</p>";
    }
}
?>