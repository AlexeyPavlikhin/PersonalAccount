<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    $query = $connection->prepare(  "SELECT 
                                        u.login,
                                        u.username,
                                        u.email,
                                        u.user_group
                                    FROM users u 
                                    order by u.login"
                                );
    $query->execute();
    $response = json_encode($query->fetchAll(PDO::FETCH_DEFAULT));
    echo $response;
}
?>

