import vMaska from "./vue";

export default {
    directives: { maska: vMaska },
    data: () => ({
      options: {
        mask: "#-#",
        eager: true
      }
    }),
    template: 
    `
    <input v-maska="options" data-maska-reversed>
    `
}
