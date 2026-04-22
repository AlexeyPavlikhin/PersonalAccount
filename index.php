<?php
    //error_reporting(0);
    ob_start();
    session_start();
    include('config.php');

    if(!isset($_SESSION['current_user_id'])){
        header('Location: login.php');
        exit;
    } else {
        if (($_SESSION['current_user_group'] != 'client' ) && ($_SESSION['current_user_group'] != 'operator' )) {
            header('Location: login.php');
            exit;
        }
    }     
       
?>
<html>
    <head> 
        <link href="./css/styles.css?v=1.0.1" rel="stylesheet">
        
        <!--link href="https://vjs.zencdn.net/8.23.4/video-js.css" rel="stylesheet" /-->

        <link href="./css/jost.css" rel="stylesheet">
        <script src="./js/axios.min.js"></script>

        <title>Личный кабинет: Главная страница</title>

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

            <main id='main'>
                <!--input class="msll_small_button" type="button" value = "Изменить" @click="test_func"-->                
                <Navigation-Menu ref="ref_NavigationMenu"></Navigation-Menu>

                <router-view v-slot="{ Component }">
                    <component :is="Component" ref="mainContent" />
                </router-view>          

                <div id="id_spinner_panel" class="spinner">
                    <pulse-loader :color="p_color" :size="p_size"></pulse-loader>
                </div>
                <div id="id_FormModalMessage" class="modal">
                    <Form-Modal-Message ref="ref_FormModalMessage"/>
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


<!--script type="importmap">{
    "imports": {
      "vue": "./js/vue3.esm-browser.js"
    }
  }
</script-->

<script src="./js/vue-spinner.min.js"></script>

<!--script src="https://unpkg.com/vue@3/dist/vue.global.js"></script-->
<!--script src="https://unpkg.com/vue-router@4/dist/vue-router.global.js"></script-->
<script src="./js/vue.global.js"></script>
<script src="./js/vue-router.global.js"></script>

<script>
  var PulseLoader = VueSpinner.PulseLoader;
</script>


<script type="module">

    import FormModalMessage from './components/Form_Modal_Message.js';
    import MenuProfileAndExit from './components/Menu_Profile_And_Exit.js';
    import NavigationMenu from './components/Navigation_Menu.js';    
    
    const { createApp } = Vue;
    //const { createRouter, createWebHistory, createWebHashHistory } = VueRouter;
    const { createRouter, createWebHashHistory } = VueRouter;
    //const { createRouter, createWebHistory } = VueRouter;

    import UC from './components/pg_uc.js';
    import SALES from './components/pg_sales.js';
    import EMPTY from './components/pg_empty.js';
    import COURSES from './components/pg_courses.js';
    

    // Define your routes
    const routes = [
        { path: '/', component: EMPTY },
        { path: '/uc', component: UC },
        { path: '/sales', component: SALES },
        { path: '/courses', component: COURSES }/*,
        { path: '/login', path: "/login.php" }*/
    ];

    // Create the router instance
    const router = createRouter({
        history: createWebHashHistory(),
        //history: createWebHistory(),
        routes
    });

    const app = createApp({
        components: {
            PulseLoader,
            FormModalMessage,
            MenuProfileAndExit,
            NavigationMenu
        },
        data() {
            return {
                p_color: "#bd162b",
                p_size: "20px"
            }
        },
        mounted() {
            //this.$router.push('/');
            //this.$root.$refs.ref_NavigationMenu.init();
            //console.log(this.$route.path);
            
        },
        methods: {
            set_route_to_first_menu_item(){
                this.$router.push(this.$refs.ref_NavigationMenu.get_start_item_of_menu());
                //console.log(this.$refs.ref_NavigationMenu.get_start_item_of_menu);
                //console.log(this.$refs.ref_NavigationMenu.get_start_item_of_menu()[1].page_name);
/*                
                if (this.$refs.ref_NavigationMenu.get_start_item_of_menu().lenght==0){
                    this.$router.push('/sales');

                } else {
                    this.$router.push('/sales');
                    //this.$router.push(this.$refs.ref_NavigationMenu.get_start_item_of_menu()[1].page_name);
                }
*/
            },
        check_for_permition_route(in_route_name){

            //определяем разрешения пользователя
            try {
                var this2 = this;
                axios.get('./queries/get_current_user_permition.php', {
                    params: {
                    }
                })
                .then((response) => {
                    
                    var is_premited_route = false;
                    if (response.data) {
                        //обрабатываем ответ
                        //this.$root.set_route_to_first_menu_item();
                        //console.log(this.users_permitions);
                        for (const item_of_user_permition of response.data) {
                            if (item_of_user_permition.permition_name==in_route_name){
                                is_premited_route = true;
                            } 
                            //console.log(item_of_user_permition);
                        }
                        
                        //console.log(is_premited_route);
                        if (!is_premited_route){
                            this2.set_route_to_first_menu_item();
                        }
                    } else {
                        // пустой ответ
                        console.log('Ответ от сервера пустой (data undefined/null)');
                    }
                });
            } catch (error) {
                // Обработка ошибки
                console.error('Ошибка при запросе:', error);
                if (error.response) {
                    console.error('Статус ошибки:', error.response.status);
                    console.error('Данные ошибки:', error.response.data);
                }
            }            

            
        },
        check_for_empty_session(){
                axios.post("./queries/get_current_user_name.php")
                .then(function (response) {
                    //console.log(response.data);
                    if (response.data=="\r\n"){
                        //alert("Пусто");
                        window.location.href = '/login.php';
                    } else {
                        //alert("Не пусто |"+response.data+"|");
                    }
                })
                .catch(function (error) {
                    //alert(error);
                    //console.log(error);
                    window.location.href = '/login.php';
                });
            },               
                
        }
    });
    app.use(router); // Use the router
    app.mount('#app');

</script>

