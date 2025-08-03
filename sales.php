<link rel="stylesheet" href="styles.css">
<link href="https://fonts.googleapis.com/css2?family=Jost:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>

<?php
    //error_reporting(0);
    ob_start();
    session_start();
    include('config.php');
    if(!isset($_SESSION['current_user_id'])){
        header('Location: login.php');
        exit;
    } else {
        if ($_SESSION['current_user_group'] == 'client' ) {
            header('Location: login.php');
            exit;
        }
    }
?>
 
<html>
    <header class='my_header'>
    <div class='logo'> </div>
    <div class='my_header_polygon'></div>
    </header>
        
    <header class='my_header2'>
    <div class='menu-bar'>
        <ul>
            <li class='right' id='header_menu'>
                {{ user_name }}
                <ul>
                    <li><a href='#'>Профиль</a></li>
                    <li><a href='login.php'>Выход</a></li>
                </ul>
            </li>
        </ul>
    </div>
    </header>
    <main>
        <br/><br/>
        <div class='menu'>
            <ul>
                <li><a class='menu_button' href='lk.php'><div class='menu_button_text'>Управление заказами</div></a></li>
                <li><a class='menu_button' href='uc.php'><div class='menu_button_text'>Управление пользователями</div></a></li>
                <li><a class='menu_button_atcive' href='sales.php'><div class='menu_button_text_active'>Управление продажами</div></a></li>
            </ul>
        </div>
        <div class='sidenav'>
            <form method='GET' action='' id='filter'>
  
                <label>Группа</label>
                <select class='msll_filter' name='field_type' required @change='onSelectFirstFilter("раз два три!!!!");'>
                    <option disabled value=''>Выберите поле</option>
                    <option v-for="option in options1" :value="option.value">{{ option.text }}</option>
                </select>

                <select class='msll_filter' name='search_operation' required>
                    <option value='Совпадает'>Совпадает</option>
                    <option value='Содержит'>Содержит</option>
                    <option value='Не совпадает'>Не совпадает</option>
                    <option value='Не содержит'>Не содержит</option>
                </select>
                <textarea class='ta_searched_value' name='ta_searched_value' rows='2' cols='130'></textarea>
                <button class='msll_button' type='submit' name='close_form' value='close_form'>Закрыть без сохранения</button>
            </form>
            <textarea class='ta_searched_value' name='ta_search_parametr' rows='2' cols='130'></textarea>
        </div>
        <div class='msll_body'>
            <form method='GET' action=''>
                <table class='msll_table'>
                    <tr>
                        <th width='4%'>№</th>
                        <th width='29%'>Фаимлия</th>
                        <th width='29%'>Имя</th>
                        <th width='29%'>Отчество</th>
                        <th width='10%'>Подробно</th>
                    </tr>
                            
                <!--"SELECT ROW_NUMBER() OVER (order by tbl.client_second_name, tbl.client_first_name, tbl.client_patronymic) num, tbl.client_id, tbl.client_second_name, tbl.client_first_name, tbl.client_patronymic from (select DISTINCT cl.client_id, cl.client_second_name, cl.client_first_name,  cl.client_patronymic, cl.comment  FROM sales sl, clients cl, products pr, subproducts spr WHERE sl.client_id=cl.client_id and sl.product_id=pr.product_id and sl.subproduct_id=spr.subproduct_id) tbl;");
                

                while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>
                    echo "<td>" . $row["num"] . "</td>
                    echo "<td>" . $row["client_second_name"] . "</td>
                    echo "<td>" . $row["client_first_name"] . "</td>
                    echo "<td>" . $row["client_patronymic"] . "</td>
                    echo "<td> <a href='lk.php?action=detail&id=".$row["client_id"]."'>Подробно</a> </td>
                    echo "</tr>
                }
        -->
                </table>
                
            </form>
        </div>  

    
    </main>
    <footer class='msll_footer'>
        <div class='msll_footer_polygon_dark_gray'></div>
        <div class='msll_footer_polygon_light_gray'></div>
        <div class='msll_footer_polygon_red'></div>
    </footer>
</html>


<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                user_name: 'Имя Пользователя',

            }
        }
    }).mount('#header_menu')

    createApp({
        data() {
            return {
                user_name: 'Имя Пользователя',

                options1: [
                    { text: 'ФИО', value: 'ФИО' },
                    { text: 'Продукт', value: 'Продукт' },
                    { text: 'Подродукт', value: 'Подродукт' }
                ]
            }
        },
        methods: {
            onSelectFirstFilter(message){
                alert(message)
            }

        }
    }).mount('#filter')
</script>