import FormEditProfile from './Form_Edit_Profile.js';

export default {
    components: {
        FormEditProfile
    },      
    data() {
        return {
            user_name: 'Имя Пользователя'
        }
    },
    methods: {
        onClickMenuProfile(){
            //если сессия закончилась, то переходим на стрницу login.php
            this.$root.check_for_empty_session();

            const openProfileModal = () => {
                this.$refs.ref_FormEditProfile.init();
                document.body.style.overflow = 'hidden';
                const el = document.getElementById('id_FormEditProfile');
                if (el) {
                    el.style.display = 'block';
                }
            };

            // На мобильной версии модалка была внутри v-show меню — закрытие меню скрывало форму.
            // Сначала закрываем выпадающее меню, затем открываем модалку на следующем тике Vue.
            if (this.$root.isMobileMode && typeof this.$root.closeMobileMenus === 'function') {
                this.$root.closeMobileMenus();
                this.$nextTick(openProfileModal);
                return;
            }

            openProfileModal();
        },
        async refresh(){
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
            
            //this.$root.get_users()
            //this.$parent.get_users()
            //this.$app2.get_users();

            /*
          
            */
        },
        onClickExit() {
            if (this.$root.isMobileMode && typeof this.$root.closeMobileMenus === 'function') {
                this.$root.closeMobileMenus();
            }
        }
    },
    mounted() {
        this.refresh();
    },
    template: 
    `


    <div class='menu-bar'>
        <ul>
            <li>
                {{ user_name }}
                <ul>
                    <li @click="onClickMenuProfile()">Профиль</li>
                    <li @click="onClickExit"><a href='login.php'>Выход</a></li>
                </ul>
            </li>
        </ul>
    </div>  

    <Teleport to="body" :disabled="!$root.isMobileMode">
        <div id="id_FormEditProfile" class="modal">
            <div class="my_unheader2">
                <Form-Edit-Profile ref="ref_FormEditProfile"/>
            </div>
        </div>
    </Teleport>
    `
    }
