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
            //console.log(this.user_name)

            this.$refs.ref_FormEditProfile.init(this);

            //отключить прокрутку страницы
            document.body.style.overflow = 'hidden';

            //сделать элемент модальным     
            document.getElementById("id_FormEditProfile").style.display = "block";                

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
                    <li><a href='login.php'>Выход</a></li>
                </ul>
            </li>
        </ul>
    </div>  

    <div id="id_FormEditProfile" class="modal">
        <div class="my_unheader2">
            <Form-Edit-Profile ref="ref_FormEditProfile"/>
        </div>
    </div>          
    `
    }
