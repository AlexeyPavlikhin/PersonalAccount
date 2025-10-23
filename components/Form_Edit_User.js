export default {
    data() {
        return {
                    user_login: "",
                    user_username: "",
                    user_email: "",
                    user_user_group: "",
                                        
                    user_login_save: "",
                    user_username_save: "",
                    user_email_save: "",
                    user_user_group_save: "",

                    ref_to_parent: "",
                    WarningMessage: "",
/*                    
                    is_user_login_entered: false,
                    is_user_login_no_dublicate: false,
                    is_user_login_formate_correct: false,
*/                    
                    is_user_username_ready: false,
                    is_user_email_entered: false,
                    is_user_email_formate_correct: false,
                    is_user_email_ready: false,
                    is_user_user_group_ready: false


        }
    },
    methods: {
                init(in_ref_to_parent, in_user){
                    this.ref_to_parent = in_ref_to_parent;

                    this.user_login = in_user.login;
                    this.user_username = in_user.username;
                    this.user_email = in_user.email;
                    this.user_user_group = in_user.user_group;

                    // создаём копии переменных (для последующего отслеживания изменений)
                    this.user_username_save = this.user_username.repeat(1);
                    this.user_email_save = this.user_email.repeat(1);
                    this.user_user_group_save = this.user_user_group.repeat(1);

                    this.WarningMessage = "";
                  
                    this.is_user_username_ready = false;
                    this.is_user_email_entered = false;
                    this.is_user_email_formate_correct = false;
                    this.is_user_email_ready = false;
                    this.is_user_user_group_ready = false;

                    this.onChangeUserName(this.user_username);
                    this.onChangeUserEmail(this.user_email, this.user_login);
                    this.onChangeUserGroup(this.user_user_group);

                },                

                CloseForm(){
                    document.getElementById("id_FormEditUserID").style.display = "none";
                    document.body.style.overflow = '';
                },

                CreateNewUser(){
                    let this2 = this;
                    
                    axios.post("./queries/update_user.php", {user_login: this.user_login, user_username: this.user_username, user_email: this.user_email, user_user_group: this.user_user_group})
                    .then(function (response) {
                        //console.log(response.data);
                        if (response.data == "1"){

                            // обноляем родительскую форму
                            this2.ref_to_parent.get_users();

                            //закрываем модальное окно
                            this2.CloseForm();

                        } else {
                            console.error("Ошибка: Ожидалось, что будет создана 1 запись, но что-то пошло не так. Ответ: " + response.data);
                            alert("Ошибка: Ожидалось, что будет создана 1 запись, но что-то пошло не так. Ответ: " + response.data);
                            
                            // обноляем родительскую форму
                            this2.ref_to_parent.get_users();

                            //закрываем модальное окно
                            this2.CloseForm();
                        }
                    })
                    .catch(function (error) {
                        console.error(error);
                        alert(error);

                        // обноляем родительскую форму
                        this2.ref_to_parent.get_users();

                        //закрываем модальное окно
                        this2.CloseForm();

                    });
                },

                is_ValueNotEqual(){
                    if (
                        (this.user_username_save == this.user_username) && 
                        (this.user_email_save == this.user_email) &&
                        (this.user_user_group_save == this.user_user_group)){
                        return false;
                    } else {
                        return true;
                    }

                },

                checkForReady(){
                    /*
                    console.log("is_user_username_ready: " + this.is_user_username_ready);
                    console.log("is_user_email_entered: " + this.is_user_email_entered ); 
                    console.log("is_user_email_formate_correct: " + this.is_user_email_formate_correct );
                    console.log("is_user_email_no_dublicate: " + this.is_user_email_no_dublicate );
                    console.log("is_user_user_group_ready: " + this.is_user_user_group_ready);
                    console.log("is_ValueNotEqual: " + this.is_ValueNotEqual());
                    */

                    if (this.is_user_username_ready && 
                        this.is_user_email_entered && 
                        this.is_user_email_formate_correct &&
                        this.is_user_email_no_dublicate &&
                        this.is_user_user_group_ready &&
                        this.is_ValueNotEqual()
                       ){
                        document.getElementById("buttonUpdateUser").disabled = false;
                        //console.log("disabled = false");
                    }else{
                        document.getElementById("buttonUpdateUser").disabled = true;
                        //console.log("disabled = true");
                    }

                    this.WarningMessage = "";
                    
                    if(!this.is_user_email_no_dublicate && this.user_email.length > 0){
                        this.WarningMessage = "Такой E-mail уже зарегистрирован в системе. "
                    }
                },
/*
                checkForDublicateLogin(in_user_login){
                    try {
                        axios.get('./queries/get_count_users_by_login.php', {
                            params: {
                                user_login: in_user_login
                            }
                        })
                        .then((response1) => {
                            //console.log(response.data)
                            if (response1.data) {
                                if (response1.data[0].count == '0'){
                                    this.is_user_login_no_dublicate = true;
                                } else {
                                    this.is_user_login_no_dublicate = false;
                                }
                                this.checkForReady();
                            } else {
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
*/
                checkForDublicatEmail(in_user_email, in_user_login){
                    try {
                        axios.get('./queries/get_count_other_users_by_email.php', {
                            params: {
                                user_login: in_user_login,
                                user_email: in_user_email
                            }
                        })
                        .then((response2) => {
                            //console.log(response2.data)
                            if (response2.data) {
                                if (response2.data[0].count == '0'){
                                    this.is_user_email_no_dublicate = true;
                                } else {
                                    this.is_user_email_no_dublicate = false;
                                }
                                this.checkForReady();
                            } else {
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

                onClickCloseForm(){
                    this.CloseForm();
                },

                onClickCreateNewUser(){
                    this.CreateNewUser();
                },

                onClickCancel(){
                    this.CloseForm();
                },

/*                
                onChangeUserLogin(in_user_login){
                                     
                    console.log("start onChangeUserLogin")  
                    
                    if(in_user_login.length > 0){
                        this.is_user_login_entered = true;
                        
                        //проверяем на дубликаты
                        this.checkForDublicateLogin(in_user_login);

                    } else {
                        this.is_user_login_entered = false;
                    }   

                    //проверяем формат
                    let re = /^[a-zA-Z0-9]+$/;
                    if (re.test(String(in_user_login).toLowerCase())){
                        this.is_user_login_formate_correct = true;
                    } else {
                        this.is_user_login_formate_correct = false;
                    }
                    
                    //финальная проверка всех параметров
                    this.checkForReady();

                },
*/
                onChangeUserName(in_user_username){
                    
                    if(in_user_username.length > 0){
                        this.is_user_username_ready = true;
                    } else {
                        this.is_user_username_ready = false;
                    }
                    this.checkForReady();
                },

                onChangeUserEmail(in_user_email, in_user_login){
                    //console.log("start onChangeUserEmail")  

                    if(in_user_email.length > 0){
                        this.is_user_email_entered = true;

                        //проверяем на дубликаты
                        this.checkForDublicatEmail(in_user_email, in_user_login);

                    } else {
                        this.is_user_email_entered = false;
                    }

                    //проверяем формат
                    let re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                    if (re.test(String(in_user_email).toLowerCase())){
                        this.is_user_email_formate_correct = true;
                    } else {
                        this.is_user_email_formate_correct = false;
                    }

                    
                    //финальная проверка всех параметров
                    this.checkForReady();                 
                },

                onChangeUserGroup(in_user_user_group){

                    if(in_user_user_group.length > 0){
                        this.is_user_user_group_ready = true;
                    } else {
                        this.is_user_user_group_ready = false;
                    }
                    this.checkForReady();                 
                },


    },
    template: 
    `
    <!-- Modal content -->
    <div class="modal-content-40">
        <div class="modal-header">
            <span class="close" @click="onClickCloseForm()">&times;</span>
            <h2>Редактирование данных пользователя</h2>
         
        </div>
        <div class="modal-body">

        
            <div class="form-element">
                <label>Login</label>
                <input class="msll_filter" type="text" v-model="user_login" placeholder="Введите login пользователя (буквы латинского алфавита и цифры без пробелов)" @input="onChangeUserLogin(user_login)" disabled/>
            </div>
            <div class="form-element">
                <label>Имя пользователя</label>
                <input class="msll_filter" type="text" v-model="user_username" placeholder="Введите ФИО пользователя" @input="onChangeUserName(user_username)"/>
            </div>
            <div class="form-element">
                <label>E-mail</label>
                <input class="msll_filter" type="text" v-model="user_email" placeholder="Введите e-mail пользователя (на этот email будет отправлен пароль)" @input="onChangeUserEmail(user_email, user_login)"/>
            </div>
            <div class="form-element">
                <label>Группа</label>
                <select class="msll_filter" v-model="user_user_group" @change="onChangeUserGroup(user_user_group)">
                    <option disabled value="">Выберите группу</option>
                    <option value="client">client</option>
                    <option value="operator">operator</option>
                </select>

            </div>
            <div class="form-element">
                <div class="error" style="height: 50px"><h2>{{WarningMessage}}</h2></div>
            </div>
            
            <input class="msll_middle_button" type="button" value = "Обновить" @click="onClickCreateNewUser()" id="buttonUpdateUser" disabled>
            <input class="msll_middle_button" type="button" value = "Отменить" @click="onClickCancel()">

        </div>
        <div class="modal-footer">
        </div>
    </div>      
    `
    }
