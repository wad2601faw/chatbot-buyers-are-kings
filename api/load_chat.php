<?php
include "../config/koneksi.php";

$result = mysqli_query($koneksi,"
SELECT * FROM chats
ORDER BY id ASC
LIMIT 50
");

while($row = mysqli_fetch_assoc($result)){

    echo "<div class='msg user'>".$row['message']."</div>";
    echo "<div class='msg bot'>".$row['reply']."</div>";
}
