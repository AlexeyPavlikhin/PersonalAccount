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
        
        <title>Личный кабинет: Управление пользователями</title>

        <link rel="icon" type="image/png" sizes="32x32" href="./pictures/Iogo-1.png" media="(prefers-color-scheme: light)">
        <link rel="icon" type="image/png" sizes="32x32" href="-./pictures/Iogo-2.png" media="(prefers-color-scheme: dark)">
        <link rel="icon" type="image/svg+xml" sizes="any" href="./pictures/Iogo-4.svg">
        <link rel="apple-touch-icon" type="image/png" href="./pictures/Iogo-3.png">
        <link rel="icon" type="image/png" sizes="192x192" href="./pictures/Iogo-3.png">

    </head> 
    <body>

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
                        <li><input class="msll_button_in_table" type="button" value = "Профиль" @click="ChangeUser(user)"></li>
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
                    <li><a class='menu_button_atcive' href='uc.php'><div class='menu_button_text_active'>Управление пользователями</div></a></li>
                    <li><a class='menu_button' href='sales.php'><div class='menu_button_text'>Управление продажами</div></a></li>
                </ul>
            </div>
                
            <div class='sidenav'>
                <input class="msll_button" type="button" value = "Новый пользователь" @click="onClikCreateNewUser()">
            </div>

            <div class='msll_body'>
                <table class='msll_table'>
                    <tbody>
                        <tr>
                            <th>Login</th>
                            <th>Имя пользователя</th>
                            <th>E-mail</th>
                            <th>Группа пользователей</th>
                            <th></th>
                        </tr>
                        <tr v-for="user in users">
                            <td>{{user.login}}</td>
                            <td>{{user.username}}</td>
                            <td>{{user.email}}</td>
                            <td>{{user.user_group}}</td>
                            <td><input class="msll_small_button" type="button" value = "Изменить" @click="ChangeUser(user)"></td>
                        </tr>
                    </tbody>
                </table>

                <div id="id_FormCreateNewUserID" class="modal">
                    <Form-Create-New-User ref="ref_FormCreateNewUser"/>
                </div>  

                <div id="id_FormEditUserID" class="modal">
                    <Form-Edit-User ref="ref_FormEditUser"/>
                </div>  
                
            </div>  
        
        </main>
        
        <footer class='msll_footer'>
            <div class='msll_footer_polygon_dark_gray'></div>
            <div class='msll_footer_polygon_light_gray'></div>
            <div class='msll_footer_polygon_red'></div>
        </footer>
    </body>
</html>

<script type="importmap">{
    "imports": {
      "vue": "./js/vue3.esm-browser.js"
    }
  }
</script>

<script type="module">
    import FormCreateNewUser from './components/Form_Create_New_User.js';
    import FormEditUser from './components/Form_Edit_User.js';

    import { createApp } from 'vue';

    const app = createApp({
        data() {
            return {
               user_name: 'Имя Пользователя' 

            }
        },
        async mounted() {
            try {
                    const response = await axios.get('./queries/get_current_user_name.php');
                    if (response.data) {
                        //обрабатываем ответ
                        this.user_name=response.data;
                        //console.log(response.data);
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
        }    
    });     
    app.mount('#header_menu');

    const app2 = createApp({
        components: {
            FormCreateNewUser,
            FormEditUser
        },
        data() {
            return {
                users: []
            }
        },
        async mounted() {
            this.get_users();
        },
        methods: {
            async get_users(){
                try {
                    const response = await axios.get("./queries/get_all_users.php");
                    if (response.data) {
                        //console.log(response.data);
                        this.users=response.data;

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
            onClikCreateNewUser(){
                this.$refs.ref_FormCreateNewUser.init(this);

                //отключить прокрутку страницы
                document.body.style.overflow = 'hidden';

                //сделать элемент модальным     
                document.getElementById("id_FormCreateNewUserID").style.display = "block";                

            },

            ChangeUser(in_user){
                this.$refs.ref_FormEditUser.init(this, in_user);

                //отключить прокрутку страницы
                document.body.style.overflow = 'hidden';

                //сделать элемент модальным     
                document.getElementById("id_FormEditUserID").style.display = "block";                

            }

        }
    });
    app2.mount('#main');
</script>
