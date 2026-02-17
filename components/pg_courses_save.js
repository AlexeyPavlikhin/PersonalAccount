export default {

  data() {
    return {
      current_route_name: "courses",
      users_permited_courses: [],
      link_to_selected_course: "",
      v_key: "3"
    }
  },

  mounted() {
    this.$root.check_for_permition_route(this.current_route_name);
    this.$root.$refs.ref_NavigationMenu.setActivMenuItem(this.current_route_name);

    //определяем доступные для пользователя курсы
    try {
        axios.get('./queries/get_current_user_prmited_courses.php', {
            params: {
            }
        })
        .then((response) => {
            //console.log(response.data)
            if (response.data) {
                //обрабатываем ответ
                //console.log(response.data);
                this.users_permited_courses = response.data;

                // Выбираем первый курс
                for (const users_permited_course of this.users_permited_courses) {
                  this.link_to_selected_course = users_permited_course.course_video_link;
                  //console.log(this.link_to_selected_course);
                  //console.log("BBB");
                  break;
                }

            } else {
                // пустой ответ
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

//vplvrnqfpy25eyvbm4xi
//vplvwqsydipghjxptlxq
//https://runtime.video.cloud.yandex.net/player/video/vplvwqsydipghjxptlxq?autoplay=0&mute=0

  },

  template:
    `
        <div class='sidenav'>

          <table class='msll_table'>
              <tbody>
                <tr>
                    <th width='3%'>#</th>
                    <th >Название курса</th>
                    <th width='25%'>куплен до</th>
                </tr>

                <tr v-for="course in users_permited_courses">
                  <td> 
                    <input type="radio" :id="course.course_name" :value="course.course_video_link" v-model="link_to_selected_course"/>
                  </td>
                  <td>
                    {{course.course_name}}
                  </td>
                  <td>
                    {{course.available_until}}
                  </td>
                </tr>
              </tbody>
          </table>
        </div>

        <div class='msll_body'>
          <div v-if="v_key ==='1'">
            <h1>val 1<\h1>
          </div>

          <div v-if="v_key ==='2'">
            <h1>val 2<\h1>
          </div>

          <div v-if="v_key ==='3'">
            <h1>val 3<\h1>
          </div>


          <div>
            <iframe 
              width="1400" 
              height="787.5" 
              :src="link_to_selected_course"
              allow="autoplay; fullscreen; accelerometer; gyroscope; picture-in-picture; encrypted-media" 
              frameborder="0" 
              scrolling="">
            </iframe>    
            <br/><br/><br/>   
          </div>
        </div>

        `
}
