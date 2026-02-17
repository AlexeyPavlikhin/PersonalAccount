<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    //echo $_SESSION['current_user_name'];
    $query = $connection->prepare("
                                    SELECT 
                                        id, 
                                        course_id, 
                                        course_item_type, 
                                        course_item_video_link, 
                                        course_item_text, 
                                        course_item_href, 
                                        course_item_href_text, 
                                        course_item_ancnor_name, 
                                        course_item_picture, 
                                        course_item_doc_pdf 
                                    FROM 
                                        courses_data 
                                    WHERE 
                                        course_id = '".$_GET['course_id']."' 
                                    ORDER BY id;
                                ");
    $query->execute();
    $response = json_encode($query->fetchAll(PDO::FETCH_DEFAULT));
    echo $response;    
}
?>

