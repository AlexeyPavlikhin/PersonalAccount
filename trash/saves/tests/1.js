export default {
    data() {
        return {
            count: 0
        }
    },
  template: `
    <button @click="count++">
      Вы нажали на меня {{ count }} раз.
    </button>`
  // Can also target an in-DOM template:
  // template: '#my-template-element'
}