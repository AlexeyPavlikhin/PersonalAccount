export default {
    data() {
        return {
            menu_data: [
                {item_of_menu_class: "item_of_menu", menu_button_class: "menu_button", page_name: "lk.php",   div_text_class: "menu_button_text", div_text: "Статус услуг" },
                {item_of_menu_class: "item_of_menu", menu_button_class: "menu_button", page_name: "sales",    div_text_class: "menu_button_text", div_text: "Управление продажами"},
                {item_of_menu_class: "item_of_menu", menu_button_class: "menu_button", page_name: "uc",       div_text_class: "menu_button_text", div_text: "Управление пользователями" },
                {item_of_menu_class: "item_of_menu", menu_button_class: "menu_button", page_name: "mss.php",  div_text_class: "menu_button_text", div_text: "Управление статусом услуг" },
                {item_of_menu_class: "item_of_menu", menu_button_class: "menu_button", page_name: "courses",  div_text_class: "menu_button_text", div_text: "Учебные курсы" }
            ],
            users_permitions: []
        }
    },
    mounted() {
            this.init();
            //console.log("mounted NavigationMenu"); 
    },
    methods: {
        setActivMenuItem(in_current_item_of_menu){
            //console.log("Active:"+in_current_item_of_menu)
            //Меняем стили для активного пункта меняю
            for (const item_data of this.menu_data) {
                if (item_data.page_name==in_current_item_of_menu){
                    item_data.div_text_class = "menu_button_text_active";
                    item_data.menu_button_class = "menu_button_active";
                } else {
                    item_data.div_text_class = "menu_button_text";
                    item_data.menu_button_class = "menu_button";
                    
                }
            }
        },

        async init(){
            //определяем разрешения пользователя
            try {
                axios.get('./queries/get_current_user_permition.php', {
                    params: {
                    }
                })
                .then((response) => {
                    //console.log(response.data)
                    if (response.data) {
                        //обрабатываем ответ
                        //this.user_name=response.data;
                        //console.log(response.data);
                        this.users_permitions = response.data;
                        if (typeof this.$root.setMobileNavButtonVisible === 'function') {
                            this.$root.setMobileNavButtonVisible(this.users_permitions.length > 1);
                        }
                        this.$root.set_route_to_first_menu_item();
                        //this.$root.set_route_to_first_menu_item();
                        //console.log(this.users_permitions.length);
                        if (this.users_permitions.length > 1){
                            for (const item_of_user_permition of this.users_permitions) {
                                for (const item_menu_data of this.menu_data) {
                                    if (item_menu_data.page_name==item_of_user_permition.permition_name){
                                        item_menu_data.item_of_menu_class = "item_of_menu_permited";
                                    } 
                                }
                                //console.log(item_of_user_permition);
                            }
                        }
                        
                    } else {
                        // пустой ответ
                        if (typeof this.$root.setMobileNavButtonVisible === 'function') {
                            this.$root.setMobileNavButtonVisible(false);
                        }
                        console.log('Ответ от сервера пустой (data undefined/null)');
                    }
                });
            } catch (error) {
                // Обработка ошибки
                if (typeof this.$root.setMobileNavButtonVisible === 'function') {
                    this.$root.setMobileNavButtonVisible(false);
                }
                console.error('Ошибка при запросе:', error);
                if (error.response) {
                    console.error('Статус ошибки:', error.response.status);
                    console.error('Данные ошибки:', error.response.data);
                }
            }            
            
        },

        on_click(in_target){
            //если сессия закончилась, то переходим на стрницу login.php
            this.$root.check_for_empty_session();
            this.$root.$router.push('/'+in_target);
            if (this.$root.isMobileMode && typeof this.$root.closeMobileMenus === 'function') {
                this.$root.closeMobileMenus();
            }
            //console.log("click");
        },

        get_start_item_of_menu(){

            //console.log(this.users_permitions.length);
            //console.log(this.users_permitions[0].permition_name);
            if (this.users_permitions.length==0){
                return("/");
            } else {
                return(this.users_permitions[0].permition_name);

            }
            //console.log("111111111111");
        } 
    },
    template: 
    `
    <div class="container_inline_center2 margin-left-200">
        <div v-for="menu_item in menu_data"> 
        <!--    
        <div :class = 'menu_item.item_of_menu_class'><a :class='menu_item.menu_button_class' :href='menu_item.page_name'><div :class='menu_item.div_text_class'>{{menu_item.div_text}}</div></a></div>
        -->

        <div :class = 'menu_item.item_of_menu_class' @click="on_click(menu_item.page_name)">
            <div :class='menu_item.menu_button_class'>
                <div :class='menu_item.div_text_class'>{{menu_item.div_text}}</div>
            </div>
        </div>
        </div>
    </div>
    `
    }
