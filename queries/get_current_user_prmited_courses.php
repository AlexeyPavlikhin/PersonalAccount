<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    //echo $_SESSION['current_user_name'];
    $query = $connection->prepare("
                                    SELECT 
                                        spr_c.id as course_id,
                                        spr_c.course_name, 
                                        CONCAT('https://runtime.video.cloud.yandex.net/player/', spr_c.course_video_link, '?autoplay=0&mute=0') as course_video_link,
                                        DATE_FORMAT(upc.available_until, '%d.%m.%Y') as available_until
                                    FROM  
                                        users_premited_courses upc, 
                                        spr_courses spr_c, 
                                        users usr  
                                    WHERE 
                                        usr.id = '".$_SESSION['current_user_id']."'
                                    AND upc.user_id=usr.id 
                                    AND upc.course_id = spr_c.id 
                                    ORDER BY spr_c.course_name
                                ");

    $query->execute();
    $response = json_encode($query->fetchAll(PDO::FETCH_DEFAULT));
    echo $response;    
}
?>

