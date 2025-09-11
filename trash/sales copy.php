<link href="./css/styles.css" rel="stylesheet">
<link href="./css/jost.css" rel="stylesheet">

<?php
    //error_reporting(0);
    ob_start();
    session_start();
    include('config.php');
    
    echo "<html>";
    echo "<header class='my_header'>";
    echo "  <div class='logo'> </div>";
    echo "  <div class='my_header_polygon'></div>";
    echo "</header>";
    
    echo "<header class='my_header2'>";
    echo "<div class='menu-bar'>";
    echo "  <ul>";
    echo "      <li class='right'>";
    echo "          ".$_SESSION['current_user_name'];
    echo "          <ul>";
    echo "              <li><a href='#'>Профиль</a></li>";
    echo "              <li><a href='login.php'>Выход</a></li>";
    echo "          </ul>";
    echo "        </li>";
    echo "    </ul>";
    echo "</div>";
    echo "</header>";
    echo "<main>";
    echo "<br/><br/>";
    
    if(!isset($_SESSION['current_user_id'])){
        header('Location: login.php');
        exit;
    } else {
        if ($_SESSION['current_user_group'] != 'client' ) {
            echo "<div class='menu'>";
            echo "    <ul>";
            echo "        <li><a class='menu_button' href='lk.php'><div class='menu_button_text'>Управление заказами</div></a></li>";
            echo "        <li><a class='menu_button' href='uc.php'><div class='menu_button_text'>Управление пользователями</div></a></li>";
            echo "        <li><a class='menu_button_atcive' href='sales.php'><div class='menu_button_text_active'>Управление продажами</div></a></li>";
            echo "    </ul>";
            echo "</div>";
            echo "<div class='sidenav'>";
            echo "<form method='GET' action=''>";
            echo "  <label>Группа</label>";
            //echo "  <select class='msll_filter' name='field_type' required onchange='form.submit()'>";
            //echo "  <select class='msll_filter' name='field_type' required onchange='$.post(\"sales.php\", {text: 'Текст'}, function(data){ alert(data); });'>";
            echo "  <select class='msll_filter' name='field_type' required onchange='$.post(\"sales.php\", {text: \"Текст\"});'>";
            //echo "  <select class='msll_filter' name='field_type' required onchange='alert(\"один два три\");'>";
            
            echo "            <option value=''>Выберите поле</option>";
            echo "            <option value='ФИО'>ФИО</option>";
            echo "            <option value='Продукт'>Продукт</option>";
            echo "            <option value='Подродукт'>Подродукт</option>";
            echo "  </select>";

            echo "  <select class='msll_filter' name='search_operation' required>";
            echo "            <option value='Совпадает'>Совпадает</option>";
            echo "            <option value='Содержит'>Содержит</option>";
            echo "            <option value='Не совпадает'>Не совпадает</option>";
            echo "            <option value='Не содержит'>Не содержит</option>";
            echo "  </select>";
            echo "  <textarea class='ta_searched_value' name='ta_searched_value' rows='2' cols='130'></textarea>";
            echo "  <button class='msll_button' type='submit' name='close_form' value='close_form'>Закрыть без сохранения</button>";
            echo "  </form>";
            echo "  <textarea class='ta_searched_value' name='ta_search_parametr' rows='2' cols='130'></textarea>";


            echo "</div>";
            echo "<div class='msll_body'>";
            echo "<form method='GET' action=''>";
            echo "  <table class='msll_table'>";
            echo "      <tr>";
            echo "          <th width='4%'>№</th>";
            echo "          <th width='29%'>Фаимлия</th>";
            echo "          <th width='29%'>Имя</th>";
            echo "          <th width='29%'>Отчество</th>";
            echo "          <th width='10%'>Подробно</th>";
            echo "      </tr>";
                        
            $query = $connection->prepare("SELECT ROW_NUMBER() OVER (order by tbl.client_last_name, tbl.client_first_name, tbl.client_patronymic) num, tbl.client_id, tbl.client_last_name, tbl.client_first_name, tbl.client_patronymic from (select DISTINCT cl.client_id, cl.client_last_name, cl.client_first_name,  cl.client_patronymic, cl.comment  FROM sales sl, clients cl, products pr, subproducts spr WHERE sl.client_id=cl.client_id and sl.product_id=pr.product_id and sl.subproduct_id=spr.subproduct_id) tbl;");
            $query->execute();

            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . $row["num"] . "</td>";
                echo "<td>" . $row["client_last_name"] . "</td>";
                echo "<td>" . $row["client_first_name"] . "</td>";
                echo "<td>" . $row["client_patronymic"] . "</td>";
                echo "<td> <a href='lk.php?action=detail&id=".$row["client_id"]."'>Подробно</a> </td>";
                echo "</tr>";
            }
            echo "  </table>";
            
            echo "</form>";
            echo "</div>";  

        } else {
            echo "<p class='error'> Доступ закрыт!</p>";
        }
    }
    echo "</main>";
    echo "<footer class='msll_footer'>";
    echo "  <div class='msll_footer_polygon_dark_gray'></div>";
    echo "  <div class='msll_footer_polygon_light_gray'></div>";
    echo "  <div class='msll_footer_polygon_red'></div>";
    echo "</footer>";
    echo "</html>";
?>
