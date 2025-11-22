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
                    is_user_user_group_ready: false,

                    is_generate_password: false


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

                    this.WarningMessage = [];
                  
                    this.is_user_username_ready = false;
                    this.is_user_email_entered = false;
                    this.is_user_email_formate_correct = false;
                    this.is_user_email_ready = false;
                    this.is_user_user_group_ready = false;

                    this.is_generate_password = false

                    this.onChangeUserName(this.user_username);
                    this.onChangeUserEmail(this.user_email, this.user_login);
                    this.onChangeUserGroup(this.user_user_group);

                },                

                CloseForm(){
                    document.getElementById("id_FormUpdateUser").style.display = "none";
                    document.body.style.overflow = '';
                },

                UpdateUser(){
                    let this2 = this;
                    
                    //запускаем спиннер    
                    document.getElementById("id_spinner_panel").style.display = "block"; 

                    axios.post("./queries/update_user.php", {user_login: this.user_login, user_username: this.user_username, user_email: this.user_email, user_user_group: this.user_user_group})
                    .then(function (response) {
                        //console.log(response.data);
                        //console.log(this2.is_ValueNotEqual());
                        if ((response.data == "1")||(response.data == "0" && this2.is_ValueNotEqual() == false)){

                            //теперь делаем генерацию пароля
                            if (this2.is_generate_password){
                                
                                axios.post("./queries/generate_password.php", {user_login: this2.user_login})
                                .then(function (response1) {      
                                    //останавливаем спиннер    
                                    document.getElementById("id_spinner_panel").style.display = "none";

                                    //console.log(response1.data)     
                                    //console.log(response1.data.new_pass)

                                    if (response1.data.send_status == 0){
                                        this2.ref_to_parent.$refs.ref_FormModalMessage.init(this, 
                                            "Установлен новый пароль для пользователя " + "<br>" +
                                            "login: " + this2.user_login + "<br>" +
                                            "Пароль: " + response1.data.new_pass +"<br>"+
                                            
                                            "При попытке отправки письма с новым паролем на адрес "+ this2.user_email +" произошла ошибка и письмо не отправилось, поэтому нужно этот пароль как-то сообщить пользователю.<br>" + 
                                            "<br>" +
                                            "Информация об ошибке: <br>"+ response1.data.send_error);
                                        //показываем сообщение    
                                        document.getElementById("id_FormModalMessage").style.display = "block"; 
                                    } else {
                                        this2.ref_to_parent.$refs.ref_FormModalMessage.init(this, 
                                            "Установлен новый пароль для пользователя " + "<br>" +
                                            "Информация о пароле отправлна на адрес электронной почты: "+ this2.user_email + ".");
                                        //показываем сообщение    
                                        document.getElementById("id_FormModalMessage").style.display = "block"; 
                                    }

                                    // обноляем родительскую форму
                                    this2.ref_to_parent.get_users();

                                    //закрываем модальное окно
                                    this2.CloseForm();

                                })
                                .catch(function (error1) {
                                    //останавливаем спиннер    
                                    document.getElementById("id_spinner_panel").style.display = "none";

                                    this2.ref_to_parent.$refs.ref_FormModalMessage.init(this, "Что-то пошло не так при генерации пароля. Пароль не создан <br>" + error1);
                                    //показываем сообщение    
                                    document.getElementById("id_FormModalMessage").style.display = "block";           
                                    
                                    console.log(error1)                 

                                    // обноляем родительскую форму
                                    this2.ref_to_parent.get_users();

                                    //закрываем модальное окно
                                    this2.CloseForm();

                                });
                            } else {
                                //останавливаем спиннер    
                                document.getElementById("id_spinner_panel").style.display = "none";

                                // обноляем родительскую форму
                                this2.ref_to_parent.get_users();

                                //закрываем модальное окно
                                this2.CloseForm();
                            }




                        } else {
                            //останавливаем спиннер    
                            document.getElementById("id_spinner_panel").style.display = "none";

                            this2.ref_to_parent.$refs.ref_FormModalMessage.init(this, "Ошибка: Ожидалось, что будет создана 1 запись, но что-то пошло не так. <br>Ответ: <br>" + response.data);
                            //показываем сообщение    
                            document.getElementById("id_FormModalMessage").style.display = "block";                            

                            console.error("Ошибка: Ожидалось, что будет создана 1 запись, но что-то пошло не так. Ответ: " + response.data);
                            
                            // обноляем родительскую форму
                            this2.ref_to_parent.get_users();

                            //закрываем модальное окно
                            this2.CloseForm();
                        }
                    })
                    .catch(function (error) {
                        //останавливаем спиннер    
                        document.getElementById("id_spinner_panel").style.display = "none";

                        this2.ref_to_parent.$refs.ref_FormModalMessage.init(this, "Что-то пошло не так при обновлении пользователя. Пользователь не обновлён.<br>" + error);
                        //показываем сообщение    
                        document.getElementById("id_FormModalMessage").style.display = "block";                            


                        console.error(error);

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
                    console.log("=================================");
                    console.log("is_user_username_ready: " + this.is_user_username_ready);
                    console.log("is_user_email_entered: " + this.is_user_email_entered ); 
                    console.log("is_user_email_formate_correct: " + this.is_user_email_formate_correct );
                    console.log("is_user_email_no_dublicate: " + this.is_user_email_no_dublicate );
                    console.log("is_user_user_group_ready: " + this.is_user_user_group_ready);
                    console.log("is_ValueNotEqual: " + this.is_ValueNotEqual());
                    console.log("is_generate_password: " + this.is_generate_password);
                                        
                    if (this.is_generate_password){
                        console.log("is_generate_password: bool");
                    }

                    if (this.is_generate_password=="true"){
                        console.log("is_generate_password: string");
                    }
                    
                    */

                    if ((this.is_user_username_ready && 
                        this.is_user_email_entered && 
                        this.is_user_email_formate_correct &&
                        this.is_user_email_no_dublicate &&
                        this.is_user_user_group_ready &&
                        this.is_ValueNotEqual()
                       )||(this.is_generate_password)){
                        document.getElementById("buttonUpdateUser").disabled = false;
                        //console.log("disabled = false");
                    }else{
                        document.getElementById("buttonUpdateUser").disabled = true;
                        //console.log("disabled = true");
                    }

                    this.WarningMessage = [];
                    
                    if(!this.is_user_email_no_dublicate && this.user_email.length > 0){
                        this.WarningMessage.push("Такой E-mail уже зарегистрирован в системе.");
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

                onClickUpdateUser(){
                    this.UpdateUser();
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

                onChangeSignGeneratePassword(){ 
                    this.checkForReady();
                }


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
                <div class="width95">
                    <div class="container_inline2">
                        <input class="checkbox-height25" type="checkbox" v-model="is_generate_password" @change="onChangeSignGeneratePassword"/>
                        <label class="label-align-left">Сменить пароль</label>
                    </div>
                </div>
            </div>

            <input class="msll_middle_button" type="button" value = "Обновить" @click="onClickUpdateUser()" id="buttonUpdateUser" disabled>
            <input class="msll_middle_button" type="button" value = "Отменить" @click="onClickCancel()">
            
            <div class="container_left" v-for="item in WarningMessage">
                <div class="error">{{item}}</div>
            </div>
        </div>
        <div class="modal-footer">
        </div>
    </div>      
    `
    }
