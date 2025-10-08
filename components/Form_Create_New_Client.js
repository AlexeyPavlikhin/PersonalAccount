export default {
    emits: ["client_creted"],
    data() {
        return {
                    new_client_LastName: "",
                    new_client_FirstName: "",
                    new_client_Patronymic: "",
                    new_client_id: "",
                    list_of_clients_all: "",
                    list_of_selected_clients: "",
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
                    this.new_client_id = "";
                },

                async activate(in_list_of_clients){
                    this.list_of_clients_all=in_list_of_clients;
                },
               
                async onClickApplyForm(){
                    if (this.new_client_LastName+this.new_client_FirstName+this.new_client_Patronymic != "") {
                        
                        let is_resp_success = false;
                        var var_new_client_id = "";
                        const self = this;

                        //Делаем insert записи о новом клиенте
                            
                        is_resp_success= axios.post("./queries/create_new_client.php", {client_LastName: this.new_client_LastName, client_FirstName: this.new_client_FirstName, client_Patronymic: this.new_client_Patronymic})
                        .then(function (response) {
                            //setTimeout(function(){alert(response.data[0].id)},10000);
                            //alert(response.data[0].id);
                            //alert(response.data);
                            //console.log(response);

                            //console.log(response.data[0].id);
                            //alert("1: "+response.data[0].id);


                            ////self.new_client_id = response.data[0].id; 
                            ////self.$emit('client_creted', response.data[0].id);
                            self.new_client_id = response.data; 
                            self.$emit('client_creted', response.data);
                            self.onClickCloseForm();
                        })
                        .catch(function (error) {
                            alert(error);
                            console.log(error);
                        });

                        

                    } else {
                        alert("Минимум одно из полей должно быть заполнено");

                    }

                    
                },

                onClikUseThisClient(inClientID){
                    console.log(inClientID);
                    
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
                
                onClickFieldLastName(){
                    //console.log(this.list_of_clients_all)
                    this.list_of_clients_all.forEach((item) => {
                    if (item) {
                        console.log(item.client_last_name)
                        this.list_of_selected_clients.push(item)
                    }
                    });
 /*
                    let i;

                    for (i=0; i<this.list_of_clients_all.lenght; i++){
                        
                        console.log(this.list_of_clients_all[i].client_last_name);
                    }
*/
                }



    },
    template: 
    `
    <!-- Modal content -->
    <div class="modal-content-editor">
        <div class="modal-header">
            <span class="close" @click="onClickCloseForm()">&times;</span>
            <h2>Создание нового клиента</h2>
        </div>
        <div class="modal-body">
            <br>
            <button class="msll_middle_button" type="button" @click="onClickFieldLastName">Отфильтровать</button>
            <table class='msll_table'>
                <tbody>

                    <tr>
                        <td width='20%'>Фаимлия</td>
                        <td width='80%'><input class="msll_filter" type="input" v-model="new_client_LastName"/></td>
                    </tr>
                    <tr>
                        <td>Имя</td>
                        <td><input class="msll_filter" type="input" v-model="new_client_FirstName"/></td>
                    </tr>
                    <tr>
                        <td>Отчество</td>
                        <td><input class="msll_filter" type="input" v-model="new_client_Patronymic"/></td>
                    </tr>
                </tbody>
            </table>

            <button class="msll_middle_button" type="button" @click="onClickApplyForm()">Создать</button>
            <button class="msll_middle_button" type="button" @click="onClickCloseForm()">Отменить</button>

            <table class='msll_table'>
                <tbody>
                    <tr>
                        <th width='250px'>ФИО 11111111111111111111111111</th>
                        <th width='25px'>Почта</th>
                        <th width='25px'>Телефон</th>
                        <th width='25px'>Telegram</th>
                        
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
        <div class="modal-footer">
        </div>
    </div>      
    `
    }
