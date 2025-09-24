export default {
    emits: ["client_creted"],
    data() {
        return {
                    new_client_LastName: "",
                    new_client_FirstName: "",
                    new_client_Patronymic: "",
                    new_client_id: ""
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
/*
                async activate(clientID){
                    
                    //Получаем Фамилию
                    try {
                        this.detail_client_id=clientID;
                        
                        const response = await axios.get('./queries/get_comment_by_id.php?clientID='+clientID);
                        if (response.data) {
                            //console.log(response.data);
                            this.detail_client_comment = response.data; 
                            this.detail_client_comment_saved = response.data;
                            
                            //сделать элемент модальным     
                            document.getElementById("Form_Editor_Comment_Of_Client").style.display = "block";

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
*/                
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

                    };

                    //alert("2: "+this.new_client_id);
/*
*/                    


                    
                },
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

        </div>
        <div class="modal-footer">
        </div>
    </div>      
    `
    }
