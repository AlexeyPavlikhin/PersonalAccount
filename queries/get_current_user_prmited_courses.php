<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    //echo $_SESSION['current_user_name'];
    $query = $connection->prepare("
                                    SELECT 
                                        spr_c.id as course_id,
                                        spr_c.course_name, 
                                        DATE_FORMAT(upc.available_until, '%d.%m.%Y') as available_until,
                                        spr_cc.id as course_contents_item_id,
                                        spr_cc.course_contents_item_name
                                    FROM  
                                        users_premited_courses upc, 
                                        spr_courses_name spr_c, 
                                        users usr,
                                        spr_courses_contents spr_cc
                                    WHERE 
                                        usr.id = '".$_SESSION['current_user_id']."'
                                    AND upc.user_id=usr.id 
                                    AND upc.course_id = spr_c.id 
                                    AND spr_c.id = spr_cc.course_id 
                                    ORDER BY spr_c.course_name, spr_cc.id
                                ");

    $query->execute();
    $response = json_encode($query->fetchAll(PDO::FETCH_DEFAULT));
    echo $response;    
}
?>

