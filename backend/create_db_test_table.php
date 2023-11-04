<?php

include_once "config.php";

try {
        if ($servertype == "mysql") {
                $sql = "CREATE TABLE IF NOT EXISTS $tablename (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        address VARCHAR(42) NOT NULL
                        )";
        } else {
                die('DB Config Error');
        }

        $conn->exec($sql);
        print("Created $tablename Table.\n");
        $conn = null;
} catch (PDOException $e) {
        echo $e->getMessage();
}

?>