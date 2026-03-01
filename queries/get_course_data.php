<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    //echo $_SESSION['current_user_name'];
    $query = $connection->prepare("
                                    SELECT 
                                        cd.id, 
                                        cd.course_contents_item_id, 
                                        spr_t.item_type_name as course_item_type_name,
                                        cd.course_item_data,
                                        cd.course_item_data2
                                    FROM 
                                        courses_data cd, spr_courses_item_types spr_t
                                    WHERE 
                                        cd.course_contents_item_id = '".$_GET['course_contents_item_id']."' 
                                    AND cd.course_item_type_id = spr_t.item_type_id
                                    ORDER BY id;
                                ");
    $query->execute();
    $response = json_encode($query->fetchAll(PDO::FETCH_DEFAULT));
    echo $response;    
}
?>

