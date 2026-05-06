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
        <link href="./css/styles.css?v=<?=$ASSET_VER?>" rel="stylesheet">
        
        <!--link href="https://vjs.zencdn.net/8.23.4/video-js.css" rel="stylesheet" /-->

        <link href="./css/jost.css?v=<?=$ASSET_VER?>" rel="stylesheet">
        <script src="./js/axios.min.js?v=<?=$ASSET_VER?>"></script>

        <title>Личный кабинет: Главная страница</title>

        <link rel="icon" type="image/x-icon" href="./favicon.ico?v=<?=$ASSET_VER?>">
        <link rel="shortcut icon" href="./favicon.ico?v=<?=$ASSET_VER?>">

    </head> 
    <body>
        <div id='app'>
            <header class='my_header'>
                <div><img class="logo_image" src="./pictures/logo.png?v=<?=$ASSET_VER?>" alt="Лаборатория права Майи Саблиной"></div>
                <div v-if="!isMobileMode" class='my_header_polygon'></div>
                <div v-if="isMobileMode" class="mobile_header_controls">
                    <button
                        type="button"
                        class="mobile_header_button mobile_header_button_courses"
                        v-if="isMobileCoursesButtonVisible"
                        @click="toggleMobileCoursesSidenav"
                        :aria-expanded="isMobileCoursesSidenavVisible"
                        aria-label="Открыть меню курсов"
                    ></button>
                    <button
                        type="button"
                        class="mobile_header_button mobile_header_button_nav"
                        v-if="isMobileNavButtonVisible"
                        @click="toggleNavMenu"
                        :aria-expanded="isNavMenuOpen"
                        aria-label="Открыть навигационное меню"
                    ></button>
                    <button
                        type="button"
                        class="mobile_header_button mobile_header_button_profile"
                        @click="toggleProfileMenu"
                        :aria-expanded="isProfileMenuOpen"
                        aria-label="Открыть меню профиля"
                    ></button>
                </div>
            </header>

            <header v-if="!isMobileMode" class='my_header2' id='header_menu'>
                <div id="id_MenuProfileAndExit">
                    <Menu-Profile-And-Exit ref="ref_MenuProfileAndExit"/>
                </div> 
            </header>

            <div
                v-if="isMobileMode && (isProfileMenuOpen || isNavMenuOpen || isMobileCoursesSidenavVisible)"
                class="mobile_dropdown_backdrop"
                :class="{ 'mobile_dropdown_backdrop--courses-modal': isMobileCoursesSidenavVisible }"
                @click="closeMobileMenus"
            ></div>

            <div v-if="isMobileMode" class="mobile_header_dropdowns">
                <section v-show="isProfileMenuOpen" class="mobile_dropdown_panel mobile_dropdown_panel_profile">
                    <Menu-Profile-And-Exit ref="ref_MenuProfileAndExitMobile"/>
                </section>
                <section v-show="isNavMenuOpen" class="mobile_dropdown_panel mobile_dropdown_panel_nav">
                    <Navigation-Menu ref="ref_NavigationMenuMobile"></Navigation-Menu>
                </section>
            </div>

            <main id='main'>
                <!--input class="msll_small_button" type="button" value = "Изменить" @click="test_func"-->                
                <Navigation-Menu v-if="!isMobileMode" ref="ref_NavigationMenu"></Navigation-Menu>

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

<script src="./js/vue-spinner.min.js?v=<?=$ASSET_VER?>"></script>

<!--script src="https://unpkg.com/vue@3/dist/vue.global.js"></script-->
<!--script src="https://unpkg.com/vue-router@4/dist/vue-router.global.js"></script-->
<script src="./js/vue.global.js?v=<?=$ASSET_VER?>"></script>
<script src="./js/vue-router.global.js?v=<?=$ASSET_VER?>"></script>

<script>
  var PulseLoader = VueSpinner.PulseLoader;
</script>


<script type="module">

    import FormModalMessage from './components/Form_Modal_Message.js?v=<?=$ASSET_VER?>';
    import MenuProfileAndExit from './components/Menu_Profile_And_Exit.js?v=<?=$ASSET_VER?>';
    import NavigationMenu from './components/Navigation_Menu.js?v=<?=$ASSET_VER?>';    
    
    const { createApp } = Vue;
    //const { createRouter, createWebHistory, createWebHashHistory } = VueRouter;
    const { createRouter, createWebHashHistory } = VueRouter;
    //const { createRouter, createWebHistory } = VueRouter;

    import UC from './components/pg_uc.js?v=<?=$ASSET_VER?>';
    import SALES from './components/pg_sales.js?v=<?=$ASSET_VER?>';
    import EMPTY from './components/pg_empty.js?v=<?=$ASSET_VER?>';
    import COURSES from './components/pg_courses.js?v=<?=$ASSET_VER?>';
    

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

    const detectMobileMode = () => {
        const ua = navigator.userAgent || navigator.vendor || window.opera || '';
        const isMobileByUA = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile/i.test(ua.toLowerCase());
        const isMobileByViewport = window.matchMedia
            ? window.matchMedia('(max-width: 991px)').matches
            : window.innerWidth <= 991;
        return isMobileByUA || isMobileByViewport;
    };

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
                p_size: "20px",
                isMobileMode: detectMobileMode(),
                isMobileNavButtonVisible: false,
                isMobileCoursesButtonVisible: false,
                isMobileCoursesSidenavVisible: false,
                isProfileMenuOpen: false,
                isNavMenuOpen: false
            }
        },
        mounted() {
            //this.$router.push('/');
            //this.$root.$refs.ref_NavigationMenu.init();
            //console.log(this.$route.path);
            this.detectMobileModeByUA();
            window.addEventListener('resize', this.onViewportChange);
            window.addEventListener('orientationchange', this.onViewportChange);
        },
        beforeUnmount() {
            window.removeEventListener('resize', this.onViewportChange);
            window.removeEventListener('orientationchange', this.onViewportChange);
            document.documentElement.classList.remove('mobile-scroll-lock');
            document.body.classList.remove('mobile-scroll-lock');
        },
        computed: {
            mobileBackdropActive() {
                return this.isMobileMode
                    && (this.isProfileMenuOpen || this.isNavMenuOpen || this.isMobileCoursesSidenavVisible);
            },
        },
        watch: {
            mobileBackdropActive: {
                immediate: true,
                handler(val) {
                    document.documentElement.classList.toggle('mobile-scroll-lock', Boolean(val));
                    document.body.classList.toggle('mobile-scroll-lock', Boolean(val));
                },
            },
        },
        methods: {
            detectMobileModeByUA() {
                this.isMobileMode = detectMobileMode();
            },
            onViewportChange() {
                const wasMobileMode = this.isMobileMode;
                this.detectMobileModeByUA();
                if (wasMobileMode !== this.isMobileMode || !this.isMobileMode) {
                    this.closeMobileMenus();
                }
            },
            closeMobileMenus() {
                this.isProfileMenuOpen = false;
                this.isNavMenuOpen = false;
                this.isMobileCoursesSidenavVisible = false;
            },
            toggleProfileMenu() {
                this.isProfileMenuOpen = !this.isProfileMenuOpen;
                if (this.isProfileMenuOpen) {
                    this.isNavMenuOpen = false;
                    this.isMobileCoursesSidenavVisible = false;
                }
            },
            toggleNavMenu() {
                if (!this.isMobileNavButtonVisible) {
                    return;
                }
                this.isNavMenuOpen = !this.isNavMenuOpen;
                if (this.isNavMenuOpen) {
                    this.isProfileMenuOpen = false;
                    this.isMobileCoursesSidenavVisible = false;
                }
            },
            setMobileNavButtonVisible(isVisible) {
                this.isMobileNavButtonVisible = Boolean(isVisible);
                if (!this.isMobileNavButtonVisible) {
                    this.isNavMenuOpen = false;
                }
            },
            setMobileCoursesButtonVisible(isVisible) {
                this.isMobileCoursesButtonVisible = Boolean(isVisible);
                if (!this.isMobileCoursesButtonVisible) {
                    this.isMobileCoursesSidenavVisible = false;
                }
            },
            setMobileCoursesSidenavVisible(isVisible) {
                this.isMobileCoursesSidenavVisible = Boolean(isVisible);
            },
            toggleMobileCoursesSidenav() {
                if (!this.isMobileCoursesButtonVisible) {
                    return;
                }
                this.isMobileCoursesSidenavVisible = !this.isMobileCoursesSidenavVisible;
                if (this.isMobileCoursesSidenavVisible) {
                    this.isProfileMenuOpen = false;
                    this.isNavMenuOpen = false;
                }
            },
            getNavigationMenuRef() {
                if (this.isMobileMode) {
                    return this.$refs.ref_NavigationMenuMobile;
                }
                return this.$refs.ref_NavigationMenu;
            },
            set_route_to_first_menu_item(){
                const navigationMenuRef = this.getNavigationMenuRef();
                if (!navigationMenuRef || typeof navigationMenuRef.get_start_item_of_menu !== 'function') {
                    return;
                }
                this.$router.push(navigationMenuRef.get_start_item_of_menu());
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

