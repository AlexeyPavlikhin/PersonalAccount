import FormCreateNewUser from './Form_Create_New_User.js';
import FormEditUser from './Form_Edit_User.js';
import NavigationMenu from './Navigation_Menu.js';    

export default {
        props: ['message'],
        components: {
            FormCreateNewUser,
            FormEditUser,
//            PulseLoader,
//            FormModalMessage,
//            MenuProfileAndExit,
           NavigationMenu
        },
        data() {
            return {
                users: [],
                p_color: "#bd162b",
                p_size: "20px",
                current_route_name: "uc"

            }
        },
        async mounted() {
            this.$root.check_for_permition_route(this.current_route_name);
            const navigationMenuRef = typeof this.$root.getNavigationMenuRef === 'function'
                ? this.$root.getNavigationMenuRef()
                : this.$root.$refs.ref_NavigationMenu;
            if (navigationMenuRef && typeof navigationMenuRef.setActivMenuItem === 'function') {
                navigationMenuRef.setActivMenuItem(this.current_route_name);
            }
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
                this.$refs.ref_FormCreateNewUser.init();

                //отключить прокрутку страницы
                document.body.style.overflow = 'hidden';

                //сделать элемент модальным     
                document.getElementById("id_FormCreateNewUser").style.display = "block";    

            },

            ChangeUser(in_user){
                //console.log(in_user)

                this.$refs.ref_FormEditUser.init(in_user);

                //отключить прокрутку страницы
                document.body.style.overflow = 'hidden';

                //сделать элемент модальным     
                document.getElementById("id_FormUpdateUser").style.display = "block";                

            }

        },
        template: 
        `
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
            <br/><br/><br/>
            <div id="id_FormCreateNewUser" class="modal">
                <Form-Create-New-User ref="ref_FormCreateNewUser"/>
            </div>  

            <div id="id_FormUpdateUser" class="modal">
                <Form-Edit-User ref="ref_FormEditUser"/>
            </div>  

            <!--div id="id_spinner_panel" class="spinner">
                <pulse-loader :color="p_color" :size="p_size"></pulse-loader>
            </div-->

            <!--div id="id_FormModalMessage" class="modal">
                <Form-Modal-Message ref="ref_FormModalMessage"/>
            </div-->  

        </div>  
        `        
}
