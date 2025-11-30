export default {
    emits: ["client_created"],
    data() {
        return {
                    new_client_LastName: "",
                    new_client_FirstName: "",
                    new_client_Patronymic: "",
                    new_client_Email: "",
                    new_client_Phone: "",
                    new_client_Telegram: "",
                    new_client_id: "",
                    list_of_clients_all: "",
                    list_of_selected_clients: [],
        }
    },
    methods: {
                // When the user clicks on <span> (x), close the modal
                onClickCloseForm(){
                    document.getElementById("form_Create_New_Client").style.display = "none";
                    document.body.style.overflow = '';
                    this.new_client_LastName = "";
                    this.new_client_FirstName = "";
                    this.new_client_Patronymic = "";
                    this.new_client_Email = "",
                    this.new_client_Phone = "",
                    this.new_client_Telegram = "",                    
                    this.new_client_id = "";
                    this.list_of_clients_all = [],
                    this.list_of_selected_clients = []

                },

                async activate(){
                    //this.list_of_clients_all;
                    try {
                        //const response = await axios.get('./queries/get_default_list_of_clients_limit.php');
                        const response = await axios.get('./queries/get_default_list_of_clients.php');
                        if (response.data) {
                            //обрабатываем ответ
                            this.list_of_clients_all=response.data;
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
                    
                    this.new_client_LastName = "";
                    this.new_client_FirstName = "";
                    this.new_client_Patronymic = "";
                    this.new_client_Email = "",
                    this.new_client_Phone - "",
                    this.new_client_Telegram = "",                    
                    this.new_client_id = "";     
                    document.getElementById("dublicate_table").style.display = "none";               
                },
               
                async onClickApplyForm(){
                   
                    const self = this;

                    //Делаем insert записи о новом клиенте
                    axios.post("./queries/create_new_client.php", {client_LastName: this.new_client_LastName, client_FirstName: this.new_client_FirstName, client_Patronymic: this.new_client_Patronymic})
                    .then(function (response) {
                        self.new_client_id = response.data; 

                        //теперь создаём email
                        if (self.new_client_Email.length >0){
                            axios.post("./queries/add_client_email.php", {email: self.new_client_Email, client_id: self.new_client_id})
                            .then(function (response2) {
                                //console.log(response.data);
                                if (response2.data=="1"){
                                    //this.is_resp_success = true;
                                    //is_resp_success1 = true;
                                    return true;
                                } else {
                                    alert(response2.data);
                                }
                            })
                            .catch(function (error2) {
                                alert(error2);
                                console.log(error2);
                            });
                        }

                        //теперь создаём телефон
                        if (self.new_client_Phone.length >0){
                            axios.post("./queries/add_client_phone.php", {phone: self.to_clear_number(self.new_client_Phone), client_id: self.new_client_id})
                            .then(function (response3) {
                                //console.log(response.data);
                                if (response3.data=="1"){
                                    //this.is_resp_success = true;
                                    //is_resp_success1 = true;
                                    return true;
                                } else {
                                    alert(response3.data);
                                }
                            })
                            .catch(function (error3) {
                                alert(error3);
                                console.log(error3);
                            });
                        }

                        //теперь создаём телеграм
                        if (self.new_client_Telegram.length >0){
                            axios.post("./queries/add_client_telegram.php", {telegram: self.new_client_Telegram, client_id: self.new_client_id})
                            .then(function (response4) {
                                //console.log(response.data);
                                if (response4.data=="1"){
                                    //this.is_resp_success = true;
                                    //is_resp_success1 = true;
                                    return true;
                                } else {
                                    alert(response4.data);
                                }
                            })
                            .catch(function (error4) {
                                alert(error4);
                                console.log(error4);
                            });
                        }
                        
                        self.$emit('client_created', response.data);
                        self.onClickCloseForm();
                    })
                    .catch(function (error) {
                        alert(error);
                        console.log(error);
                    });
                },

                onClikUseThisClient(inClientID){
                    //console.log(inClientID);
                    this.$emit('client_created', inClientID);
                    this.onClickCloseForm();  
                    document.body.style.overflow = 'hidden';                  
                },

                formate_phone(in_phone){
                    let ret;
                    let in_phone2;
                    in_phone2 = in_phone;

                    ret="-"+in_phone.slice(-2);
                    in_phone=in_phone.slice(0,-2);

                    ret="-"+in_phone.slice(-2)+ret;
                    in_phone=in_phone.slice(0,-2);

                    ret=") "+in_phone.slice(-3)+ret;
                    in_phone=in_phone.slice(0,-3);

                    ret=" ("+in_phone.slice(-3)+ret;
                    in_phone=in_phone.slice(0,-3);                

                    ret="+"+in_phone+ret;
                    
                    return ret;
                },

                formate_phone2(in_phone){
                    if (!in_phone){
                        in_phone="";
                    }

                    let ret;
                    let v_count = 0;
                    
                    in_phone.split('').forEach((item) => {
                        v_count++
                        if (v_count==1){
                            //console.log(v_count);
                            if(item == "8"){
                                ret="+7";
                            }else if(item == "0"){
                                ret="";
                            } else {
                                ret="+"+item;
                            }
                        }

                        if (v_count==2){
                            //console.log(v_count);
                            ret=ret + " (" + item;
                        }

                        if (v_count==3){
                            //console.log(v_count);
                            ret=ret + item;
                        }

                        if (v_count==4){
                            //console.log(v_count);
                            ret=ret + item;
                        }

                        if (v_count==5){
                            //console.log(v_count);
                            ret=ret + ") "+ item;
                        }

                        if (v_count==6){
                            //console.log(v_count);
                            ret=ret + item;
                        }
                        if (v_count==7){
                            //console.log(v_count);
                            ret=ret + item;
                        }
                        if (v_count==8){
                            //console.log(v_count);
                            ret=ret + "-" + item;
                        }
                        if (v_count==9){
                            //console.log(v_count);
                            ret=ret + item;
                        }
                        if (v_count==10){
                            //console.log(v_count);
                            ret=ret + "-" + item;
                        }
                        if (v_count==11){
                            //console.log(v_count);
                            ret=ret + item;
                        }

                        })

                        return ret;
                },

                to_clear_number(in_str){
                    let v_new_value = "";                    
                    if (in_str){
                        if (in_str!=""){
                            in_str.split('').forEach((item) => {
                                if (Number.parseInt(item) || item=="0"){
                                    v_new_value=v_new_value + item
                                } 
                            })
                            
                        }
                    }
                    return v_new_value;
                },

                onChangePhone(event){
                    //console.log(event.target.value);
                    //event.target.value = "qqq";
                    //this.new_client_Phone = "qqq"
                    
                    this.new_client_Phone=this.formate_phone2(this.to_clear_number(this.new_client_Phone));
                    this.onChangeKeyData();

                },

                onChangeKeyData(){

                    //console.log(this.list_of_clients_all)
                    this.list_of_selected_clients = [];
                    let is_searched = false;

                    this.list_of_clients_all.forEach((item) => {
                        // ищем совпадения email
                        if (this.new_client_Email != ""){
                            item.client_emails.forEach((item_email) => {
                                //console.log(item_email);
                                //if (item_email.toUpperCase().indexOf(this.new_client_Email.toUpperCase())>-1) {
                                if (item_email.toUpperCase() == this.new_client_Email.toUpperCase()){
                                    //console.log(item_email)
                                    this.list_of_selected_clients.push(item)
                                }
                            });
                        }
                    
                        // ищем совпадения телефонов
                        if (this.new_client_Phone != ""){                        
                            item.client_phones.forEach((item_phone) => {
                                //console.log(item_phone);
                                //if (item_phone.toString().toUpperCase().indexOf(this.to_clear_number(this.new_client_Phone).toString().toUpperCase())>-1) {
                                //if (this.formate_phone2(item_phone).indexOf(this.new_client_Phone)>-1){
                                if (this.formate_phone2(item_phone) == this.new_client_Phone){    
                                    //console.log(item_phone)
                                    // проверяем не добавляли ли этого клиента в список? (чтоб не было задвоений)
                                    //console.log(item.client_id);
                                    is_searched = false;
                                    this.list_of_selected_clients.forEach((selected_client) => {
                                        //console.log(selected_client.client_id);
                                        if (selected_client.client_id == item.client_id){
                                            //console.log("нашел");
                                            is_searched = true;
                                        }
                                    });
                                    if (!is_searched){
                                        //console.log("добавляем");
                                        this.list_of_selected_clients.push(item);
                                    }
                                }
                            });
                        }
                        // ищем совпадения телеграм
                        if (this.new_client_Telegram != ""){                        
                            item.client_telegrams.forEach((item_telegram) => {
                                //console.log(item_telegram);
                                //if (item_telegram.toUpperCase().indexOf(this.new_client_Telegram.toUpperCase())>-1) {
                                if (item_telegram.toUpperCase() == this.new_client_Telegram.toUpperCase()){     
                                    //  console.log(item_telegram)
                                    // проверяем не добавляли ли этого клиента в спиок? (чтоб не было задвоений)
                                    //console.log(item.client_id);
                                    is_searched = false;
                                    this.list_of_selected_clients.forEach((selected_client) => {
                                        //console.log(selected_client.client_id);
                                        if (selected_client.client_id == item.client_id){
                                            //console.log("нашел");
                                            is_searched = true;
                                        }
                                    });
                                    if (!is_searched){
                                        //console.log("добавляем");
                                        this.list_of_selected_clients.push(item);
                                    }

                                }
                            });
                        }

                        
                        /*
                        if (item.client_last_name.indexOf(this.new_client_LastName)>-1) {
                            console.log(item.client_last_name)
                            this.list_of_selected_clients.push(item)
                        }
                        */
                       this.check_for_creating_client();
                    });
 /*
                    let i;

                    for (i=0; i<this.list_of_clients_all.lenght; i++){
                        
                        console.log(this.list_of_clients_all[i].client_last_name);
                    }
*/
                   
                },
                format_phone(in_phone){
                    let ret;
                    //console.log(in_phone.length);
                    if (in_phone){
                        //console.log("строка 290");
                        //console.log(in_phone);
                        //console.log("строка 292");
                        in_phone=in_phone.toString().slice();
                        
                        while (in_phone.length<11){
                            in_phone  = in_phone + "_"
                        }
                        
                        //console.log(in_phone);

                        ret="-"+in_phone.slice(-2);
                        in_phone=in_phone.slice(0,-2);

                        ret="-"+in_phone.slice(-2)+ret;
                        in_phone=in_phone.slice(0,-2);

                        ret=") "+in_phone.slice(-3)+ret;
                        in_phone=in_phone.slice(0,-3);

                        ret=" ("+in_phone.slice(-3)+ret;
                        in_phone=in_phone.slice(0,-3);                

                        ret="+"+in_phone+ret;
                    }
                    return ret;
                },

                check_for_creating_client(){
                    if (!this.new_client_Phone){
                        this.new_client_Phone="";
                    }
                    let is_ready_for_creating_client = true;
                    document.getElementById("apply_button").disabled = true;
                    
                    if (this.new_client_LastName+this.new_client_FirstName+this.new_client_Patronymic == "") {
                        is_ready_for_creating_client = false;
                    }

                    if (this.new_client_Email+this.new_client_Phone+this.new_client_Telegram == "") {
                        is_ready_for_creating_client = false;
                    }

                    if (this.new_client_Email !="" && !this.validateEmail(this.new_client_Email)){
                        is_ready_for_creating_client = false;
                    }

                    if (this.new_client_Phone.length > 0 && this.new_client_Phone.length < 18) {
                        is_ready_for_creating_client = false;
                    }

                    document.getElementById("dublicate_table").style.display = "none";
                    if (this.list_of_selected_clients.length>0){
                        is_ready_for_creating_client = false;
                        document.getElementById("dublicate_table").style.display = "block";
                        
                    }



                    if (is_ready_for_creating_client){

                        document.getElementById("apply_button").disabled = false;
                        //console.log("проверку прошел ");
                    }

                },
                validateEmail(email) {
                    var re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                    return re.test(String(email).toLowerCase());
                } 





    },
    template: 
    `
    <!-- Modal content -->
    <div class="modal-content-50">
        <div class="modal-header">
            <span class="close" @click="onClickCloseForm()">&times;</span>
            <h2>Создание нового клиента</h2>
        </div>
        <div class="modal-body">
            <br>
            <!--button class="msll_middle_button" type="button" @click="test">Отфильтровать</button-->
            <table class='msll_table'>
                <tbody>

                    <tr>
                        <td width='20%'>Фаимлия</td>
                        <td width='80%'><input class="msll_filter" type="input" v-model="new_client_LastName" @input="check_for_creating_client"/></td>
                    </tr>
                    <tr>
                        <td>Имя</td>
                        <td><input class="msll_filter" type="input" v-model="new_client_FirstName" @input="check_for_creating_client"/></td>
                    </tr>
                    <tr>
                        <td>Отчество</td>
                        <td><input class="msll_filter" type="input" v-model="new_client_Patronymic" @input="check_for_creating_client"/></td>
                    </tr>
                    <tr>
                        <td>Почта</td>
                        <td><input class="msll_filter" type="input" v-model="new_client_Email" @input="onChangeKeyData"/></td>
                    </tr>
                    <tr>
                        <td>Телефон</td>
                        <td>
                            <input class="msll_filter" type="text" v-model="new_client_Phone" placeholder="+7 (916) 123-45-67" @input="onChangePhone"/>
                        </td>
                    </tr>
                    <tr>
                        <td>Telegram</td>
                        <td><input class="msll_filter" type="text" v-model="new_client_Telegram" @input="onChangeKeyData"/></td>
                    </tr>

                </tbody>
            </table>

            <button class="msll_middle_button" type="button" @click="onClickApplyForm()" id="apply_button" disabled>Создать</button>
            <button class="msll_middle_button" type="button" @click="onClickCloseForm()">Отменить</button>
            <br/><br/>

            <div id="dublicate_table">
                <table class="msll_table" >
                    <tbody>
                        <tr>
                            <th width='300px'>ФИО</th>
                            <th>Почта</th>
                            <th>Телефон</th>
                            <th>Telegram</th>
                            
                        </tr>

                        <tr v-for="client_item in list_of_selected_clients">
                            <td style='position: relative;'><button  class="msll_button_in_table" type="button" @click='onClikUseThisClient(client_item.client_id)'> {{client_item.client_last_name}} {{client_item.client_first_name}} {{client_item.client_patronymic}}</button></td>
                            
                            <td>
                                <div v-for="item in client_item.client_emails">
                                    <p>{{item}}</p>
                                </div>
                            </td>
                            <td>
                                <div v-for="item in client_item.client_phones">
                                    <p>{{formate_phone(item)}}</p>
                                </div>
                            </td>
                            <td>
                                <div v-for="item in client_item.client_telegrams">
                                    <p>{{item}}</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <br/><br/>
        </div>
        <div class="modal-footer">
        </div>
    </div>      
    `
    }
