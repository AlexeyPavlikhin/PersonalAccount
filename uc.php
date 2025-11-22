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

                    <div id="id_FormCreateNewUser" class="modal">
                        <Form-Create-New-User ref="ref_FormCreateNewUser"/>
                    </div>  

                    <div id="id_FormUpdateUser" class="modal">
                        <Form-Edit-User ref="ref_FormEditUser"/>
                    </div>  

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
    import FormCreateNewUser from './components/Form_Create_New_User.js';
    import FormEditUser from './components/Form_Edit_User.js';
    //import FormEditProfile from './components/Form_Edit_Profile.js';
    import FormModalMessage from './components/Form_Modal_Message.js';
    import MenuProfileAndExit from './components/Menu_Profile_And_Exit.js';
    
    

    import { createApp } from 'vue';

    const app = createApp({
        components: {
            FormCreateNewUser,
            FormEditUser,
            PulseLoader,
            FormModalMessage,
            MenuProfileAndExit
        },
        data() {
            return {
                users: [],
                p_color: "#bd162b",
                p_size: "20px"

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
                document.getElementById("id_FormCreateNewUser").style.display = "block";    

            },

            ChangeUser(in_user){
                //console.log(in_user)

                this.$refs.ref_FormEditUser.init(this, in_user);

                //отключить прокрутку страницы
                document.body.style.overflow = 'hidden';

                //сделать элемент модальным     
                document.getElementById("id_FormUpdateUser").style.display = "block";                

            }

        }
    });
    app.mount('#app');
    
</script>
