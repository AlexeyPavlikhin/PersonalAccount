<link rel="stylesheet" href="styles.css">
<link href="https://fonts.googleapis.com/css2?family=Jost:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
<!--<script src="https://code.jquery.com/jquery-3.3.1.js" integrity="sha256-2Kok7MbOyxpgUVvAk/HJ2jigOSYS2auK4Pfzbm7uH60=" crossorigin="anonymous"></script>-->
<!--<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>-->

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
        
    <header class='my_header2' id='header_menu'>
    <div class='menu-bar'>
        <ul>
            <li class='right' >
                {{ user_name }}
                <ul>
                    <li><a href='#'>Профиль</a></li>
                    <li><a href='login.php'>Выход</a></li>
                </ul>
            </li>
        </ul>
    </div>
    </header>
    <main id='main'>
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
  
                <label>Фильтр</label>

                <select class='msll_filter' name='fieldFirstFilter' id='fieldFirstFilter' required @change='onSelectFirstFilter();'>
                    <option disabled value=''>Выберите поле</option>
                    <option v-for="option in options1" :value="option.value">{{ option.text }}</option>
                </select>

                <select class='msll_filter' name='search_operation' id='search_operation' required>
                    <option v-for="option in options2" :value="option.value">{{ option.text }}</option>
                </select>

                
                <input class='msll_filter' type="search" list="options" id=item_for_search>
                <datalist class='msll_filter' id="options">
                    <option v-for="item in items_for_search" :value="item">
                </datalist>

                <!--<button class='msll_button' type='submit' name='close_form' value='close_form'>Закрыть без сохранения</button>-->

                <a class='msll_button' href='#'  id='BtnApply' @click='onClikBtnApply(true);'>Применить</a>

                <!--<textarea class='ta_searched_value' name='ta_search_parametr' rows='2' cols='130'></textarea>-->

                <label>Применённые фильтры</label>
                <div>
                    <table class='msll_table'>
                        <tr v-for="condition in conditions">
                            <td>{{condition.object}}</td>
                            <td>{{condition.operation}}</td>
                            <td>{{condition.value}}</td>
                            <td><a href='#'  v-on:click='onClikDeleteItemOfConditions(condition.item_id)'>{{condition.item_id}}</a></td>

                        <tr>

                    </table>
                </div>

            </form>
            
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

                    <tr v-for="client_item in list_of_clients">
                        <td>{{client_item.num}}</td>
                        <td>{{client_item.client_second_name}}</td>
                        <td>{{client_item.client_first_name}}</td>
                        <td>{{client_item.client_patronymic}}</td>
                        <td>{{client_item.client_id}}</td>
                    <tr>

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
               user_name: 'Имя Пользователя' 

            }
        }
    }).mount('#header_menu')

    createApp({
        data() {
            return {
                opt: 0,

                options1: [
                    { text: 'ФИО', value: 'ФИО' },
                    { text: 'Продукт', value: 'Продукт' },
                    { text: 'Подпродукт', value: 'Подпродукт' }
                ],

                options2: [
                    { text: 'Содержит', value: 'Содержит' },
                    { text: 'Не содержит', value: 'Не содержит' },
                    { text: 'Совпадает', value: 'Совпадает' },
                    { text: 'Не совпадает', value: 'Не совпадает' }
                ],
                
                items_for_search: [],

                conditions: [],

                list_of_clients: [
                    { num: '1', client_second_name: 'Фамилия1', client_first_name: 'Имя1', client_patronymic: 'Отчество1', client_id: '1' },
                    { num: '2', client_second_name: 'Фамилия2', client_first_name: 'Имя2', client_patronymic: 'Отчество2', client_id: '2' }
                ]

            }
        },
        async mounted() {
            this.onSelectFirstFilter();
            try {
                    const response = await axios.get('./queries/get_default_list_of_cliets.php');
                    if (response.data) {
                        //обрабатываем ответ
                        this.list_of_clients=response.data;
                    } else {
                        // пустой ответ
                        console.log('Ответ от сервера пустой (data undefined/null)');
                    }
                } catch (error) {
                    // Обработка ошибки
                    console.error('Ошибка при запросе:', error);
                    if (error.response) {
                        console.error('Статус ошибки:', error.response.status);
                        console.error('Данные ошибки:', error.response.data);
                    }
                }            
        },
        methods: {

            async onSelectFirstFilter(){
                // Assuming you have a select element with id="mySelect"
                const selectElement = document.getElementById('fieldFirstFilter');
                // Get the value of the currently selected option
                let url;

                if (selectElement.value=="ФИО"){
                    url="./queries/get_all_fio.php";

                } else if (selectElement.value=="Продукт"){
                    url="./queries/get_all_products.php";

                } else if (selectElement.value=="Подпродукт"){
                    url="./queries/get_all_subproducts.php";

                }

                //alert(message);
                
                    try {
                        //const response = await axios.get('./queries/get_all_items_of_selected_object.php');
                        const response = await axios.get(url);

                        // Обработка успешного ответа
                        if (response.data) {
                        // Далее работаем с данными
                        this.items_for_search=response.data;

                        } else {
                            console.log('Ответ от сервера пустой (data undefined/null)');
                        }

                    } catch (error) {
                        // Обработка ошибки
                        console.error('Ошибка при запросе:', error);
                        if (error.response) {
                            console.error('Статус ошибки:', error.response.status);
                            console.error('Данные ошибки:', error.response.data);
                        }
                    }
                //selectElement.value = "";
                document.getElementById('item_for_search').value="";

                
            },
            async onClikBtnApply(is_need_add_condition){
                
                if (is_need_add_condition){
                    if (document.getElementById('item_for_search').value != ''){
                        this.conditions.push({ object: document.getElementById('fieldFirstFilter').value, operation: document.getElementById('search_operation').value, value: document.getElementById('item_for_search').value, item_id: this.conditions.length}); 
                    }
                }

                try {
                    const response = await axios.get('./queries/get_list_of_cliets_by_conditions2.php?conditions='+JSON.stringify(this.conditions));
                    if (response.data) {
                        console.log(response.data);
                    } else {
                        console.log('Ответ от сервера пустой (data undefined/null)');
                    }
                } catch (error) {
                    // Обработка ошибки
                    console.error('Ошибка при запросе:', error);
                    if (error.response) {
                        console.error('Статус ошибки:', error.response.status);
                        console.error('Данные ошибки:', error.response.data);
                    }
                }


                try {
                    const response = await axios.get('./queries/get_list_of_cliets_by_conditions.php?conditions='+JSON.stringify(this.conditions));
                    //const response = await axios.get('./queries/get_list_of_cliets_by_conditions.php', conditions, {headers: { 'Content-Type': 'application/x-www-form-urlencoded' } });
                    // Обработка успешного ответа
                    if (response.data) {
                        this.list_of_clients=response.data;

                    } else {
                        console.log('Ответ от сервера пустой (data undefined/null)');
                    }

                } catch (error) {
                    // Обработка ошибки
                    console.error('Ошибка при запросе:', error);
                    if (error.response) {
                        console.error('Статус ошибки:', error.response.status);
                        console.error('Данные ошибки:', error.response.data);
                    }
                }
                document.getElementById('item_for_search').value="";

            },
            onClikDeleteItemOfConditions(id){
                //delete this.conditions[id];
                //console.log(id);
                this.conditions.splice(this.conditions.findIndex((item) => item.item_id === id), 1); 
                this.onClikBtnApply(false);

            }

            
        }
           
    }).mount('#main')
</script>