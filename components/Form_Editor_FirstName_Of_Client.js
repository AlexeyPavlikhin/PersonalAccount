export default {
    emits: ["update_client_data"],
    data() {
        return {
                    detail_client_first_name: "",
                    detail_client_first_name_saved: "",
                    detail_client_id: ""
        }
    },
    methods: {
                // When the user clicks on <span> (x), close the modal
                onClickCloseFormEditorFirstNameOfClient(){
                    document.getElementById("Form_Editor_FirstName_Of_Client").style.display = "none";
                },
                async activate(clientID){
                    //Получаем Фамилию
                    try {
                        this.detail_client_id=clientID;
                        
                        const response = await axios.get('./queries/get_first_name_by_id.php?clientID='+clientID);
                        if (response.data) {
                            //console.log(response.data);
                            this.detail_client_first_name = response.data; 
                            this.detail_client_first_name_saved = response.data;
                            
                            //сделать элемент модальным     
                            document.getElementById("Form_Editor_FirstName_Of_Client").style.display = "block";

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
                onClickApplyFormEditorFirstNameOfClient(){
                    let is_resp_success = false;
                    //Делаем update Фамилии
                    //axios.post("./queries/update_client_first_name_by_id.php", {'data1': 'AAAAAAAAA'})
                    if (this.detail_client_first_name!=this.detail_client_first_name_saved){
                        
                        //alert('разные '+this.detail_client_first_name+' '+this.detail_client_first_name_saved+' ');

                        is_resp_success= axios.post("./queries/update_client_first_name_by_id.php", {clientID: this.detail_client_id, clientFirstName: this.detail_client_first_name})
                        .then(function (response) {
                            //console.log(response.data);
                            if (response.data=="1"){
                                //this.is_resp_success = true;
                                //is_resp_success1 = true;
                                return true;
                            } else {
                                alert("Обновлено "+response.data+" записей");
                            }
                        })
                        .catch(function (error) {
                            alert(error);
                            console.log(error);
                        });

                        if (is_resp_success) {
                            //alert('resp_success ' + this.detail_client_id + " " +this.detail_client_first_name);
                            this.$emit('update_client_data', 'FirstName');
                        }
                    }
                    
                    this.onClickCloseFormEditorFirstNameOfClient();

                    
                },
    },
    template: 
    `
    <!-- Modal content -->
    <div class="modal-content-40">
        <div class="modal-header">
            <span class="close" @click="onClickCloseFormEditorFirstNameOfClient()">&times;</span>
            <h2>Изменение имени клиента</h2>
        </div>
        <div class="modal-body">
            <br>
            <input class="msll_filter" type="input" id="item_for_search" v-model="detail_client_first_name" />
            <button class="msll_middle_button" type="button" @click="onClickApplyFormEditorFirstNameOfClient()">Применить</button>
            <button class="msll_middle_button" type="button" @click="onClickCloseFormEditorFirstNameOfClient()">Отменить</button>

        </div>
        <div class="modal-footer">
        </div>
    </div>      
    `
    }
