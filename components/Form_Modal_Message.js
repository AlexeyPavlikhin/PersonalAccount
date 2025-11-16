export default {
    data() {
        return {
            message_for_user: [],
            ref_to_parent: ""
        }
    },
    methods: {
        init(in_ref_to_parent, in_message_for_user){
            this.ref_to_parent = in_ref_to_parent;
            this.message_for_user = in_message_for_user.split("<br>");
            //console.log( this.message_for_user );
        },                

        CloseForm(){
            document.getElementById("id_FormModalMessage").style.display = "none";
            //document.body.style.overflow = '';
        },

        onClickCloseForm(){
            this.CloseForm();
        },

        onClickCancel(){
            this.CloseForm();
        }
    },
    template: 
    `
    <!-- Modal content -->
    <div class="modal-content-40">
        <div class="modal-header">
            <span class="close" @click="onClickCloseForm()">&times;</span>
            <h2>Внимание!!!</h2>
        </div>
        <div class="modal-body">
            <div class="form-element" v-for="string_of_message in message_for_user">
            
            <p class="p-text-aligan-justify">{{string_of_message}}</p>

            </div>
            <input class="msll_middle_button" type="button" value = "OK" @click="onClickCancel()">
        </div>
    </div>      
    `
    }
