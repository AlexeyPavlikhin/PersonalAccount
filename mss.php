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
    <head> 
        <link href="./css/styles.css" rel="stylesheet">
        <link href="./css/jost.css" rel="stylesheet">
        <script src="./js/axios.min.js"></script>
        
        <title>Личный кабинет: Сатус услуг</title>

        <link rel="icon" type="image/x-icon" href="./favicon.ico">
        <link rel="shortcut icon" href="./favicon.ico">

    </head> 
    <body>
        <div id='app'>
            <header class='my_header'>
                <div class='logo'> </div>
                <div class='my_header_polygon'></div>
            </header>
                
            <header class='my_header2' id='header_menu'>
                <div id="id_MenuProfileAndExit">
                    <Menu-Profile-And-Exit ref="ref_MenuProfileAndExit"/>
                </div> 
            </header>   
            <main>
                <!--br/><br/-->
                <!--div class='menu'>
                    <ul>
                        <li><a class='menu_button' href='lk.php'><div class='menu_button_text'>Управление заказами</div></a></li>
                        <li><a class='menu_button_atcive' href='uc.php'><div class='menu_button_text_active'>Управление пользователями</div></a></li>
                        <li><a class='menu_button' href='sales.php'><div class='menu_button_text'>Управление продажами</div></a></li>
                    </ul>
                </div-->
                    
                <div class='sidenav'>
                </div>

                <div class='msll_body'>
                    <Navigation-Menu ref="ref_NavigationMenu"></Navigation-Menu>
                    <table class='msll_table'>
                        <tbody>
                            <tr>
                                <th width='5%'>№ заказа</th>
                                <th width='10%'>Дата изменения</th>
                                <th width='25%'>Статус (для клиента)</th>
                                <th width='50%'>Комментарий (для сотрудников)</th>
                                <th width='10%'>Подробно</th>
                            </tr>
                            <tr v-for="order in orders">
                                <td>{{order.order_id}}</td>
                                <td>{{order.formated_row_creation_time}}</td>
                                <td>{{order.order_status}}</td>
                                <td>{{order.order_description}}</td>
                                <td><input class="msll_small_button" type="button" value = "Подробно" @click="DetailOrderStatus(order.order_id)"></td>
                            </tr>
                        </tbody>
                    </table>

                    <div id="id_spinner_panel" class="spinner">
                        <pulse-loader :color="p_color" :size="p_size"></pulse-loader>
                    </div>

                    <div id="id_FormModalMessage" class="modal">
                        <Form-Modal-Message ref="ref_FormModalMessage"/>
                    </div>  

                </div>  
            </main>
            
            <footer class='msll_footer'>
                <div class='msll_footer_polygon_dark_gray'></div>
                <div class='msll_footer_polygon_light_gray'></div>
                <div class='msll_footer_polygon_red'></div>
            </footer>
        </div>
    </body>
</html>

<script type="importmap">{
    "imports": {
      "vue": "./js/vue3.esm-browser.js"
    }
  }
</script>

<script src="./js/vue-spinner.min.js"></script>
<script>
  var PulseLoader = VueSpinner.PulseLoader;
</script>

<script type="module">
    import FormModalMessage from './components/Form_Modal_Message.js';
    import MenuProfileAndExit from './components/Menu_Profile_And_Exit.js';
    import NavigationMenu from './components/Navigation_Menu.js';
    
    import { createApp } from 'vue';

    const app = createApp({
        components: {
            PulseLoader,
            FormModalMessage,
            MenuProfileAndExit,
            NavigationMenu
        },
        data() {
            return {
                orders: [],
                p_color: "#bd162b",
                p_size: "20px"

            }
        },
        async mounted() {
            this.$refs.ref_NavigationMenu.init("ss.php");
            this.get_orders();

        },
        methods: {
            callback_profile(){
            },
            async get_orders(){
                try {
                    const response = await axios.get("./queries/get_orders.php");
                    if (response.data) {
                        //console.log(response.data);
                        this.orders=response.data;

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
            },

            DetailOrderStatus(in_order){
                //console.log(in_user)

                //this.$refs.ref_FormEditUser.init(in_order);

                //отключить прокрутку страницы
                //document.body.style.overflow = 'hidden';

                //сделать элемент модальным     
                //document.getElementById("id_FormUpdateUser").style.display = "block";                

            }

        }
    });
    app.mount('#app');
    
</script>
